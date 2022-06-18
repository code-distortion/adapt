<?php

namespace CodeDistortion\Adapt\Adapters\LaravelPostgreSQL;

use CodeDistortion\Adapt\Adapters\Interfaces\VersionInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\DTO\VersionsDTO;
use Throwable;

/**
 * Database-adapter methods related to getting a Laravel/MySQL database's version.
 */
class LaravelPostgreSQLVersion implements VersionInterface
{
    use InjectTrait;



    /**
     * Resolve the database version and store it in the VersionsDTO.
     *
     * @param VersionsDTO $versionsDTO The VersionsDTO to update with the version.
     * @return void
     */
    public function resolveDatabaseVersion($versionsDTO)
    {
        $versionsDTO->postgresqlVersion($this->getDatabaseVersion());
    }

    /**
     * Get the version of the database being used.
     *
     * @return string|null
     */
    public function getDatabaseVersion()
    {
//        try {
//            $rows = $this->di->db->select("SELECT version()");
//            return $rows[0]->version;
//        } catch (Throwable) {
//        }

        // when the database doesn't exist
        try {
            $pdo = $this->di->db->newPDO();
            $rows = $pdo->select("SELECT version()");
            return $rows[0]['version'];
        } catch (Throwable $exception) {
            return null;
        }
    }
}
