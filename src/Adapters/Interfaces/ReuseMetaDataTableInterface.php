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
     * @param string  $origDBName          The name of the database that this test-database is for.
     * @param string  $buildHash           The current build-hash.
     * @param string  $snapshotHash        The current snapshot-hash.
     * @param string  $scenarioHash        The current scenario-hash.
     * @param boolean $transactionReusable Whether this database can be reused because of a transaction or not.
     * @param boolean $journalReusable     Whether this database can be reused because of journaling or not.
     * @param boolean $willVerify          Whether this database will be verified or not.
     * @return void
     */
    public function writeReuseMetaData(
        string $origDBName,
        string $buildHash,
        string $snapshotHash,
        string $scenarioHash,
        bool $transactionReusable,
        bool $journalReusable,
        bool $willVerify
    ): void;

    /**
     * Remove the re-use meta-data table.
     *
     * @return void
     */
    public function removeReuseMetaTable(): void;

    /**
     * Check to see if the database can be reused.
     *
     * @param string $buildHash    The current build-hash.
     * @param string $scenarioHash The current scenario-hash.
     * @param string $projectName  The project-name.
     * @param string $database     The database being built.
     * @return boolean
     * @throws AdaptBuildException When the database is owned by another project.
     */
    public function dbIsCleanForReuse(
        string $buildHash,
        string $scenarioHash,
        string $projectName,
        string $database
    ): bool;
}
