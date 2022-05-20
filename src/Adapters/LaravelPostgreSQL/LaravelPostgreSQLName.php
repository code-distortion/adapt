<?php

namespace CodeDistortion\Adapt\Adapters\LaravelPostgreSQL;

use CodeDistortion\Adapt\Adapters\Interfaces\NameInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Exceptions\AdaptBuildException;

/**
 * Database-adapter methods related to naming Laravel/PostgreSQL database things.
 */
class LaravelPostgreSQLName implements NameInterface
{
    use InjectTrait;



    /**
     * Build a scenario database name.
     *
     * @param boolean     $usingScenarios Whether scenarios are being used or not.
     * @param string|null $dbNameHashPart The current database part, based on the snapshot hash.
     * @return string
     * @throws AdaptBuildException When the database name is invalid.
     */
    public function generateDBName(bool $usingScenarios, ?string $dbNameHashPart): string
    {
        if (!$usingScenarios) {
            $this->validateDBName($this->configDTO->origDatabase);
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
        $filename = $prefix . $this->configDTO->origDatabase . '.' . $snapshotFilenameHashPart . '.pgsql';
        $filename = str_replace('_', '-', $filename);
        return $this->configDTO->storageDir . '/' . $filename;
    }

    /**
     * Check that the given database name is ok.
     *
     * @param string $database The database name to check.
     * @return void
     * @throws AdaptBuildException When the database name is invalid.
     */
    private function validateDBName(string $database): void
    {
        if (mb_strlen($database) > 63) {
            throw AdaptBuildException::yourPostgreSQLDatabaseNameIsTooLongCouldYouChangeItThx($database);
        }
    }
}
