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
     * Create the database if it doesn't exist, and wipe the database clean if it does.
     *
     * @return void
     */
    public function resetDB(): void;

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
