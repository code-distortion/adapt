<?php

namespace CodeDistortion\Adapt\Adapters\LaravelPostgreSQL;

use CodeDistortion\Adapt\Adapters\AbstractClasses\AbstractReuseMetaDataTable;
use CodeDistortion\Adapt\Adapters\Interfaces\ReuseMetaDataTableInterface;
use CodeDistortion\Adapt\Support\Settings;
use stdClass;

/**
 * Database-adapter methods related to managing Laravel/PostgreSQL reuse meta-data.
 */
class LaravelPostgreSQLReuseMetaDataTable extends AbstractReuseMetaDataTable implements ReuseMetaDataTableInterface
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
        $origDBName,
        $buildHash,
        $snapshotHash,
        $scenarioHash
    ) {

        $this->removeReuseMetaTable();

        $table = Settings::REUSE_TABLE;

        $this->di->db->statement(
            "CREATE TABLE \"$table\" ("
            . "\"project_name\" character varying(255), "
            . "\"reuse_table_version\" character varying(16), "
            . "\"orig_db_name\" character varying(255) NOT NULL, "
            . "\"build_hash\" character varying(32) NULL, "
            . "\"snapshot_hash\" character varying(32) NULL, "
            . "\"scenario_hash\" character varying(32) NULL, "
            . "\"transaction_reusable\" boolean NULL, "
            . "\"journal_reusable\" boolean NULL, "
            . "\"validation_passed\" boolean NULL, "
            . "\"last_used\" timestamp"
            . ")"
        );

        $this->di->db->insert(
            "INSERT INTO \"$table\" ("
                . "\"project_name\", "
                . "\"reuse_table_version\", "
                . "\"orig_db_name\", "
                . "\"build_hash\", "
                . "\"snapshot_hash\", "
                . "\"scenario_hash\", "
                . "\"transaction_reusable\", "
                . "\"journal_reusable\", "
                . "\"validation_passed\", "
                . "\"last_used\""
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
    public function updateMetaTableLastUsed()
    {
        $this->di->db->update(
            "UPDATE \"" . Settings::REUSE_TABLE . "\" SET \"last_used\" = ?",
            [$this->nowUtcString()]
        );
    }

    /**
     * Remove the re-use meta-data table.
     *
     * @return void
     */
    public function removeReuseMetaTable()
    {
        $this->di->db->statement("DROP TABLE IF EXISTS \"" . Settings::REUSE_TABLE . "\"");
    }

    /**
     * Load the reuse details from the meta-data table.
     *
     * @return stdClass|null
     */
    protected function loadReuseInfo()
    {
        $rows = $this->di->db->select("SELECT * FROM \"" . Settings::REUSE_TABLE . "\" LIMIT 1 OFFSET 0");
        return $rows[0] ?? null;
    }
}
