<?php

namespace CodeDistortion\Adapt\Adapters\LaravelMySQL;

use CodeDistortion\Adapt\Adapters\AbstractClasses\AbstractReuseMetaDataTable;
use CodeDistortion\Adapt\Adapters\Interfaces\ReuseMetaDataTableInterface;
use CodeDistortion\Adapt\Support\Settings;
use stdClass;

/**
 * Database-adapter methods related to managing Laravel/MySQL reuse meta-data.
 */
class LaravelMySQLReuseMetaDataTable extends AbstractReuseMetaDataTable implements ReuseMetaDataTableInterface
{
    /**
     * Insert details to the database to help identify if it can be reused or not.
     *
     * @param string      $origDBName       The name of the database that this test-database is for.
     * @param string|null $buildChecksum    The current build-checksum.
     * @param string|null $snapshotChecksum The current snapshot-checksum.
     * @param string|null $scenarioChecksum The current scenario-checksum.
     * @return void
     */
    public function createReuseMetaDataTable(
        $origDBName,
        $buildChecksum,
        $snapshotChecksum,
        $scenarioChecksum
    ) {

        $this->removeReuseMetaTable();

        $this->di->db->statement(
            "CREATE TABLE `" . Settings::REUSE_TABLE . "` ("
            . "`project_name` varchar(255), "
            . "`reuse_table_version` varchar(16), "
            . "`orig_db_name` varchar(255) NOT NULL, "
            . "`build_checksum` varchar(32) NULL, "
            . "`snapshot_checksum` varchar(32) NULL, "
            . "`scenario_checksum` varchar(32) NULL, "
            . "`transaction_reusable` tinyint unsigned NULL, "
            . "`journal_reusable` tinyint unsigned NULL, "
            . "`validation_passed` tinyint unsigned NULL, "
            . "`last_used` timestamp"
            . ")"
        );

        $this->di->db->insert(
            "INSERT INTO `" . Settings::REUSE_TABLE . "` ("
                . "`project_name`, "
                . "`reuse_table_version`, "
                . "`orig_db_name`, "
                . "`build_checksum`, "
                . "`snapshot_checksum`, "
                . "`scenario_checksum`, "
                . "`transaction_reusable`, "
                . "`journal_reusable`, "
                . "`validation_passed`, "
                . "`last_used`"
            . ") "
            . "VALUES ("
                . ":projectName, "
                . ":reuseTableVersion, "
                . ":origDBName, "
                . ":buildChecksum, "
                . ":snapshotChecksum, "
                . ":scenarioChecksum, "
                . ":transactionReusable, "
                . ":journalReusable, "
                . ":validationPassed, "
                . ":lastUsed"
            . ")",
            [
                'projectName' => $this->configDTO->projectName,
                'reuseTableVersion' => Settings::REUSE_TABLE_VERSION,
                'origDBName' => $origDBName,
                'buildChecksum' => $buildChecksum,
                'snapshotChecksum' => $snapshotChecksum,
                'scenarioChecksum' => $scenarioChecksum,
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
    public function updateMetaTableLastUsed()
    {
        $this->di->db->update("UPDATE `" . Settings::REUSE_TABLE . "` SET `last_used` = ?", [$this->nowUtcString()]);
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
     * Load the reuse details from the meta-data table.
     *
     * @return stdClass|null
     */
    protected function loadReuseInfo()
    {
        $rows = $this->di->db->select("SELECT * FROM `" . Settings::REUSE_TABLE . "` LIMIT 0, 1");
        return $rows[0] ?? null;
    }
}
