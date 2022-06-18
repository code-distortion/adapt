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
     * @param string      $origDBName       The name of the database that this test-database is for.
     * @param string|null $buildChecksum    The current build-checksum.
     * @param string|null $snapshotChecksum The current snapshot-checksum.
     * @param string|null $scenarioChecksum The current scenario-checksum.
     * @return void
     */
    public function createReuseMetaDataTable(
        $origDBName,
        $buildChecksum,
        $snapshotChecksum,
        $scenarioChecksum
    );

    /**
     * Update the scenario-checksum and last-used fields in the meta-table.
     *
     * @param string|null $scenarioChecksum The current scenario-checksum.
     * @return void
     */
    public function updateMetaTable($scenarioChecksum);

    /**
     * Remove the re-use meta-data table.
     *
     * @return void
     */
    public function removeReuseMetaTable();

    /**
     * Check to see if the database can be reused.
     *
     * @param string|null $buildChecksum    The current build-checksum.
     * @param string|null $scenarioChecksum The current scenario-checksum.
     * @param string|null $projectName      The project-name.
     * @param string      $database         The database being built.
     * @return boolean
     * @throws AdaptBuildException When the database is owned by another project.
     */
    public function dbIsCleanForReuse(
        $buildChecksum,
        $scenarioChecksum,
        $projectName,
        $database
    ): bool;

    /**
     * Get the reason why the database couldn't be reused.
     *
     * @return string|null
     */
    public function getCantReuseReason();
}
