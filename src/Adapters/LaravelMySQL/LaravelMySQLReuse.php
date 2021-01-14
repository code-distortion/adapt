<?php

namespace CodeDistortion\Adapt\Adapters\LaravelMySQL;

use CodeDistortion\Adapt\Adapters\Interfaces\ReuseInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectInclHasherTrait;
use CodeDistortion\Adapt\Exceptions\AdaptBuildException;
use CodeDistortion\Adapt\Support\Settings;
use stdClass;
use Throwable;
use PDOException;

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
     * @param string  $sourceFilesHash The current source-files-hash based on the database-building file content.
     * @param string  $scenarioHash    The current scenario-hash based on the pre-migration-imports, migrations and
     *                                 seeder-settings.
     * @param boolean $reusable        Whether this database can be reused or not.
     * @return void
     */
    public function writeReuseData(
        string $origDBName,
        string $sourceFilesHash,
        string $scenarioHash,
        bool $reusable
    ): void {

        $this->di->db->statement("DROP TABLE IF EXISTS `" . Settings::REUSE_TABLE . "`");
        $this->di->db->statement(
            "CREATE TABLE `" . Settings::REUSE_TABLE . "` ("
            . "`project_name` varchar(255), "
            . "`reuse_table_version` varchar(16), "
            . "`orig_db_name` varchar(255) NOT NULL, "
            . "`source_files_hash` varchar(255) NOT NULL, "
            . "`scenario_hash` varchar(255) NOT NULL, "
            . "`reusable` tinyint unsigned, "
            . "`inside_transaction` tinyint unsigned"
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
                . "`inside_transaction`"
            . ") "
            . "VALUES ("
                . ":projectName, "
                . ":reuseTableVersion, "
                . ":origDBName, "
                . ":sourceFilesHash, "
                . ":scenarioHash, "
                . ":reusable, "
                . ":insideTransaction"
            . ")",
            [
                'projectName' => $this->config->projectName,
                'reuseTableVersion' => Settings::REUSE_TABLE_VERSION,
                'origDBName' => $origDBName,
                'sourceFilesHash' => $sourceFilesHash,
                'scenarioHash' => $scenarioHash,
                'reusable' => (int) $reusable,
                'insideTransaction' => false,
            ]
        );
    }

    /**
     * Check to see if the database can be reused.
     *
     * @param string $sourceFilesHash The current source-files-hash based on the database-building file content.
     * @param string $scenarioHash    The scenario-hash based on the pre-migration-imports, migrations and
     *                                seeder-settings.
     * @return boolean
     * @throws AdaptBuildException When the database is owned by another project.
     */
    public function dbIsCleanForReuse(string $sourceFilesHash, string $scenarioHash): bool
    {
        try {
            $rows = $this->di->db->select("SELECT * FROM `" . Settings::REUSE_TABLE . "` LIMIT 0, 1");
            $reuseInfo = reset($rows);
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
            $this->di->log->warning(
                'The previous transaction for database "' . $this->config->database . '" '
                . 'was committed instead of being rolled-back'
            );
            return false;
        }

        return true;
    }

    /**
     * Look for databases, and check if they're valid or invalid (current or old).
     *
     * Only removes databases that have reuse-info stored,
     * and that were for the same original database that this instance is for.
     *
     * @param string|null $origDBName      The original database that this instance is for - will be ignored when null.
     * @param string      $sourceFilesHash The current files-hash based on the database-building file content.
     * @param boolean     $detectOld       Detect old databases.
     * @param boolean     $detectCurrent   Detect new databases.
     * @return string[]
     */
    public function findRelevantDatabases(
        ?string $origDBName,
        string $sourceFilesHash,
        bool $detectOld,
        bool $detectCurrent
    ): array {

        $relevantDBs = [];

        $pdo = $this->di->db->newPDO();
        foreach ($pdo->listDatabases() as $database) {

            $reuseInfo = $pdo->fetchReuseTableInfo(
                "SELECT * FROM `" . $database . "`.`" . Settings::REUSE_TABLE . "` LIMIT 0, 1"
            );

            if (
                $this->isDatabaseRelevant(
                    $reuseInfo,
                    $origDBName,
                    $sourceFilesHash,
                    $detectOld,
                    $detectCurrent
                )
            ) {
                $relevantDBs[] = $database;
            }
        }
        return $relevantDBs;
    }

    /**
     * Check to see if the given database is relevant.
     *
     * @param stdClass|null $reuseInfo       The reuse info from the database.
     * @param string|null   $origDBName      The original database that this instance is for - will be ignored when
     *                                       null.
     * @param string        $sourceFilesHash The current files-hash based on the database-building file content.
     * @param boolean       $detectOld       Detect old databases.
     * @param boolean       $detectCurrent   Detect new databases.
     * @return boolean
     */
    private function isDatabaseRelevant(
        ?stdClass $reuseInfo,
        ?string $origDBName,
        string $sourceFilesHash,
        bool $detectOld,
        bool $detectCurrent
    ): bool {

        if (!$reuseInfo) {
            return false;
        }

        if ($reuseInfo->project_name != $this->config->projectName) {
            return false;
        }

        if ((!is_null($origDBName)) && ($reuseInfo->orig_db_name != $origDBName)) {
            return false;
        }

        // pick this up as "relevant" because it's obselete and should be replaced
        if ($reuseInfo->reuse_table_version != Settings::REUSE_TABLE_VERSION) {
            return true;
        }

        $filesHashMatched = ($reuseInfo->source_files_hash == $sourceFilesHash);
        return ((($detectOld) && (!$filesHashMatched))
            || (($detectCurrent) && ($filesHashMatched)));
    }

    /**
     * Remove the given database.
     *
     * @param string  $database The database to remove.
     * @param boolean $isOld    If this database is "old" - affects the log message.
     * @return boolean
     */
    public function removeDatabase(string $database, bool $isOld = false): bool
    {
        $logTimer = $this->di->log->newTimer();

        $pdo = $this->di->db->newPDO();
        $success = $pdo->dropDatabase("DROP DATABASE IF EXISTS `$database`");

        $this->di->log->info('Removed ' . ($isOld ? 'old ' : '') . "database: \"$database\"", $logTimer);

        return $success;
    }

    /**
     * Get the database's size in bytes.
     *
     * @param string $database The database to get the size of.
     * @return integer|null
     */
    public function size(string $database): ?int
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
