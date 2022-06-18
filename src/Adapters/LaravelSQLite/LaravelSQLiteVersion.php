<?php

namespace CodeDistortion\Adapt\Adapters\LaravelSQLite;

use CodeDistortion\Adapt\Adapters\Interfaces\VersionInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\DTO\VersionsDTO;
use Throwable;

/**
 * Database-adapter methods related to getting a Laravel/MySQL database's version.
 */
class LaravelSQLiteVersion implements VersionInterface
{
    use InjectTrait;



    /**
     * Resolve the database version and store it in the VersionsDTO.
     *
     * @param VersionsDTO $versionsDTO The VersionsDTO to update with the version.
     * @return void
     */
    public function resolveDatabaseVersion(VersionsDTO $versionsDTO): void
    {
        $versionsDTO->sqliteVersion($this->getDatabaseVersion());
    }

    /**
     * Get the version of the database being used.
     *
     * @return string|null
     */
    public function getDatabaseVersion(): ?string
    {
//        try {
//            $rows = $this->di->db->select("SELECT sqlite_version() AS version");
//            return $rows[0]->version;
//        } catch (Throwable) {
//        }

        // when the database doesn't exist
        try {
            $pdo = $this->di->db->newPDO(':memory:');
            $rows = $pdo->select("SELECT sqlite_version() AS version");
            return $rows[0]['version'];
        } catch (Throwable) {
            return null;
        }
    }
}
