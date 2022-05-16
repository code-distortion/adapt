<?php

namespace CodeDistortion\Adapt\Adapters\LaravelSQLite;

use CodeDistortion\Adapt\Adapters\AbstractClasses\AbstractFind;
use CodeDistortion\Adapt\Adapters\Interfaces\FindInterface;
use CodeDistortion\Adapt\DTO\DatabaseMetaInfo;
use CodeDistortion\Adapt\Support\Settings;

/**
 * Database-adapter methods related to finding Laravel/SQLite databases.
 */
class LaravelSQLiteFind extends AbstractFind implements FindInterface
{
    /**
     * Look for databases and build DatabaseMetaInfo objects for them.
     *
     * Only pick databases that have "reuse" meta-info stored.
     *
     * @param string|null $origDBName The original database that this instance is for - will be ignored when null.
     * @param string|null $buildHash  The current build-hash.
     * @return DatabaseMetaInfo[]
     */
    public function findDatabases(?string $origDBName, ?string $buildHash): array
    {
        if (!$this->di->filesystem->dirExists($this->configDTO->storageDir)) {
            return [];
        }

        $databaseMetaInfos = [];
        foreach ($this->di->filesystem->filesInDir($this->configDTO->storageDir) as $name) {

            $table = Settings::REUSE_TABLE;

            $pdo = $this->di->db->newPDO($name);
            $databaseMetaInfos[] = $this->buildDatabaseMetaInfo(
                $this->di->db->getConnection(),
                $name,
                $pdo->fetchReuseTableInfo("SELECT * FROM `$table` LIMIT 0, 1"),
                $buildHash
            );
        }
        return array_values(array_filter($databaseMetaInfos));
    }

    /**
     * Remove the given database.
     *
     * @param DatabaseMetaInfo $databaseMetaInfo The info object representing the database.
     * @return boolean
     */
    protected function removeDatabase(DatabaseMetaInfo $databaseMetaInfo): bool
    {
        if (!$this->di->filesystem->fileExists($databaseMetaInfo->name)) {
            return true;
        }

        $logTimer = $this->di->log->newTimer();

        if (!$this->di->filesystem->unlink($databaseMetaInfo->name)) {
            return false;
        }

        $this->di->log->debug(
            'Removed ' . (!$databaseMetaInfo->isValid ? 'old ' : '') . "database: \"$databaseMetaInfo->name\"",
            $logTimer
        );
        return true;
    }

    /**
     * Get the database's size in bytes.
     *
     * @param string $database The database to get the size of.
     * @return integer|null
     */
    protected function size(string $database): ?int
    {
        return $this->di->filesystem->size($database);
    }
}
