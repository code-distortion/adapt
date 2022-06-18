<?php

namespace CodeDistortion\Adapt\Adapters\Interfaces;

use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\DTO\VersionsDTO;

/**
 * Database-adapter methods related to getting a database's version.
 */
interface VersionInterface
{
    /**
     * Constructor.
     *
     * @param DIContainer $di        The dependency-injection container to use.
     * @param ConfigDTO   $configDTO A DTO containing the settings to use.
     */
    public function __construct(DIContainer $di, ConfigDTO $configDTO);



    /**
     * Resolve the database version and store it in the VersionsDTO.
     *
     * @param VersionsDTO $versionsDTO The VersionsDTO to update with the version.
     * @return void
     */
    public function resolveDatabaseVersion($versionsDTO);

    /**
     * Get the version of the database being used.
     *
     * @return string|null
     */
    public function getDatabaseVersion();
}
