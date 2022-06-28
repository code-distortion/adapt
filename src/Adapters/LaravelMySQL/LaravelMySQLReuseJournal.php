<?php

namespace CodeDistortion\Adapt\Adapters\LaravelMySQL;

use CodeDistortion\Adapt\Adapters\Interfaces\ReuseJournalInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectInclVerifierTrait;
use CodeDistortion\Adapt\Exceptions\AdaptJournalException;
use CodeDistortion\Adapt\Support\Settings;
use CodeDistortion\Adapt\Support\StringSupport;
use PDOException;
use Throwable;

/**
 * Database-adapter methods related to managing Laravel/MySQL reuse through journaling.
 */
class LaravelMySQLReuseJournal implements ReuseJournalInterface
{
    use InjectInclVerifierTrait;



    /** @var string The id field added to each journal table. */
    private const ID_FIELD = '____adapt_id____';

    /** @var string The enum field to store the action being performed ("INSERT" / "UPDATE" / "DELETE"). */
    private const ACTION_FIELD = '____adapt_action____';



    /**
     * Determine if a journal can be used on this database (for database re-use).
     *
     * @return boolean
     */
    public function supportsJournaling(): bool
    {
        return true;
    }



    /**
     * Create journal tables and triggers to populate them.
     *
     * @return void
     * @throws AdaptJournalException When something goes wrong.
     */
    public function setUpJournal(): void
    {
        $logTimer = $this->di->log->newTimer();

        $this->createChangeTrackerTable();
        $this->setUpJournalTablesAndTriggers();

        $this->di->log->vDebug('Set up the journaling stores', $logTimer);
    }

    /**
     * Record that journaling has begun.
     *
     * @return void
     */
    public function recordJournalingStart(): void
    {
        $this->di->db->update("UPDATE `" . Settings::REUSE_TABLE . "` SET `journal_reusable` = 0");
    }

    /**
     * Record that journaling has finished, and the database is clean.
     *
     * @return void
     */
    private function recordJournalingStop(): void
    {
        $this->di->db->update("UPDATE `" . Settings::REUSE_TABLE . "` SET `journal_reusable` = 1");
    }





    /**
     * Create the table that keeps track of the tables that have changed.
     *
     * @return void
     */
    private function createChangeTrackerTable(): void
    {
        $table = Settings::JOURNAL_CHANGE_TRACKER_TABLE;
        $query = "CREATE TABLE `$table` (`table` varchar(63) NOT NULL, PRIMARY KEY(`table`))";
        $this->di->db->statement($query);
    }





    /**
     * Create the journal tables, and triggers to populate them.
     *
     * @return void
     * @throws AdaptJournalException When the journal table or triggers cannot be created.
     */
    private function setUpJournalTablesAndTriggers(): void
    {
        foreach ($this->verifier->getTableList() as $table) {
            $this->createJournalTable($table);
            $this->createTableTriggers($table);
        }
    }





    /**
     * Create the table that will keep a journal of changes in the original table.
     *
     * @param string $srcTable The table to copy the structure from.
     * @return void
     * @throws AdaptJournalException When the journal table cannot be created.
     */
    private function createJournalTable(string $srcTable): void
    {
        try {
            $createTableQuery = $this->verifier->getCreateTableQuery($srcTable);
            $journalTable = $this->generateJournalTableName($srcTable);

            $journalQuery = $this->removeAndReplaceUnneededThings($createTableQuery);
            $journalQuery = $this->replaceEndOfTableSection($journalQuery, $srcTable);
            $journalQuery = $this->replaceTableNameAndAddNewFields($journalQuery, $srcTable, $journalTable);

            try {
                $this->di->db->statement($journalQuery);
            } catch (PDOException $e) {
                throw AdaptJournalException::journalCreateTableQueryFailed($srcTable, $e);
            }

        } catch (AdaptJournalException $e) {
            throw $e; // just rethrow as is
        } catch (Throwable $e) {
            throw AdaptJournalException::cannotCreateJournalTable($srcTable, $e);
        }
    }

    /**
     * Update a query by removing the "AUTO_INCREMENT" part (if present).
     *
     * @param string $query The query to update.
     * @return string
     */
    private function removeAndReplaceUnneededThings(string $query): string
    {
        // @todo make sure these don't update comments etc

        // existing fields should not use auto-increment
        $query = str_replace(" AUTO_INCREMENT,\n", ",\n", $query);

        // some default values break the query.
        // e.g. SQLSTATE[42000]: Syntax error or access violation: 1067 Invalid default value for 'deliver_at'
        //      "`deliver_at` timestamp NOT NULL"
        $query = str_replace(" DEFAULT '0000-00-00 00:00:00'", '', $query);

        // the values are copied so default values don't matter
        $query = str_replace(' NOT NULL', ' NULL DEFAULT NULL', $query);
        $query = str_replace(' DEFAULT current_timestamp() ON UPDATE current_timestamp()', '', $query);

        return $query;
    }

