<?php

namespace CodeDistortion\Adapt\Adapters\LaravelPostgreSQL;

use CodeDistortion\Adapt\Adapters\Interfaces\NameInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Exceptions\AdaptBuildException;
use CodeDistortion\Adapt\Support\Settings;

/**
 * Database-adapter methods related to naming Laravel/PostgreSQL database things.
 */
class LaravelPostgreSQLName implements NameInterface
{
    use InjectTrait;



    /**
     * Build a scenario database name.
     *
     * @param boolean     $usingScenarios     Whether scenarios are being used or not.
     * @param string|null $dbNameChecksumPart The current database part, based on the snapshot checksum.
     * @return string
     * @throws AdaptBuildException When the database name is invalid.
     */
    public function generateDBName(bool $usingScenarios, ?string $dbNameChecksumPart): string
    {
        if (!$usingScenarios) {
            $this->validateDBName($this->configDTO->origDatabase);
            return $this->configDTO->origDatabase;
        }

        $dbNameChecksumPart = str_replace('-', '_', (string) $dbNameChecksumPart);
        $database = $this->configDTO->databasePrefix . $this->configDTO->origDatabase . '_' . $dbNameChecksumPart;
        $this->validateDBName($database);
        return $database;
    }

    /**
     * Generate the path (including filename) for the snapshot file.
     *
     * @param string $snapshotFilenameChecksumPart The current filename part, based on the snapshot checksum.
     * @return string
     */
    public function generateSnapshotPath(string $snapshotFilenameChecksumPart): string
    {
        $prefix = $this->configDTO->snapshotPrefix;
        $filename = $prefix . $this->configDTO->origDatabase . '.' . $snapshotFilenameChecksumPart . '.pgsql';
        $filename = str_replace('_', '-', $filename);
        return Settings::snapshotDir($this->configDTO->storageDir, $filename);
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
