<?php

namespace CodeDistortion\Adapt\Adapters\Interfaces;

use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;

/**
 * Database-adapter methods related to building a database.
 */
interface BuildInterface
{
    /**
     * Constructor.
     *
     * @param DIContainer $di        The dependency-injection container to use.
     * @param ConfigDTO   $configDTO A DTO containing the settings to use.
     */
    public function __construct(DIContainer $di, ConfigDTO $configDTO);



    /**
     * Check if this database will disappear after use.
     *
     * @return boolean
     */
    public function databaseIsEphemeral(): bool;

    /**
     * Check if this database type supports database re-use.
     *
     * @return boolean
     */
    public function supportsReuse(): bool;

    /**
     * Check if this database type supports the use of scenario-databases.
     *
     * @return boolean
     */
    public function supportsScenarios(): bool;

    /**
     * Check if this database type can be built remotely.
     *
     * @return boolean
     */
    public function canBeBuiltRemotely(): bool;

    /**
     * Check if this database type can be used when browser testing.
     *
     * @return boolean
     */
    public function isCompatibleWithBrowserTests(): bool;

    /**
     * Remove the database (if it exists), and create it.
     *
     * @param boolean $exists Whether the database exists or not.
     * @return void
     */
    public function resetDB(bool $exists): void;

    /**
     * Migrate the database.
     *
     * @param string|null $migrationsPath The location of the migrations.
     * @return void
     */
    public function migrate(?string $migrationsPath): void;

    /**
     * Run the given seeders.
     *
     * @param string[] $seeders The seeders to run.
     * @return void
     */
    public function seed(array $seeders): void;
}
