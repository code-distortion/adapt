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
     * @param string  $origDBName   The name of the database that this test-database is for.
     * @param string  $buildHash    The current build-hash.
     * @param string  $snapshotHash The current snapshot-hash.
     * @param string  $scenarioHash The current scenario-hash.
     * @param boolean $reusable     Whether this database can be reused or not.
     * @return void
     */
    public function writeReuseMetaData(
        string $origDBName,
        string $buildHash,
        string $snapshotHash,
        string $scenarioHash,
        bool $reusable
    ): void {

        $this->removeReuseMetaTable();

        $this->di->db->statement(
            "CREATE TABLE `" . Settings::REUSE_TABLE . "` ("
            . "`project_name` varchar(255), "
            . "`reuse_table_version` varchar(16), "
            . "`orig_db_name` varchar(255) NOT NULL, "
            . "`build_hash` varchar(32) NOT NULL, "
            . "`snapshot_hash` varchar(32) NOT NULL, "
            . "`scenario_hash` varchar(32) NOT NULL, "
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
                . "`build_hash`, "
                . "`snapshot_hash`, "
                . "`scenario_hash`, "
                . "`reusable`, "
                . "`inside_transaction`, "
                . "`last_used`"
            . ") "
            . "VALUES ("
                . ":projectName, "
                . ":reuseTableVersion, "
                . ":origDBName, "
                . ":buildHash, "
                . ":snapshotHash, "
                . ":scenarioHash, "
                . ":reusable, "
                . ":insideTransaction, "
                . ":lastUsed"
            . ")",
            [
                'projectName' => $this->config->projectName,
                'reuseTableVersion' => Settings::REUSE_TABLE_VERSION,
                'origDBName' => $origDBName,
                'buildHash' => $buildHash,
                'snapshotHash' => $snapshotHash,
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
    public function removeReuseMetaTable(): void
    {
        $this->di->db->statement("DROP TABLE IF EXISTS `" . Settings::REUSE_TABLE . "`");
    }

    /**
     * Check to see if the database can be reused.
     *
     * @param string $buildHash    The current build-hash.
     * @param string $scenarioHash The current scenario-hash.
     * @param string $projectName  The project-name.
     * @param string $database     The database being built.
     * @return boolean
     * @throws AdaptBuildException When the database is owned by another project.
     */
    public function dbIsCleanForReuse(
        string $buildHash,
        string $scenarioHash,
        string $projectName,
        string $database
    ): bool {

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

        if ($reuseInfo->project_name != $projectName) {
            throw AdaptBuildException::databaseOwnedByAnotherProject($database, $reuseInfo->project_name);
        }

        if ($reuseInfo->reuse_table_version != Settings::REUSE_TABLE_VERSION) {
            return false;
        }

        if ($reuseInfo->build_hash != $buildHash) {
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
//                'The previous transaction for database "' . $database . '" '
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
     * @param string|null $origDBName The original database that this instance is for - will be ignored when null.
     * @param string      $buildHash  The current build-hash.
     * @return DatabaseMetaInfo[]
     */
    public function findDatabases(?string $origDBName, string $buildHash): array
    {
        $databaseMetaInfos = [];
        $pdo = $this->di->db->newPDO();
        foreach ($pdo->listDatabases() as $name) {

            $databaseMetaInfos[] = $this->buildDatabaseMetaInfo(
                $this->di->db->getConnection(),
                $name,
                $pdo->fetchReuseTableInfo("SELECT * FROM `" . $name . "`.`" . Settings::REUSE_TABLE . "` LIMIT 0, 1"),
                $buildHash
            );
        }
        return array_values(array_filter($databaseMetaInfos));
    }

    /**
     * Build DatabaseMetaInfo objects for a database.
     *
     * @param string        $connection The connection the database is within.
     * @param string        $name       The database's name.
     * @param stdClass|null $reuseInfo  The reuse info from the database.
     * @param string        $buildHash  The current build-hash.
     * @return DatabaseMetaInfo|null
     */
    private function buildDatabaseMetaInfo(
        string $connection,
        string $name,
        ?stdClass $reuseInfo,
        string $buildHash
    ): ?DatabaseMetaInfo {

        if (!$reuseInfo) {
            return null;
        }

        if ($reuseInfo->project_name != $this->config->projectName) {
            return null;
        }

        $isValid = (
            $reuseInfo->reuse_table_version == Settings::REUSE_TABLE_VERSION
            && $reuseInfo->build_hash == $buildHash
        );

        $databaseMetaInfo = new DatabaseMetaInfo(
            $connection,
            $name,
            DateTime::createFromFormat('Y-m-d H:i:s', $reuseInfo->last_used ?? null, new DateTimeZone('UTC')) ?: null,
            $isValid,
            fn() => $this->size($name),
            $this->config->staleGraceSeconds
        );
        $databaseMetaInfo->setDeleteCallback(fn() => $this->removeDatabase($databaseMetaInfo));
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
        if (!$pdo->dropDatabase("DROP DATABASE IF EXISTS `$databaseMetaInfo->name`")) {
            return true;
        }

        $this->di->log->debug(
            'Removed ' . (!$databaseMetaInfo->isValid ? 'old ' : '') . "database: \"$databaseMetaInfo->name\"",
            $logTimer
        );
        return true;
    }

    /**
     * Get the database's size in bytes.
     *
     * @param string $database The database to get the size of.
     * @return integer|null
     */
    private function size(string $database): ?int
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
