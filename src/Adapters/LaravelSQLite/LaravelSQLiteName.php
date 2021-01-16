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
     * Build a dynamic database name.
     *
     * @param string $dbNameHash The current db-name-hash based on the database-building file content,
     *                           database-name-prefix, pre-migration-imports, migrations, seeder-settings, connection
     *                           and transactions.
     * @return string
     */
    public function generateDynamicDBName(string $dbNameHash): string
    {
        if ($this->isMemoryDatabase()) {
            return $this->origDBName(); // ":memory:"
        }
        $dbNameHash = str_replace('_', '-', $dbNameHash);
        $filename = $this->pickBaseFilename($this->origDBName());
        $filename = $this->config->databasePrefix . $filename . '.' . $dbNameHash . '.sqlite';
        return $this->config->storageDir . '/' . $filename;
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
        $filename = $this->pickBaseFilename($this->origDBName());
        $filename = $this->config->snapshotPrefix . $filename . '.' . $snapshotHash . '.sqlite';
        $filename = str_replace('_', '-', $filename);
        return $this->config->storageDir . '/' . $filename;
    }
}
