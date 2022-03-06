<?php

namespace CodeDistortion\Adapt\Adapters\LaravelMySQL;

use CodeDistortion\Adapt\Adapters\Interfaces\NameInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Adapters\Traits\Laravel\LaravelHelperTrait;
use CodeDistortion\Adapt\Exceptions\AdaptLaravelMySQLAdapterException;

/**
 * Database-adapter methods related to naming Laravel/MySQL database things.
 */
class LaravelMySQLName implements NameInterface
{
    use InjectTrait;
    use LaravelHelperTrait;



    /**
     * Build a scenario database name.
     *
     * @param string $dbNameHash The current db-name-hash based on the database-building file content,
     *                           database-name-prefix, pre-migration-imports, migrations, seeder-settings, connection,
     *                           transactions and isBrowserTest.
     * @return string
     * @throws AdaptLaravelMySQLAdapterException When the database name is invalid.
     */
    public function generateScenarioDBName(string $dbNameHash): string
    {
        $dbNameHash = str_replace('-', '_', $dbNameHash);
        $database = $this->configDTO->databasePrefix . $this->configDTO->origDatabase . '_' . $dbNameHash;
        $this->validateDBName($database);
        return $database;
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
        $filename = $this->configDTO->snapshotPrefix . $this->configDTO->origDatabase . '.' . $snapshotHash . '.mysql';
        $filename = str_replace('_', '-', $filename);
        return $this->configDTO->storageDir . '/' . $filename;
    }

    /**
     * Check that the given database name is ok.
     *
     * @param string $database The database name to check.
     * @return void
     * @throws AdaptLaravelMySQLAdapterException When the database name is invalid.
     */
    private function validateDBName(string $database): void
    {
        if (mb_strlen($database) > 64) {
            throw AdaptLaravelMySQLAdapterException::yourDatabaseNameIsTooLongCouldYouChangeItThx($database);
        }
    }
}
