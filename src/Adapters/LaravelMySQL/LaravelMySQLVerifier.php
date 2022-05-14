<?php

namespace CodeDistortion\Adapt\Adapters\LaravelMySQL;

use CodeDistortion\Adapt\Adapters\Interfaces\VerifierInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Exceptions\AdaptVerificationException;
use CodeDistortion\Adapt\Support\Settings;
use CodeDistortion\Adapt\Support\StringSupport;
use stdClass;

/**
 * Database-adapter methods related to verifying a Laravel/MySQL database's structure and content.
 */
class LaravelMySQLVerifier implements VerifierInterface
{
    use InjectTrait;



    /** @var string[]|null An internal cache of the names of the tables that exist. */
    private ?array $tableList = null;

    /** @var array<string, string[]> An internal cache of the table's primary-keys. */
    private array $primaryKeys = [];

    /** @var array<string, string> An internal cache of each table's CREATE TABLE queries. */
    private array $createTableQueries = [];



    /**
     * Determine whether this database can be verified or not (for checking of database structure and content).
     *
     * @return boolean
     */
    public function isVerifiable(): bool
    {
        return true;
    }



    /**
     * Create and populate the verification table.
     *
     * @param boolean $createStructureHash Generate hashes of the create-table queries?.
     * @param boolean $createDataHash      Generate hashes of the table's data?.
     * @return void
     */
    public function setUpVerification(bool $createStructureHash, bool $createDataHash): void
    {
        $logTimer = $this->di->log->newTimer();

        $this->createVerificationTable();
        $this->populateVerificationTable($createStructureHash, $createDataHash);

//        $list = $this->readableList([
//            $createStructureHash ? 'structure-hashes' : '',
//            $createDataHash ? 'data-hashes' : '',
//        ]);
//        $this->di->log->debug("Set up the verification table ($list)", $logTimer);
        $this->di->log->debug("Set up database verification", $logTimer);
    }





    /**
     * Record that a test with verification has begun.
     *
     * @return void
     */
    public function recordVerificationStart(): void
    {
        $this->di->db->update("UPDATE `" . Settings::REUSE_TABLE . "` SET `validation_passed` = 0");
    }

    /**
     * Record that a test with verification has finished, and the database is clean.
     *
     * @return void
     */
    public function recordVerificationStop(): void
    {
        $this->di->db->update("UPDATE `" . Settings::REUSE_TABLE . "` SET `validation_passed` = 1");
    }





    /**
     * Verify that the database's structure hasn't changed.
     *
     * @param boolean $newLineAfter Whether a new line should be added after logging or not.
     * @return void
     * @throws AdaptVerificationException When the database structure or content has changed.
     */
    public function verifyStructure(bool $newLineAfter): void
    {
        $logTimer = $this->di->log->newTimer();

        $this->performDatabaseStructureVerification();

        $this->di->log->debug(
            "Verified that the structure of database \"{$this->configDTO->database}\" hasn't changed",
            $logTimer,
            $newLineAfter
        );
    }

    /**
     * Verify that the database's content hasn't changed.
     *
     * @param boolean $newLineAfter Whether a new line should be added after logging or not.
     * @return void
     * @throws AdaptVerificationException When the database structure or content has changed.
     */
    public function verifyData(bool $newLineAfter): void
    {
        $logTimer = $this->di->log->newTimer();

        $this->performDatabaseDataVerification();

        $this->di->log->debug(
            "Verified that content in database \"{$this->configDTO->database}\" hasn't changed",
            $logTimer,
            $newLineAfter
        );
    }





    /**
     * Create the table to contain meta-data about all of the tables.
     *
     * @return void
     */
    private function createVerificationTable(): void
    {
        $query = "CREATE TABLE `" . Settings::VERIFICATION_TABLE . "` ("
            . "`table` varchar(63) NOT NULL, "
            . "`structure_hash` varchar(32) NULL DEFAULT NULL, "
            . "`data_hash` varchar(32) NULL DEFAULT NULL, "
            . "PRIMARY KEY(`table`)"
            . ")";
        $this->di->db->directExec($query);
    }

    /**
     * Get the list of tables that were recorded before.
     *
     * @return stdClass[]
     */
    private function getRecordedTableList(): array
    {
        $table = Settings::VERIFICATION_TABLE;
        $query = "SELECT `table`, `structure_hash`, `data_hash` FROM `$table` ORDER BY `table`";
        $rows = $this->di->db->select($query);

        $tables = [];
        foreach ($rows as $row) {
            $tables[$row->table] = $row; // key by table name
        }
        return $tables;
    }



