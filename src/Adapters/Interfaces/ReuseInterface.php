<?php

namespace CodeDistortion\Adapt\Adapters\Interfaces;

use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\DTO\DatabaseMetaInfo;
use CodeDistortion\Adapt\Exceptions\AdaptBuildException;
use CodeDistortion\Adapt\Support\Hasher;

/**
 * Database-adapter methods related to managing "reuse" data.
 */
interface ReuseInterface
{
    /**
     * Constructor.
     *
     * @param DIContainer $di     The dependency-injection container to use.
     * @param ConfigDTO   $config A DTO containing the settings to use.
     * @param Hasher      $hasher The object used to generate and check hashes.
     */
    public function __construct(DIContainer $di, ConfigDTO $config, Hasher $hasher);


    /**
     * Insert details to the database to help identify if it can be reused or not.
     *
     * @param string  $origDBName      The database that this test-database is for name.
     * @param string  $sourceFilesHash The current source-files-hash based on the database-building file content and
     *                                 database-name-prefix.
     * @param string  $scenarioHash    The current scenario-hash based on the pre-migration-imports, migrations and
     *                                 seeder-settings.
     * @param boolean $reusable        Whether this database can be reused or not.
     * @return void
     */
    public function writeReuseMetaData(
        string $origDBName,
        string $sourceFilesHash,
        string $scenarioHash,
        bool $reusable
    ): void;

    /**
     * Check to see if the database can be reused.
     *
     * @param string $sourceFilesHash The current source-files-hash based on the database-building file content and
     *                                database-name-prefix.
     * @param string $scenarioHash    The scenario-hash based on the pre-migration-imports, migrations and
     *                                seeder-settings.
     * @return boolean
     * @throws AdaptBuildException When the database is owned by another project.
     */
    public function dbIsCleanForReuse(string $sourceFilesHash, string $scenarioHash): bool;

    /**
     * Look for databases and build DatabaseMetaInfo objects for them.
     *
     * Only pick databases that have "reuse" meta-info stored.
     *
     * @param string|null $origDBName      The original database that this instance is for - will be ignored when null.
     * @param string      $sourceFilesHash The current files-hash based on the database-building file content.
     * @return DatabaseMetaInfo[]
     */
    public function findDatabases(?string $origDBName, string $sourceFilesHash): array;
}
