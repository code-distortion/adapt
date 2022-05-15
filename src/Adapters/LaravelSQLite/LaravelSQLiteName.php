<?php

namespace CodeDistortion\Adapt\Adapters\LaravelSQLite;

use CodeDistortion\Adapt\Adapters\Interfaces\NameInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Adapters\Traits\Laravel\LaravelHelperTrait;
use CodeDistortion\Adapt\Adapters\Traits\SQLite\SQLiteHelperTrait;

/**
 * Database-adapter methods related to naming Laravel/SQLite database things.
 */
class LaravelSQLiteName implements NameInterface
{
    use InjectTrait;
    use LaravelHelperTrait;
    use SQLiteHelperTrait;



    /**
     * Build a scenario database name.
     *
     * @param string $dbNameHash The current db-name-hash based on the database-building file content,
     *                           database-name-prefix, pre-migration-imports, migrations, seeder-settings, connection,
     *                           transactions and isBrowserTest.
     * @return string
     */
    public function generateScenarioDBName(string $dbNameHash): string
    {
        $database = $this->configDTO->origDatabase;
        if ($this->isMemoryDatabase($database)) {
            return $database; // ":memory:"
        }

        $dbNameHash = str_replace('_', '-', $dbNameHash);
        $filename = $this->pickBaseFilename($database);
        $filename = $this->configDTO->databasePrefix . $filename . '.' . $dbNameHash . '.sqlite';
        return $this->configDTO->storageDir . '/' . $filename;
    }

    /**
     * Generate the path (including filename) for the snapshot file.
     *
     * @param string $snapshotHash The current snapshot-hash based on the database-building file content,
     *                             database-name-prefix, pre-migration-imports, migrations and seeder-settings.
     * @return string
     */
    public function generateSnapshotPath(string $snapshotHash): string
    {
        $filename = $this->pickBaseFilename($this->configDTO->origDatabase);
        $filename = $this->configDTO->snapshotPrefix . $filename . '.' . $snapshotHash . '.sqlite';
        $filename = str_replace('_', '-', $filename);
        return $this->configDTO->storageDir . '/' . $filename;
    }
}