    /**
     * Find the last part of the query (containing indexes, primary-key, etc.), and replace them with a new primary-key.
     *
     * @param string $query    The query to update.
     * @param string $srcTable The table's original name.
     * @return string
     * @throws AdaptJournalException When the query cannot be manipulated.
     */
    private function replaceEndOfTableSection(string $query, string $srcTable): string
    {
        $matched = preg_match_all(
            '/(,\n  [^`][^\n]+)*(,\n  [^`][^\n]+\n)?(\) ENGINE=[^\n]+)$/',
            $query,
            $matches
        );
        if (!$matched) {
            throw AdaptJournalException::cannotReplaceCreateQueryKeys($srcTable);
        }

        $engineLine = $matches[3][0]; // e.g. ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci)"
        $lastPart = $matches[0][0]; // the "engine" line, and any lines above that don't define a field

        $newPrimaryKeyField = self::ID_FIELD;
        $newPrimaryKey = "PRIMARY KEY (`$newPrimaryKeyField`)";
        $newLastPart = ",\n  $newPrimaryKey\n$engineLine"; // put the engine line back in, as well as a new primary-key

        return StringSupport::strReplaceLast($lastPart, $newLastPart, $query);
    }

    /**
     * Replace the table's name, and add in the new bigint primary-key field.
     *
     * @param string $query        The query to update.
     * @param string $srcTable     The table's original name.
     * @param string $newTableName The table's new name.
     * @return string
     * @throws AdaptJournalException When the string replacement fails.
     */
    private function replaceTableNameAndAddNewFields(
        string $query,
        string $srcTable,
        string $newTableName
    ): string {

        $newPrimaryKeyField = self::ID_FIELD;
        $actionField = self::ACTION_FIELD;

        $newPrimaryKeyColumn = "`$newPrimaryKeyField` bigint(20) unsigned NOT NULL AUTO_INCREMENT,";
        $newActionColumn = "`$actionField` enum('INSERT','UPDATE','DELETE') NOT NULL,";

        $return = (string) preg_replace(
            '/^CREATE TABLE `([^`]+)` \(/',
            "CREATE TABLE `$newTableName` (\n  $newPrimaryKeyColumn\n  $newActionColumn",
            $query,
            1
        );

        if ($return == $query) {
            throw AdaptJournalException::cannotAlterCreateQueryTableName($srcTable);
        }

        return $return;
    }





    /**
     * Create the triggers to populate the journal table.
     *
     * @param string $srcTable The table to listen to and copy from.
     * @return void
     * @throws AdaptJournalException When a trigger cannot be created.
     */
    private function createTableTriggers(string $srcTable): void
    {
        $fields = $this->getTableFields($srcTable);
        $this->createTrigger($srcTable, 'INSERT', $fields, 'NEW');
        $this->createTrigger($srcTable, 'UPDATE', $fields, 'OLD');
        $this->createTrigger($srcTable, 'DELETE', $fields, 'OLD');
    }

    /**
     * Get the list of a table's fields.
     *
     * @param string $table The table to get fields for.
     * @return string[]
     */
    private function getTableFields(string $table): array
    {
        $rows = $this->di->db->select("DESCRIBE `$table`");
        $fields = [];
        foreach ($rows as $row) {
            $fields[] = $row->Field;
        }
        return $fields;
    }

    /**
     * Generate a list of the fields, escaped ready for a query.
     *
     * @param string[] $fields   The fields to escape.
     * @param string   $copyFrom The set of values to copy from - "NEW" / "OLD".
     * @return string
     */
    private function escapeFieldList(array $fields, string $copyFrom = ''): string
    {
        $return = [];
        foreach ($fields as $field) {
            $return[] = $copyFrom ? "$copyFrom.`$field`" : "`$field`";
        }
        return implode(", ", $return);
    }

