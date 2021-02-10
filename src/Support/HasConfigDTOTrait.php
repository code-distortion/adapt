<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\Adapt\DTO\ConfigDTO;

/**
 * Provides methods to update the stored configDTO.
 */
trait HasConfigDTOTrait
{
    /** @var ConfigDTO A DTO containing the settings to use. */
    private $config;

    /**
     * Specify the database connection to prepare.
     *
     * @param string $connection The database connection to prepare.
     * @return static
     */
    public function connection(string $connection)
    {
        $this->config->connection($connection);
        return $this;
    }

    /**
     * Specify the database-modifier to use.
     *
     * @param string $databaseModifier The modifier to use.
     * @return static
     */
    public function databaseModifier(string $databaseModifier)
    {
        $this->config->databaseModifier($databaseModifier);
        return $this;
    }

    /**
     * Specify that no database-modifier is to be used.
     *
     * @return static
     */
    public function noDatabaseModifier()
    {
        $this->config->databaseModifier('');
        return $this;
    }

    /**
     * Specify the database dump files to import before migrations run.
     *
     * @param string[]|string[][] $preMigrationImports The database dump files to import, one per database type.
     * @return static
     */
    public function preMigrationImports(array $preMigrationImports = [])
    {
        $this->config->preMigrationImports($preMigrationImports);
        return $this;
    }

    /**
     * Specify that no database dump files will be imported before migrations run.
     *
     * @return static
     */
    public function noPreMigrationImports()
    {
        $this->config->preMigrationImports([]);
        return $this;
    }

    /**
     * Turn migrations on (or off), or specify the location of the migrations to run.
     *
     * @param boolean|string $migrations Should the migrations be run? / the path of the migrations to run.
     * @return static
     */
    public function migrations($migrations = true)
    {
        $this->config->migrations($migrations);
        return $this;
    }

    /**
     * Turn migrations off.
     *
     * @return static
     */
    public function noMigrations()
    {
        $this->config->migrations(false);
        return $this;
    }

    /**
     * Specify the seeders to run.
     *
     * @param string[] $seeders The seeders to run after migrating.
     * @return static
     */
    public function seeders(array $seeders)
    {
        $this->config->seeders($seeders);
        return $this;
    }

    /**
     * Turn seeders off.
     *
     * @return static
     */
    public function noSeeders()
    {
        $this->config->seeders([]);
        return $this;
    }

    /**
     * Set the types of cache to use.
     *
     * @param boolean $reuseTestDBs    Reuse databases when possible (instead of rebuilding them)?.
     * @param boolean $scenarioTestDBs Create databases as needed for the database-scenario?.
     * @return static
     */
    public function cacheTools(bool $reuseTestDBs, bool $scenarioTestDBs)
    {
        $this->config->cacheTools($reuseTestDBs, $scenarioTestDBs);
        return $this;
    }

    /**
     * Turn the reuse-test-dbs setting on (or off).
     *
     * @param boolean $reuseTestDBs Reuse existing databases?.
     * @return static
     */
    public function reuseTestDBs(bool $reuseTestDBs = true)
    {
        $this->config->reuseTestDBs($reuseTestDBs);
        return $this;
    }

    /**
     * Turn the reuse-test-dbs setting off.
     *
     * @return static
     */
    public function noReuseTestDBs()
    {
        $this->config->reuseTestDBs(false);
        return $this;
    }

    /**
     * Turn the scenario-test-dbs setting on (or off).
     *
     * @param boolean $scenarioTestDBs Create databases as needed for the database-scenario?.
     * @return static
     */
    public function scenarioTestDBs(bool $scenarioTestDBs = true)
    {
        $this->config->scenarioTestDBs($scenarioTestDBs);
        return $this;
    }

    /**
     * Turn the scenario-test-dbs setting off.
     *
     * @return static
     */
    public function noScenarioTestDBs()
    {
        $this->config->scenarioTestDBs(false);
        return $this;
    }

    /**
     * Turn the snapshots setting on.
     *
     * @param boolean $takeSnapshotAfterMigrations Take a snapshot of the database after migrations have been run?.
     * @param boolean $takeSnapshotAfterSeeders    Take a snapshot of the database after seeders have been run?.
     * @return static
     */
    public function snapshots(bool $takeSnapshotAfterMigrations = false, bool $takeSnapshotAfterSeeders = true)
    {
        $this->config->snapshots(true, $takeSnapshotAfterMigrations, $takeSnapshotAfterSeeders);
        return $this;
    }

    /**
     * Turn the snapshots setting off.
     *
     * @return static
     */
    public function noSnapshots()
    {
        $this->config->snapshots(false, false, false);
        return $this;
    }

    /**
     * Turn the is-browser-test setting on (or off).
     *
     * @param boolean $isBrowserTest Is this test a browser-test?.
     * @return static
     */
    public function isBrowserTest(bool $isBrowserTest = true)
    {
        $this->config->isBrowserTest($isBrowserTest);
        return $this;
    }

    /**
     * Turn the is-browser-test setting off.
     *
     * @return static
     */
    public function isNotBrowserTest()
    {
        $this->config->isBrowserTest(false);
        return $this;
    }

    /**
     * Retrieve the connection being used.
     *
     * @return string
     */
    public function getConnection(): string
    {
        return $this->config->connection;
    }
}
