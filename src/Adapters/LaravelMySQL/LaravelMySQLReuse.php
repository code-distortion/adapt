<?php

namespace CodeDistortion\Adapt\Adapters\LaravelMySQL;

use CodeDistortion\Adapt\Adapters\Interfaces\ReuseInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectInclHasherTrait;
use CodeDistortion\Adapt\DTO\DatabaseMetaInfo;
use CodeDistortion\Adapt\Exceptions\AdaptBuildException;
use CodeDistortion\Adapt\Support\Settings;
use DateTime;
use DateTimeZone;
use stdClass;
use Throwable;

/**
 * Database-adapter methods related to managing Laravel/MySQL "reuse" data.
 */
class LaravelMySQLReuse implements ReuseInterface
{
    use InjectInclHasherTrait;


    /**
     * Insert details to the database to help identify if it can be reused or not.
     *
     * @param string  $origDBName      The database that this test-database is for name.
     * @param string  $sourceFilesHash The current source-files-hash based on the database-building file content and
     *                                 database-name-prefix.
     * @param string  $scenarioHash    The current scenario-hash based on the pre-migration-imports, migrations and
     *                                 seeder-settings.
     * @param boolean $reusable        Whether this database can be reused or not.
     * @return void
     */
    public function writeReuseMetaData(
        $origDBName,
        $sourceFilesHash,
        $scenarioHash,
        $reusable
    ) {

        $this->removeReuseMetaTable();

        $this->di->db->statement(
            "CREATE TABLE `" . Settings::REUSE_TABLE . "` ("
            . "`project_name` varchar(255), "
            . "`reuse_table_version` varchar(16), "
            . "`orig_db_name` varchar(255) NOT NULL, "
            . "`source_files_hash` varchar(255) NOT NULL, "
            . "`scenario_hash` varchar(255) NOT NULL, "
            . "`reusable` tinyint unsigned, "
            . "`inside_transaction` tinyint unsigned, "
            . "`last_used` timestamp"
            . ")"
        );
        $this->di->db->insert(
            "INSERT INTO `" . Settings::REUSE_TABLE . "` ("
                . "`project_name`, "
                . "`reuse_table_version`, "
                . "`orig_db_name`, "
                . "`source_files_hash`, "
                . "`scenario_hash`, "
                . "`reusable`, "
                . "`inside_transaction`, "
                . "`last_used`"
            . ") "
            . "VALUES ("
                . ":projectName, "
                . ":reuseTableVersion, "
                . ":origDBName, "
                . ":sourceFilesHash, "
                . ":scenarioHash, "
                . ":reusable, "
                . ":insideTransaction, "
                . ":lastUsed"
            . ")",
            [
                'projectName' => $this->config->projectName,
                'reuseTableVersion' => Settings::REUSE_TABLE_VERSION,
                'origDBName' => $origDBName,
                'sourceFilesHash' => $sourceFilesHash,
                'scenarioHash' => $scenarioHash,
                'reusable' => (int) $reusable,
                'insideTransaction' => 0,
                'lastUsed' => (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * Remove the re-use meta-data table.
     *
     * @return void
     */
    public function removeReuseMetaTable()
    {
        $this->di->db->statement("DROP TABLE IF EXISTS `" . Settings::REUSE_TABLE . "`");
    }

    /**
     * Check to see if the database can be reused.
     *
     * @param string $sourceFilesHash The current source-files-hash based on the database-building file content and
     *                                database-name-prefix.
     * @param string $scenarioHash    The scenario-hash based on the pre-migration-imports, migrations and
     *                                seeder-settings.
     * @return boolean
     * @throws AdaptBuildException When the database is owned by another project.
     */
    public function dbIsCleanForReuse($sourceFilesHash, $scenarioHash): bool
    {
        try {
            $rows = $this->di->db->select("SELECT * FROM `" . Settings::REUSE_TABLE . "` LIMIT 0, 1");
            /** @var stdClass|null $reuseInfo */
            $reuseInfo = $rows[0] ?? null;
        } catch (Throwable $e) {
            return false;
        }

        if (!$reuseInfo) {
            return false;
        }

        if ($reuseInfo->project_name != $this->config->projectName) {
            throw AdaptBuildException::databaseOwnedByAnotherProject(
                (string) $this->config->database,
                $reuseInfo->project_name
            );
        }

        if ($reuseInfo->reuse_table_version != Settings::REUSE_TABLE_VERSION) {
            return false;
        }

        if ($reuseInfo->source_files_hash != $sourceFilesHash) {
            return false;
        }

        if ($reuseInfo->scenario_hash != $scenarioHash) {
            return false;
        }

        if (!$reuseInfo->reusable) {
            return false;
        }

        if ($reuseInfo->inside_transaction) {
//            $this->di->log->warning(
//                'The previous transaction for database "' . $this->config->database . '" '
//                . 'was committed instead of being rolled-back'
//            );
            return false;
        }

        return true;
    }

    /**
     * Check if the transaction was committed.
     *
     * @return boolean
     */
    public function wasTransactionCommitted(): bool
    {
        try {
            $rows = $this->di->db->select(
                "SELECT `inside_transaction` FROM `" . Settings::REUSE_TABLE . "` LIMIT 0, 1"
            );
            /** @var stdClass|null $reuseInfo */
            $reuseInfo = $rows[0] ?? null;
            return (bool) ($reuseInfo->inside_transaction ?? false);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Look for databases and build DatabaseMetaInfo objects for them.
     *
     * Only pick databases that have "reuse" meta-info stored.
     *
     * @param string|null $origDBName      The original database that this instance is for - will be ignored when null.
     * @param string      $sourceFilesHash The current files-hash based on the database-building file content.
     * @return DatabaseMetaInfo[]
     */
    public function findDatabases($origDBName, $sourceFilesHash): array
    {
        $databaseMetaInfos = [];
        $pdo = $this->di->db->newPDO();
        foreach ($pdo->listDatabases() as $name) {

            $databaseMetaInfos[] = $this->buildDatabaseMetaInfo(
                $this->di->db->getConnection(),
                $name,
                $pdo->fetchReuseTableInfo("SELECT * FROM `" . $name . "`.`" . Settings::REUSE_TABLE . "` LIMIT 0, 1"),
                $sourceFilesHash
            );
        }
        return array_values(array_filter($databaseMetaInfos));
    }

    /**
     * Build DatabaseMetaInfo objects for a database.
     *
     * @param string        $connection      The connection the database is within.
     * @param string        $name            The database's name.
     * @param stdClass|null $reuseInfo       The reuse info from the database.
     * @param string        $sourceFilesHash The current files-hash based on the database-building file content.
     * @return DatabaseMetaInfo|null
     */
    private function buildDatabaseMetaInfo(
        string $connection,
        string $name,
        $reuseInfo,
        string $sourceFilesHash
    ) {

        if (!$reuseInfo) {
            return null;
        }

        if ($reuseInfo->project_name != $this->config->projectName) {
            return null;
        }

        $isValid = (
            $reuseInfo->reuse_table_version == Settings::REUSE_TABLE_VERSION
            && $reuseInfo->source_files_hash == $sourceFilesHash
        );

        $databaseMetaInfo = new DatabaseMetaInfo(
            $connection,
            $name,
            DateTime::createFromFormat('Y-m-d H:i:s', $reuseInfo->last_used ?? null, new DateTimeZone('UTC')) ?: null,
            $isValid,
            function () use ($name) {
                return $this->size($name);
            },
            $this->config->invalidationGraceSeconds
        );
        $databaseMetaInfo->setDeleteCallback(function () use ($databaseMetaInfo) {
            return $this->removeDatabase($databaseMetaInfo);
        });
        return $databaseMetaInfo;
    }

    /**
     * Remove the given database.
     *
     * @param DatabaseMetaInfo $databaseMetaInfo The info object representing the database.
     * @return boolean
     */
    private function removeDatabase(DatabaseMetaInfo $databaseMetaInfo): bool
    {
        $logTimer = $this->di->log->newTimer();

        $pdo = $this->di->db->newPDO(null, $databaseMetaInfo->connection);
        if ($pdo->dropDatabase("DROP DATABASE IF EXISTS `$databaseMetaInfo->name`")) {
            $this->di->log->info(
                'Removed ' . (!$databaseMetaInfo->isValid ? 'old ' : '') . "database: \"$databaseMetaInfo->name\"",
                $logTimer
            );
            return true;
        }
        return false;
    }

    /**
     * Get the database's size in bytes.
     *
     * @param string $database The database to get the size of.
     * @return integer|null
     */
    private function size(string $database)
    {
        $pdo = $this->di->db->newPDO();
        $size = $pdo->size(
            "SELECT SUM(DATA_LENGTH + INDEX_LENGTH) AS size "
            . "FROM INFORMATION_SCHEMA.TABLES "
            . "WHERE TABLE_SCHEMA = '$database'"
        );
        return (is_integer($size) ? $size :  null);
    }
}