    /**
     * Create a trigger to copy data from a table to its journal table.
     *
     * @param string   $srcTable    The table to copy from.
     * @param string   $triggerType The trigger type - "INSERT" / "UPDATE" / "DELETE".
     * @param string[] $fields      The table's fields.
     * @param string   $copyFrom    The set of values to copy from - "NEW" / "OLD".
     * @return void
     * @throws AdaptJournalException When the trigger cannot be created.
     */
    private function createTrigger(string $srcTable, string $triggerType, array $fields, string $copyFrom): void
    {
        try {
            $journalTable = $this->generateJournalTableName($srcTable);
            $triggerName = $this->generateTriggerName($srcTable, $triggerType);

            $copyFromFields = $this->escapeFieldList($fields, $copyFrom);
            $fields = $this->escapeFieldList($fields);
            $actionField = self::ACTION_FIELD;
            $journalChangeListTable = Settings::JOURNAL_CHANGE_TRACKER_TABLE;

            $query = "CREATE TRIGGER `$triggerName` "
                . "AFTER $triggerType ON `$srcTable` "
                . "FOR EACH ROW "
                . "BEGIN "
                . "  INSERT INTO `$journalTable` (`$actionField`, {$fields}) VALUES ('$triggerType', $copyFromFields);"
                . "  INSERT IGNORE INTO `$journalChangeListTable` (`table`) VALUES ('$srcTable');"
                . "END";

            // use PDO's exec(..) directly, avoiding the below exception (not sure how to get around it otherwise):
            // PDOException: SQLSTATE[HY000]: General error: 2014 Cannot execute queries while other unbuffered queries
            //   are active.  Consider using PDOStatement::fetchAll().  Alternatively, if your code is only ever going
            //   to run against mysql, you may enable query buffering by setting the PDO::MYSQL_ATTR_USE_BUFFERED_QUERY
            //   attribute.
            $this->di->db->directExec($query);

        } catch (Throwable $e) {
            throw AdaptJournalException::cannotCreateJournalTrigger($srcTable, $e);
        }
    }





    /**
     * Take the journal information and "undo" the changes.
     *
     * @param boolean $newLineAfter Whether a new line should be added after logging or not.
     * @return void
     */
    public function reverseJournal(bool $newLineAfter): void
    {
        $logTimer = $this->di->log->newTimer();

        $madeChanges = $this->undoAllTableChanges();
        $this->recordJournalingStop();

        $message = $madeChanges
            ? "Used the journal to reverse changes to "
            : "There aren't any journaled changes to reverse for ";
        $message .= "\"{$this->configDTO->connection}\" database \"{$this->configDTO->database}\"";

        $this->di->log->vDebug($message, $logTimer, $newLineAfter);
    }

    /**
     * Find tables with changes and undo them.
     *
     * @return boolean Whether changes were made or not.
     */
    private function undoAllTableChanges(): bool
    {
        $tables = $this->listTablesWithChanges();
        if (!$tables) {
            return false;
        }

        $foreignKeyChecksOn = $this->isForeignKeyChecksOn();
        $this->disableForeignKeyChecks($foreignKeyChecksOn);

        foreach ($tables as $table) {
            $this->undoTableChanges($table);
        }

        $this->enableForeignKeyChecks($foreignKeyChecksOn);

        $this->resetChangeTrackerTable();

        return true;
    }

    /**
     * Get the list of tables with changes.
     *
     * @return string[]
     */
    private function listTablesWithChanges(): array
    {
        $rows = $this->di->db->select("SELECT `table` FROM `" . Settings::JOURNAL_CHANGE_TRACKER_TABLE . "`");

        $tables = [];
        foreach ($rows as $row) {
            $tables[] = $row->table;
        }
        return $tables;
    }

    /**
     * Check if the foreign_key_checks setting is currently on.
     *
     * @return boolean
     */
    private function isForeignKeyChecksOn(): bool
    {
        $rows = $this->di->db->select("SELECT @@SESSION.foreign_key_checks AS foreign_key_checks");
        return (bool) $rows[0]->foreign_key_checks;
    }

    /**
     * Disable MySQL's foreign_key_checks setting.
     *
     * @param boolean $performUpdate Should the change actually be applied?.
     * @return void
     */
    private function disableForeignKeyChecks(bool $performUpdate): void
    {
        if (!$performUpdate) {
            return;
        }

        $this->di->db->statement("SET FOREIGN_KEY_CHECKS = 0");
    }

    /**
     * Enable MySQL's foreign_key_checks setting.
     *
     * @param boolean $performUpdate Should the change actually be applied?.
     * @return void
     */
    private function enableForeignKeyChecks(bool $performUpdate): void
    {
        if (!$performUpdate) {
            return;
        }

        $this->di->db->statement("SET FOREIGN_KEY_CHECKS = 1");
    }

    /**
     * Reset the list of tables with changes.
     *
     * @return void
     */
    private function resetChangeTrackerTable(): void
    {
        $this->di->db->statement("TRUNCATE `" . Settings::JOURNAL_CHANGE_TRACKER_TABLE . "`");
    }





