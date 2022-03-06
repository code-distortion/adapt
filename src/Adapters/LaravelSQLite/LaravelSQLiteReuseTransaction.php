<?php

namespace CodeDistortion\Adapt\Adapters\LaravelSQLite;

use CodeDistortion\Adapt\Adapters\Interfaces\ReuseTransactionInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectInclHasherTrait;
use CodeDistortion\Adapt\Adapters\Traits\Laravel\LaravelTransactionsTrait;
use CodeDistortion\Adapt\Adapters\Traits\SQLite\SQLiteHelperTrait;
use CodeDistortion\Adapt\Exceptions\AdaptBuildException;
use CodeDistortion\Adapt\Support\Settings;
use DateTime;
use DateTimeZone;
use stdClass;
use Throwable;

/**
 * Database-adapter methods related to managing Laravel/SQLite reuse through transactions.
 */
class LaravelSQLiteReuseTransaction implements ReuseTransactionInterface
{
    use InjectInclHasherTrait;
    use LaravelTransactionsTrait;
    use SQLiteHelperTrait;



    /**
     * Insert details to the database to help identify if it can be reused or not.
     *
     * @param string  $origDBName          The name of the database that this test-database is for.
     * @param string  $buildHash           The current build-hash.
     * @param string  $snapshotHash        The current snapshot-hash.
     * @param string  $scenarioHash        The current scenario-hash.
     * @param boolean $transactionReusable Whether this database can be reused because of a transaction or not.
     * @param boolean $journalReusable     Whether this database can be reused because of journaling or not.
     * @param boolean $willVerify          Whether this database will be verified or not.
     * @return void
     */
    public function writeReuseMetaData(
        string $origDBName,
        string $buildHash,
        string $snapshotHash,
        string $scenarioHash,
        bool $transactionReusable,
        bool $journalReusable,
        bool $willVerify
    ): void {

        $this->removeReuseMetaTable();

        $table = Settings::REUSE_TABLE;

        $this->di->db->statement(
            "CREATE TABLE `$table` ("
            . "`project_name` varchar(255), "
            . "`reuse_table_version` varchar(16), "
            . "`orig_db_name` varchar(255) NOT NULL, "
            . "`build_hash` varchar(32) NOT NULL, "
            . "`snapshot_hash` varchar(32) NOT NULL, "
            . "`scenario_hash` varchar(32) NOT NULL, "
            . "`transaction_reusable` tinyint unsigned NULL, "
            . "`journal_reusable` tinyint unsigned NULL, "
            . "`validation_passed` tinyint unsigned NULL, "
            . "`last_used` timestamp"
            . ")"
        );

        $this->di->db->insert(
            "INSERT INTO `$table` ("
                . "`project_name`, "
                . "`reuse_table_version`, "
                . "`orig_db_name`, "
                . "`build_hash`, "
                . "`snapshot_hash`, "
                . "`scenario_hash`, "
                . "`transaction_reusable`, "
                . "`journal_reusable`, "
                . "`validation_passed`, "
                . "`last_used`"
            . ") "
            . "VALUES ("
                . ":projectName, "
                . ":reuseTableVersion, "
                . ":origDBName, "
                . ":buildHash, "
                . ":snapshotHash, "
                . ":scenarioHash, "
                . ":transactionReusable, "
                . ":journalReusable, "
                . ":validationPassed, "
                . ":lastUsed"
            . ")",
            [
                'projectName' => $this->configDTO->projectName,
                'reuseTableVersion' => Settings::REUSE_TABLE_VERSION,
                'origDBName' => $origDBName,
                'buildHash' => $buildHash,
                'snapshotHash' => $snapshotHash,
                'scenarioHash' => $scenarioHash,
                'transactionReusable' => $transactionReusable ? 1 : null,
                'journalReusable' => $journalReusable ? 1 : null,
                'validationPassed' => $willVerify ? 1 : null,
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

        if ($reuseInfo->transaction_reusable === 0) {
//            $this->di->log->warning(
//                'The previous transaction for database "' . $database . '" '
//                . 'was committed instead of being rolled-back'
//            );
            return false;
        }

        if ($reuseInfo->journal_reusable === 0) {
            return false;
        }

        if ($reuseInfo->validation_passed === 0) {
            return false;
        }

        if (!$reuseInfo->transaction_reusable && !$reuseInfo->journal_reusable) {
            return false;
        }

        return true;
    }



    /**
     * Determine if a transaction can be used on this database (for database re-use).
     *
     * @return boolean
     */
    public function isTransactionable(): bool
    {
        // the database connection is closed between tests,
        // which causes :memory: databases to disappear,
        // so transactions can't be used on them between tests
        return !$this->isMemoryDatabase();
    }

    /**
     * Start the transaction that the test will be encapsulated in.
     *
     * @return void
     */
    public function applyTransaction(): void
    {
        $this->di->db->update("UPDATE`" . Settings::REUSE_TABLE . "` SET `transaction_reusable` = 1");
        $this->laravelApplyTransaction();
        $this->di->db->update("UPDATE`" . Settings::REUSE_TABLE . "` SET `transaction_reusable` = 0");
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
                "SELECT `transaction_reusable` FROM `" . Settings::REUSE_TABLE . "` LIMIT 0, 1"
            );

            /** @var stdClass|null $reuseInfo */
            $reuseInfo = $rows[0] ?? null;

            return ($reuseInfo->transaction_reusable ?? null) === 0;

        } catch (Throwable $e) {
            return false;
        }
    }
}
