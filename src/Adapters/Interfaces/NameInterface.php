<?php

namespace CodeDistortion\Adapt\Adapters\Interfaces;

use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Exceptions\AdaptLaravelMySQLAdapterException;

/**
 * Database-adapter methods related to naming database things.
 */
interface NameInterface
{
    /**
     * Constructor.
     *
     * @param DIContainer $di     The dependency-injection container to use.
     * @param ConfigDTO   $config A DTO containing the settings to use.
     */
    public function __construct(DIContainer $di, ConfigDTO $config);


    /**
     * Build a scenario database name.
     *
     * @param string $dbNameHash The current db-name-hash based on the database-building file content,
     *                           database-name-prefix, pre-migration-imports, migrations, seeder-settings, connection,
     *                           transactions and isBrowserTest.
     * @return string
     * @throws AdaptLaravelMySQLAdapterException Thrown when the database name is invalid.
     */
    public function generateScenarioDBName($dbNameHash): string;

    /**
     * Generate the path (including filename) for the snapshot file.
     *
     * @param string $snapshotHash The current snapshot-hash based on the database-building file content,
     *                             database-name-prefix, pre-migration-imports, migrations and seeder-settings.
     * @return string
     */
    public function generateSnapshotPath($snapshotHash): string;
}
