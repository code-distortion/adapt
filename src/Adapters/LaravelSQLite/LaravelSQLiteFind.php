<?php

namespace CodeDistortion\Adapt\Adapters\LaravelSQLite;

use CodeDistortion\Adapt\Adapters\Interfaces\FindInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\DTO\DatabaseMetaInfo;
use CodeDistortion\Adapt\Support\Settings;
use DateTime;
use DateTimeZone;
use stdClass;

/**
 * Database-adapter methods related to finding Laravel/SQLite databases.
 */
class LaravelSQLiteFind implements FindInterface
{
    use InjectTrait;



    /**
     * Look for databases and build DatabaseMetaInfo objects for them.
     *
     * Only pick databases that have "reuse" meta-info stored.
     *
     * @param string|null $origDBName The original database that this instance is for - will be ignored when null.
     * @param string      $buildHash  The current build-hash.
     * @return DatabaseMetaInfo[]
     */
    public function findDatabases($origDBName, $buildHash): array
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
     * Build DatabaseMetaInfo objects for a database.
     *
     * @param string        $connection The connection the database is within.
     * @param string        $name       The database's name.
     * @param stdClass|null $reuseInfo  The reuse info from the database.
     * @param string        $buildHash  The current build-hash.
     * @return DatabaseMetaInfo|null
     */
    private function buildDatabaseMetaInfo(
        string $connection,
        string $name,
        $reuseInfo,
        string $buildHash
    ) {

        if (!$reuseInfo) {
            return null;
        }

        if ($reuseInfo->project_name != $this->configDTO->projectName) {
            return null;
        }

        $isValid = (
            $reuseInfo->reuse_table_version == Settings::REUSE_TABLE_VERSION
            && $reuseInfo->build_hash == $buildHash
        );

        $databaseMetaInfo = new DatabaseMetaInfo(
            $connection,
            $name,
            DateTime::createFromFormat('Y-m-d H:i:s', $reuseInfo->last_used ?? null, new DateTimeZone('UTC')) ?: null,
            $isValid,
            function () use ($name) {
                return $this->size($name);
            },
            $this->configDTO->staleGraceSeconds
        );
        $databaseMetaInfo->setDeleteCallback(function () use ($databaseMetaInfo) {
            return $this->removeDatabase($databaseMetaInfo);
        });
        return $databaseMetaInfo;
    }

    /**
     * Remove the given database.
     *
     * @param DatabaseMetaInfo $databaseMetaInfo The info object representing the database.
     * @return boolean
     */
    private function removeDatabase(DatabaseMetaInfo $databaseMetaInfo): bool
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
    private function size(string $database)
    {
        return $this->di->filesystem->size($database);
    }
}
