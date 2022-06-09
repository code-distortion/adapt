<?php

namespace CodeDistortion\Adapt\Adapters\AbstractClasses;

use CodeDistortion\Adapt\Adapters\Interfaces\FindInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\DTO\DatabaseMetaInfo;
use CodeDistortion\Adapt\Exceptions\AdaptBuildException;
use CodeDistortion\Adapt\Support\Settings;
use DateTime;
use DateTimeZone;
use stdClass;
use Throwable;

/**
 * Database-adapter methods related to managing reuse meta-data.
 */
abstract class AbstractFind implements FindInterface
{
    use InjectTrait;



    /**
     * Look for databases and build DatabaseMetaInfo objects for them.
     *
     * Only pick databases that have "reuse" meta-info stored.
     *
     * @param string|null $buildHash The current build-hash.
     * @return DatabaseMetaInfo[]
     */
    public function findDatabases(?string $buildHash): array
    {
        $logTimer = $this->di->log->newTimer();

        $connDetails = "(connection: \"{$this->configDTO->connection}\", driver: \"{$this->configDTO->driver}\")";

        try {
            $databases = $this->listDatabases();
            $this->di->log->vvDebug("Retrieved database list $connDetails", $logTimer);
        } catch (Throwable $e) {
            $this->di->log->vvWarning("Could not retrieve database list $connDetails", $logTimer);
            return [];
        }

        $logTimer2 = $this->di->log->newTimer();

        $databaseMetaInfos = [];
        $attemptedCount = 0;
        foreach ($databases as $database) {

            if ($this->shouldIgnoreDatabase($database)) {
                continue;
            }

            $attemptedCount++;

            $logTimer3 = $this->di->log->newTimer();

            try {
                $databaseMetaInfo = $this->buildDatabaseMetaInfo($database, $buildHash);
                $databaseMetaInfos[] = $databaseMetaInfo;

                $database = $databaseMetaInfo ? $databaseMetaInfo->name : $database;
                $usable = $databaseMetaInfo
                    ? ($databaseMetaInfo->isValid
                        ? '(usable)'
                        : "(stale" . ($databaseMetaInfo->shouldPurgeNow() ? '' : ' - within grace period') . ")")
                    : '(not usable - ignoring)';
                $this->di->log->vvDebug("- Found database \"$database\" $usable", $logTimer3);

            } catch (Throwable $e) {
                $this->di->log->vvWarning("Could not read from database \"$database\" $connDetails", $logTimer3);
            }
        }

        if (!$attemptedCount) {
            $this->di->log->vvDebug("- No databases were found", $logTimer2);
        }

        return array_values(array_filter($databaseMetaInfos));
    }

    /**
     * Generate the list of existing databases.
     *
     * @return string[]
     */
    abstract protected function listDatabases(): array;

    /**
     * Check if this database should be ignored.
     *
     * @param string $database The database to check.
     * @return boolean
     */
    abstract protected function shouldIgnoreDatabase(string $database): bool;

    /**
     * Build DatabaseMetaInfo objects for a database.
     *
     * @param string $database  The database to use.
     * @param string $buildHash The current build-hash.
     * @return DatabaseMetaInfo|null
     */
    abstract protected function buildDatabaseMetaInfo(string $database, string $buildHash): ?DatabaseMetaInfo;

    /**
     * Build DatabaseMetaInfo objects for a database.
     *
     * @param string        $connection The connection the database is within.
     * @param string        $name       The database's name.
     * @param stdClass|null $reuseInfo  The reuse info from the database.
     * @param string|null   $buildHash  The current build-hash.
     * @return DatabaseMetaInfo|null
     */
    protected function buildDatabaseMetaInfoX(
        string $connection,
        string $name,
        ?stdClass $reuseInfo,
        ?string $buildHash
    ): ?DatabaseMetaInfo {

        if (!$reuseInfo) {
            return null;
        }

        if ($reuseInfo->project_name !== $this->configDTO->projectName) {
            return null;
        }

        $isValid = (
            $reuseInfo->reuse_table_version == Settings::REUSE_TABLE_VERSION
            && (($reuseInfo->build_hash === $buildHash) || (is_null($reuseInfo->build_hash)))
        );

        $databaseMetaInfo = new DatabaseMetaInfo(
            $this->configDTO->driver,
            $connection,
            $name,
            DateTime::createFromFormat('Y-m-d H:i:s', $reuseInfo->last_used ?? null, new DateTimeZone('UTC')) ?: null,
            $isValid,
            fn() => $this->size($name),
            $this->configDTO->staleGraceSeconds
        );
        $databaseMetaInfo->setDeleteCallback(fn() => $this->removeDatabase($databaseMetaInfo));
        return $databaseMetaInfo;
    }

    /**
     * Remove the given database.
     *
     * @param DatabaseMetaInfo $databaseMetaInfo The info object representing the database.
     * @return boolean
     * @throws AdaptBuildException When the database cannot be removed.
     */
    abstract protected function removeDatabase(DatabaseMetaInfo $databaseMetaInfo): bool;

    /**
     * Get the database's size in bytes.
     *
     * @param string $database The database to get the size of.
     * @return integer|null
     */
    abstract protected function size(string $database): ?int;
}
