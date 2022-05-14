<?php

namespace CodeDistortion\Adapt\Adapters\AbstractClasses;

use CodeDistortion\Adapt\Adapters\Interfaces\FindInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\DTO\DatabaseMetaInfo;
use CodeDistortion\Adapt\Support\Settings;
use DateTime;
use DateTimeZone;
use stdClass;

/**
 * Database-adapter methods related to managing reuse meta-data.
 */
abstract class AbstractFind implements FindInterface
{
    use InjectTrait;



    /**
     * Build DatabaseMetaInfo objects for a database.
     *
     * @param string        $connection The connection the database is within.
     * @param string        $name       The database's name.
     * @param stdClass|null $reuseInfo  The reuse info from the database.
     * @param string        $buildHash  The current build-hash.
     * @return DatabaseMetaInfo|null
     */
    protected function buildDatabaseMetaInfo(
        string $connection,
        string $name,
        ?stdClass $reuseInfo,
        string $buildHash
    ): ?DatabaseMetaInfo {

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
