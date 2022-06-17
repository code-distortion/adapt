<?php

namespace CodeDistortion\Adapt\Adapters\LaravelSQLite;

use CodeDistortion\Adapt\Adapters\Interfaces\NameInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Adapters\Traits\SQLite\SQLiteHelperTrait;
use CodeDistortion\Adapt\Exceptions\AdaptBuildException;
use CodeDistortion\Adapt\Support\Settings;

/**
 * Database-adapter methods related to naming Laravel/SQLite database things.
 */
class LaravelSQLiteName implements NameInterface
{
    use InjectTrait;
    use SQLiteHelperTrait;



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
        $database = $this->configDTO->origDatabase;

        if ($this->isMemoryDatabase()) {
            return $database; // ":memory:"
        }

        if ((mb_strpos($database, '/') !== false) || (mb_strpos($database, '\\') !== false)) {
            throw AdaptBuildException::SQLiteDatabaseNameContainsDirectoryParts($database);
        }

        if ($usingScenarios) {
            $dbNameChecksumPart = str_replace('_', '-', (string) $dbNameChecksumPart);
            $filename = $this->pickBaseFilename($database);
            $filename = $this->configDTO->databasePrefix . $filename . '.' . $dbNameChecksumPart . '.sqlite';
        } else {
            $filename = $database;
        }

        return Settings::databaseDir($this->configDTO->storageDir, $filename);
    }

    /**
     * Generate the path (including filename) for the snapshot file.
     *
     * @param string $snapshotFilenameChecksumPart The current filename part, based on the snapshot checksum.
     * @return string
     */
    public function generateSnapshotPath(string $snapshotFilenameChecksumPart): string
    {
        $filename = $this->pickBaseFilename($this->configDTO->origDatabase);
        $filename = $this->configDTO->snapshotPrefix . $filename . '.' . $snapshotFilenameChecksumPart . '.sqlite';
        $filename = str_replace('_', '-', $filename);
        return Settings::snapshotDir($this->configDTO->storageDir, $filename);
    }
}
