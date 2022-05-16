<?php

namespace CodeDistortion\Adapt\Adapters\LaravelMySQL;

use CodeDistortion\Adapt\Adapters\Interfaces\NameInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Exceptions\AdaptLaravelMySQLAdapterException;

/**
 * Database-adapter methods related to naming Laravel/MySQL database things.
 */
class LaravelMySQLName implements NameInterface
{
    use InjectTrait;



    /**
     * Build a scenario database name.
     *
     * @param boolean     $usingScenarios Whether scenarios are being used or not.
     * @param string|null $dbNameHashPart The current database part, based on the snapshot hash.
     * @return string
     * @throws AdaptLaravelMySQLAdapterException When the database name is invalid.
     */
    public function generateDBName(bool $usingScenarios, ?string $dbNameHashPart): string
    {
        if (!$usingScenarios) {
            return $this->configDTO->origDatabase;
        }

        $dbNameHashPart = str_replace('-', '_', (string) $dbNameHashPart);
        $database = $this->configDTO->databasePrefix . $this->configDTO->origDatabase . '_' . $dbNameHashPart;
        $this->validateDBName($database);
        return $database;
    }

    /**
     * Generate the path (including filename) for the snapshot file.
     *
     * @param string $snapshotFilenameHashPart The current filename part, based on the snapshot hash.
     * @return string
     */
    public function generateSnapshotPath(string $snapshotFilenameHashPart): string
    {
        $prefix = $this->configDTO->snapshotPrefix;
        $filename = $prefix . $this->configDTO->origDatabase . '.' . $snapshotFilenameHashPart . '.mysql';
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
