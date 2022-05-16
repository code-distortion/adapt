<?php

namespace CodeDistortion\Adapt\Adapters\LaravelSQLite;

use CodeDistortion\Adapt\Adapters\Interfaces\NameInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Adapters\Traits\SQLite\SQLiteHelperTrait;
use CodeDistortion\Adapt\Exceptions\AdaptBuildException;

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
     * @param boolean     $usingScenarios Whether scenarios are being used or not.
     * @param string|null $dbNameHashPart The current database part, based on the snapshot hash.
     * @return string
     * @throws AdaptBuildException When the database name is invalid.
     */
    public function generateDBName(bool $usingScenarios, ?string $dbNameHashPart): string
    {
        $database = $this->configDTO->origDatabase;

        if ($this->isMemoryDatabase()) {
            return $database; // ":memory:"
        }

        if ((mb_strpos($database, '/') !== false) || (mb_strpos($database, '\\') !== false)) {
            throw AdaptBuildException::SQLiteDatabaseNameContainsDirectoryParts($database);
        }

        if ($usingScenarios) {
            $dbNameHashPart = str_replace('_', '-', (string) $dbNameHashPart);
            $filename = $this->pickBaseFilename($database);
            $filename = $this->configDTO->databasePrefix . $filename . '.' . $dbNameHashPart . '.sqlite';
        } else {
            $filename = $database;
        }

        return $this->configDTO->storageDir . '/' . $filename;
    }

    /**
     * Generate the path (including filename) for the snapshot file.
     *
     * @param string $snapshotFilenameHashPart The current filename part, based on the snapshot hash.
     * @return string
     */
    public function generateSnapshotPath(string $snapshotFilenameHashPart): string
    {
        $filename = $this->pickBaseFilename($this->configDTO->origDatabase);
        $filename = $this->configDTO->snapshotPrefix . $filename . '.' . $snapshotFilenameHashPart . '.sqlite';
        $filename = str_replace('_', '-', $filename);
        return $this->configDTO->storageDir . '/' . $filename;
    }
}