    /**
     * Undo the changes listed in a journal table.
     *
     * @param string $table The table to update.
     * @return void
     * @throws AdaptJournalException When changes can't be undone.
     */
    private function undoTableChanges(string $table): void
    {
        try {
            $this->applyUndoTableChanges($table);
        } catch (AdaptJournalException $e) {
            throw $e; // just rethrow as is
        } catch (Throwable $e) {
            throw AdaptJournalException::couldNotReverseJournal($table, $e);
        }
    }

    /**
     * Undo the changes listed in a journal table.
     *
     * @param string $table The table to update.
     * @return void
     * @throws AdaptJournalException When the table has no primary-key (or unique index).
     */
    private function applyUndoTableChanges(string $table): void
    {
        $primaryKey = $this->verifier->getPrimaryKey($table);
        if (!count($primaryKey)) {
            throw AdaptJournalException::tableHasNoPrimaryKey($table);
        }

        $journalTable = $this->generateJournalTableName($table);
        $orderBy = self::ID_FIELD;
        $rows = $this->di->db->select("SELECT * FROM `$journalTable` ORDER BY `$orderBy` DESC");

        foreach ($rows as $row) {

            $action = $row->{self::ACTION_FIELD};
            unset($row->{self::ID_FIELD}, $row->{self::ACTION_FIELD});

            $values = get_object_vars($row);

            switch ($action) {
                case 'INSERT':
                    $this->journalDeleteRow($table, $values, $primaryKey);
                    break;
                case 'UPDATE':
                    $this->journalUpdateRow($table, $values, $primaryKey);
                    break;
                case 'DELETE':
                    $this->journalInsertRow($table, $values);
                    break;
            }
        }

        $this->di->db->statement("TRUNCATE TABLE `$journalTable`");
    }

    /**
     * Insert a row - reversing a DELETE query.
     *
     * @param string              $table  The table to insert into.
     * @param array<string|mixed> $values The values to insert.
     * @return void
     */
    private function journalInsertRow(string $table, array $values): void
    {
        $fields = '`' . implode('`, `', array_keys($values)) . '`';
        $valuePlaceholders = implode(', ', array_fill(0, count($values), '?'));
        $this->di->db->insert("INSERT INTO `$table` ($fields) VALUES ($valuePlaceholders)", array_values($values));
    }

    /**
     * Update a row - reversing an UPDATE query.
     *
     * @param string              $table      The table to update.
     * @param array<string|mixed> $values     The values to update.
     * @param string[]            $primaryKey The primary-key field names.
     * @return void
     */
    private function journalUpdateRow(string $table, array $values, array $primaryKey): void
    {
        $pkPlaceholders = $valuePlaceholders = [];
        foreach ($values as $field => $value) {

            in_array($field, $primaryKey)
                ? $pkPlaceholders[] = "`$field` = :$field"
                : $valuePlaceholders[] = "`$field` = :$field";
        }

        $pkPlaceholders = implode(' AND ', $pkPlaceholders);
        $valuePlaceholders = implode(', ', $valuePlaceholders);

        $this->di->db->update("UPDATE `$table` SET $valuePlaceholders WHERE $pkPlaceholders", $values);
    }

    /**
     * Delete a row - reversing an INSERT query.
     *
     * @param string              $table      The table to delete from.
     * @param array<string|mixed> $values     The values to pick the primary-key values from.
     * @param string[]            $primaryKey The primary-key field names.
     * @return void
     */
    private function journalDeleteRow(string $table, array $values, array $primaryKey): void
    {
        $pkPlaceholders = $pkValues = [];
        foreach ($primaryKey as $field) {
            $pkPlaceholders[] = "`$field` = :$field";
            $pkValues[$field] = $values[$field];
        }

        $pkPlaceholders = implode(' AND ', $pkPlaceholders);

        $this->di->db->statement("DELETE FROM `$table` WHERE $pkPlaceholders", $pkValues);
    }





    /**
     * Generate the name of the journal table to use for a particular table.
     *
     * @param string $table The original table.
     * @return string
     */
    private function generateJournalTableName(string $table): string
    {
        return Settings::JOURNAL_TABLE_PREFIX . $this->generateTableNameHash($table);
    }

    /**
     * Generate the name of a trigger to use.
     *
     * @param string $table       The original table.
     * @param string $triggerType The trigger type - "INSERT" / "UPDATE" / "DELETE".
     * @return string
     */
    private function generateTriggerName(string $table, string $triggerType): string
    {
        $suffix = mb_strtolower("_" . mb_substr($triggerType, 0, 3));
        return Settings::JOURNAL_TRIGGER_PREFIX . $this->generateTableNameHash($table) . $suffix;
    }

    /**
     * Generate a hash for table names.
     *
     * @param string $table The table name to hash.
     * @return string
     */
    private function generateTableNameHash(string $table): string
    {
        return md5($table);
    }
}