    /**
     * Populate the verification table with hashes and info needed later.
     *
     * @param boolean $createStructureHash Generate hashes of the create-table queries?.
     * @param boolean $createDataHash      Generate hashes of the table's data?.
     * @return void
     */
    private function populateVerificationTable(bool $createStructureHash, bool $createDataHash): void
    {
        $tables = $this->getTableList();
        if (!count($tables)) {
            return;
        }

        $placeholders = $values = [];
        foreach ($tables as $table) {

            $tableStructureHash = $createStructureHash
                ? $this->generateStructureHash($table)
                : null;

            $dataHash = $createDataHash
                ? $this->generateDataHash($table)
                : null;

            $placeholders[] = "(?, ?, ?)";
            $currentValues = [$table, $tableStructureHash, $dataHash];
            $values = array_merge($values, $currentValues);
        }

        $allPlaceholders = implode(', ', $placeholders);
        $query = "INSERT INTO `" . Settings::VERIFICATION_TABLE . "` "
            . "(`table`, `structure_hash`, `data_hash`) "
            . "VALUES $allPlaceholders";

        $this->di->db->insert($query, $values);
    }



    /**
     * Generate a table's structure hash.
     *
     * @param string  $table        The table to generate a hash for.
     * @param boolean $forceRefresh Will overwrite the internal cache when true.
     * @return string
     */
    private function generateStructureHash(string $table, bool $forceRefresh = false): string
    {
        $query = $this->getCreateTableQuery($table, $forceRefresh);
        $query = $this->removeAutoIncrementFromCreateTableQuery($query);
        return md5($query);
    }

    /**
     * Load the CREATE TABLE query for a particular table from the database.
     *
     * @param string  $table        The table to generate the query for.
     * @param boolean $forceRefresh Will overwrite the internal cache when true.
     * @return string
     */
    public function getCreateTableQuery(string $table, bool $forceRefresh = false): string
    {
        if (($forceRefresh) || (!isset($this->createTableQueries[$table]))) {
            $rows = $this->di->db->select("SHOW CREATE TABLE `$table`");
            $this->createTableQueries[$table] = $rows[0]->{"Create Table"};
        }

        return $this->createTableQueries[$table];
    }

    /**
     * Remove the "AUTO_INCREMENT=xxx" part from a CREATE TABLE query.
     *
     * @param string $query The CREATE TABLE query to alter.
     * @return string
     */
    private function removeAutoIncrementFromCreateTableQuery(string $query): string
    {
        $matched = preg_match(
            '/\) ENGINE=[^\n]+[^\n]*( AUTO_INCREMENT=[0-9]+ )[^\n]*$/',
            $query,
            $matches
        );

        if (!$matched) {
            return $query;
        }

        $lastPart = $matches[0];
        $autoIncrement = $matches[1]; // e.g. " AUTO_INCREMENT=100 "
        $newLastPart = str_replace($autoIncrement, ' ', $lastPart);

        return StringSupport::strReplaceLast($lastPart, $newLastPart, $query);
    }



    /**
     * Generate a table's data hash.
     *
     * @param string $table The table to generate a hash for.
     * @return string
     */
    private function generateDataHash(string $table): string
    {
        $primaryKey = $this->getPrimaryKey($table);

        $orderBy = count($primaryKey)
            ? "ORDER BY `" . implode("`, `", $primaryKey) . "`"
            : '';

        $rows = $this->di->db->select("SELECT * FROM `$table` $orderBy");
        return md5(serialize($rows));
    }





    /**
     * Verify the structure of the databases' tables.
     *
     * @return void
     * @throws AdaptVerificationException When the database structure or content has changed.
     */
    private function performDatabaseStructureVerification(): void
    {
        $previousTableList = $this->getRecordedTableList();
        foreach ($this->getTableList(true) as $table) {

            // the table is here now but wasn't before
            if (!isset($previousTableList[$table])) {
                throw AdaptVerificationException::tableWasCreated((string) $this->configDTO->database, $table);
            }

            $this->checkTableStructureHash($table, $previousTableList[$table]->structure_hash);

            // remove so left-over ones can be picked up below
            unset($previousTableList[$table]);
        }

        // left-over tables have been removed
        foreach (array_keys($previousTableList) as $table) {
            throw AdaptVerificationException::tableWasRemoved((string) $this->configDTO->database, $table);
        }
    }

    /**
     * Verify the structure of the databases' tables.
     *
     * @return void
     * @throws AdaptVerificationException When the database structure or content has changed.
     */
    private function performDatabaseDataVerification(): void
    {
        $previousTableList = $this->getRecordedTableList();
        foreach (array_keys($previousTableList) as $table) {
            $this->checkTableDataHash($table, $previousTableList[$table]->data_hash);
        }
    }



