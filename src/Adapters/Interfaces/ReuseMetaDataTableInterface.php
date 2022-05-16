<?php

namespace CodeDistortion\Adapt\Adapters\Interfaces;

use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Exceptions\AdaptBuildException;

/**
 * Database-adapter methods related to managing reuse meta-data.
 */
interface ReuseMetaDataTableInterface
{
    /**
     * Constructor.
     *
     * @param DIContainer $di        The dependency-injection container to use.
     * @param ConfigDTO   $configDTO A DTO containing the settings to use.
     */
    public function __construct(DIContainer $di, ConfigDTO $configDTO);



    /**
     * Insert details to the database to help identify if it can be reused or not.
     *
     * @param string      $origDBName   The name of the database that this test-database is for.
     * @param string|null $buildHash    The current build-hash.
     * @param string|null $snapshotHash The current snapshot-hash.
     * @param string|null $scenarioHash The current scenario-hash.
     * @return void
     */
    public function createReuseMetaDataTable(
        string $origDBName,
        ?string $buildHash,
        ?string $snapshotHash,
        ?string $scenarioHash
    ): void;

    /**
     * Update the last-used field in the meta-table.
     *
     * @return void
     */
    public function updateMetaTableLastUsed(): void;

    /**
     * Remove the re-use meta-data table.
     *
     * @return void
     */
    public function removeReuseMetaTable(): void;

    /**
     * Check to see if the database can be reused.
     *
     * @param string|null $buildHash    The current build-hash.
     * @param string|null $scenarioHash The current scenario-hash.
     * @param string|null $projectName  The project-name.
     * @param string      $database     The database being built.
     * @return boolean
     * @throws AdaptBuildException When the database is owned by another project.
     */
    public function dbIsCleanForReuse(
        ?string $buildHash,
        ?string $scenarioHash,
        ?string $projectName,
        string $database
    ): bool;
}
