<?php

namespace CodeDistortion\Adapt\Adapters\LaravelMySQL;

use CodeDistortion\Adapt\Adapters\AbstractClasses\AbstractReuseMetaDataTable;
use CodeDistortion\Adapt\Adapters\Interfaces\ReuseMetaDataTableInterface;
use CodeDistortion\Adapt\Support\Settings;
use DateTime;
use DateTimeZone;
use stdClass;

/**
 * Database-adapter methods related to managing Laravel/MySQL reuse meta-data.
 */
class LaravelMySQLReuseMetaDataTable extends AbstractReuseMetaDataTable implements ReuseMetaDataTableInterface
{
    /**
     * Insert details to the database to help identify if it can be reused or not.
     *
     * @param string      $origDBName   The name of the database that this test-database is for.
     * @param string|null $buildHash    The current build-hash.
     * @param string|null $snapshotHash The current snapshot-hash.
     * @param string|null $scenarioHash The current scenario-hash.
     * @return void
     */
    public function createReuseMetaDataTable(
        string $origDBName,
        ?string $buildHash,
        ?string $snapshotHash,
        ?string $scenarioHash
    ): void {

        $this->removeReuseMetaTable();

        $table = Settings::REUSE_TABLE;

        $this->di->db->statement(
            "CREATE TABLE `$table` ("
            . "`project_name` varchar(255), "
            . "`reuse_table_version` varchar(16), "
            . "`orig_db_name` varchar(255) NOT NULL, "
            . "`build_hash` varchar(32) NULL, "
            . "`snapshot_hash` varchar(32) NULL, "
            . "`scenario_hash` varchar(32) NULL, "
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
                'transactionReusable' => null,
                'journalReusable' => null,
                'validationPassed' => null,
                'lastUsed' => $this->nowUtcString(),
            ]
        );
    }

    /**
     * Update the last-used field in the meta-table.
     *
     * @return void
     */
    public function updateMetaTableLastUsed(): void
    {
        $this->di->db->update("UPDATE `" . Settings::REUSE_TABLE . "` SET `last_used` = ?", [$this->nowUtcString()]);
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
     * Load the reuse details from the meta-data table.
     *
     * @return stdClass|null
     */
    protected function loadReuseInfo(): ?stdClass
    {
        $rows = $this->di->db->select("SELECT * FROM `" . Settings::REUSE_TABLE . "` LIMIT 0, 1");
        return $rows[0] ?? null;
    }

    /**
     * Render the current time in UTC as a string.
     *
     * @return string
     */
    private function nowUtcString(): string
    {
        return (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }
}