    /**
     * Throw an exception if the table's structure has changed.
     *
     * @param string $table        The table being inspected.
     * @param string $previousHash What the hash was before.
     * @return void
     * @throws AdaptVerificationException When the hash doesn't match.
     */
    private function checkTableStructureHash(string $table, string $previousHash)
    {
        $currentHash = $this->generateStructureHash($table, true);
        if ($currentHash == $previousHash) {
            return;
        }
        throw AdaptVerificationException::tableStructureHasChanged((string) $this->configDTO->database, $table);
    }

    /**
     * Throw an exception if the table's content has changed.
     *
     * @param string $table        The table being inspected.
     * @param string $previousHash What the hash was before.
     * @return void
     * @throws AdaptVerificationException When the hash doesn't match.
     */
    private function checkTableDataHash(string $table, string $previousHash)
    {
        $currentHash = $this->generateDataHash($table);
        if ($currentHash == $previousHash) {
            return;
        }
        throw AdaptVerificationException::tableContentHasChanged((string) $this->configDTO->database, $table);
    }



    /**
     * Generate a list of the tables that exist.
     *
     * (Excludes all Adapt tables).
     *
     * @param boolean $forceRefresh Will overwrite the internal cache when true.
     * @return string[]
     */
    public function getTableList(bool $forceRefresh = false): array
    {
        return $forceRefresh
            ? $this->tableList = $this->readTableList()
            : $this->tableList ??= $this->readTableList();
    }

    /**
     * Generate a list of the tables that exist from the database.
     *
     * (Excludes views and Adapt meta-tables).
     *
     * @return string[]
     */
    private function readTableList(): array
    {
        $rows = $this->di->db->select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");

        $tables = [];
        foreach ($rows as $row) {

            unset($row->Table_type);

            $vars = get_object_vars($row);
            $table = reset($vars);
            $table = is_string($table) ? $table : ''; // phpstan

            if (mb_strpos($table, Settings::ADAPT_TABLE_PREFIX) !== 0) {
                $tables[] = $table;
            }
        }

        return $tables;
    }





    /**
     * Get a table's primary-key.
     *
     * Note: the return value is an array, which may contain more than one field.
     * Note: may return the first "unique" key instead if a primary-key doesn't exist.
     *
     * @param string $table The table to get the primary-key for.
     * @return string[]
     */
    public function getPrimaryKey(string $table): array
    {
        return $this->primaryKeys[$table] ??= $this->resolvePrimaryKey($table);
    }

    /**
     * Resolve a table's primary-key.
     *
     * Note: may return the first "unique" key instead if a primary-key doesn't exist.
     *
     * @param string $table The table to get the primary-key for.
     * @return string[]
     */
    private function resolvePrimaryKey(string $table): array
    {
        $fields = $this->readPrimaryKeyIndex($table) ?? $this->readFirstUniqueIndex($table);

//        if (is_null($fields)) {
//            throw new Exception("Table \"$table\" doesn't have a primary-key");
//        }

        return $fields ?? [];
    }

    /**
     * Pick the table's PRIMARY KEY fields.
     *
     * @param string $table The table to look at.
     * @return string[]|null
     */
    private function readPrimaryKeyIndex(string $table): ?array
    {
        $rows = $this->di->db->select("SHOW INDEX FROM `$table` WHERE Key_name = 'PRIMARY'");
        return $this->orderIndexFields($rows);
    }

    /**
     * Pick the table's first UNIQUE INDEX fields.
     *
     * @param string $table The table to look at.
     * @return string[]|null
     */
    private function readFirstUniqueIndex(string $table): ?array
    {
        $rows = $this->di->db->select("SHOW INDEX FROM `$table` WHERE Non_unique = 0");

        // pick out only the first UNIQUE index
        $firstKeyName = null;
        foreach ($rows as $index => $row) {
            if ((is_null($firstKeyName)) || ($row->Key_name == $firstKeyName)) {
                $firstKeyName = $row->Key_name;
            } else {
                unset($rows[$index]);
            }
        }

        return $this->orderIndexFields($rows);
    }

    /**
     * Take the index rows, order them and pick out the field names.
     *
     * The rows come from "SHOW INDEX FROM <table>".
     *
     * @param stdClass[] $rows The rows from the database.
     * @return string[]|null
     */
    private function orderIndexFields(array $rows): ?array
    {
        if (!count($rows)) {
            return null;
        }

        // sort them by the Seq_in_index field
        $callback = function ($a, $b) {
            if ($a->Seq_in_index == $b->Seq_in_index) {
                return 0;
            }
            return ($a->Seq_in_index < $b->Seq_in_index) ? -1 : 1;
        };
        usort($rows, $callback);

        $fields = [];
        foreach ($rows as $row) {
            $fields[] = $row->Column_name;
        }

        return $fields;
    }
}
