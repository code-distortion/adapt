<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\Adapt\DTO\ConfigDTO;

/**
 * Provides methods to update the stored configDTO.
 */
trait HasConfigDTOTrait
{
    /**
     * A DTO containing the settings to use.
     *
     * @var ConfigDTO
     */
    private ConfigDTO $config;

    /**
     * Specify the database connection to prepare.
     *
     * @param string $connection The database connection to prepare.
     * @return static
     */
    public function connection(string $connection): self
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
    public function databaseModifier(string $databaseModifier): self
    {
        $this->config->databaseModifier($databaseModifier);
        return $this;
    }

    /**
     * Specify that no database-modifier is to be used.
     *
     * @return static
     */
    public function noDatabaseModifier(): self
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
    public function preMigrationImports(array $preMigrationImports = []): self
    {
        $this->config->preMigrationImports($preMigrationImports);
        return $this;
    }

    /**
     * Specify that no database dump files will be imported before migrations run.
     *
     * @return static
     */
    public function noPreMigrationImports(): self
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
    public function migrations($migrations = true): self
    {
        $this->config->migrations($migrations);
        return $this;
    }

    /**
     * Turn migrations off.
     *
     * @return static
     */
    public function noMigrations(): self
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
    public function seeders(array $seeders): self
    {
        $this->config->seeders($seeders);
        return $this;
    }

    /**
     * Turn seeders off.
     *
     * @return static
     */
    public function noSeeders(): self
    {
        $this->config->seeders([]);
        return $this;
    }

    /**
     * Set the types of cache to use.
     *
     * @param boolean $reuseTestDBs   Reuse databases when possible (instead of rebuilding them)?.
     * @param boolean $dynamicTestDBs Create databases as needed for the database-scenario?.
     * @param boolean $transactions   Should tests be encapsulated within transactions?.
     * @return static
     */
    public function cacheTools(bool $reuseTestDBs, bool $dynamicTestDBs, bool $transactions): self
    {
        $this->config->cacheTools($reuseTestDBs, $dynamicTestDBs, $transactions);
        return $this;
    }

    /**
     * Turn the reuse-test-dbs setting on (or off).
     *
     * @param boolean $reuseTestDBs Reuse existing databases?.
     * @return static
     */
    public function reuseTestDBs(bool $reuseTestDBs = true): self
    {
        $this->config->reuseTestDBs($reuseTestDBs);
        return $this;
    }

    /**
     * Turn the reuse-test-dbs setting off.
     *
     * @return static
     */
    public function noReuseTestDBs(): self
    {
        $this->config->reuseTestDBs(false);
        return $this;
    }

    /**
     * Turn the dynamic-test-dbs setting on (or off).
     *
     * @param boolean $dynamicTestDBs Create databases as needed for the database-scenario?.
     * @return static
     */
    public function dynamicTestDBs(bool $dynamicTestDBs = true): self
    {
        $this->config->dynamicTestDBs($dynamicTestDBs);
        return $this;
    }

    /**
     * Turn the dynamic-test-dbs setting off.
     *
     * @return static
     */
    public function noDynamicTestDBs(): self
    {
        $this->config->dynamicTestDBs(false);
        return $this;
    }

    /**
     * Turn transactions on or off.
     *
     * @param boolean $transactions Should tests be encapsulated within transactions?.
     * @return static
     */
    public function transactions(bool $transactions = true): self
    {
        $this->config->transactions($transactions);
        return $this;
    }

    /**
     * Turn transactions off.
     *
     * @return static
     */
    public function noTransactions(): self
    {
        $this->config->transactions(false);
        return $this;
    }

    /**
     * Turn the snapshots setting on.
     *
     * @param boolean $takeSnapshotAfterMigrations Take a snapshot of the database after migrations have been run?.
     * @param boolean $takeSnapshotAfterSeeders    Take a snapshot of the database after seeders have been run?.
     * @return static
     */
    public function snapshots(bool $takeSnapshotAfterMigrations = false, bool $takeSnapshotAfterSeeders = true): self
    {
        $this->config->snapshots(true, $takeSnapshotAfterMigrations, $takeSnapshotAfterSeeders);
        return $this;
    }

    /**
     * Turn the snapshots setting off.
     *
     * @return static
     */
    public function noSnapshots(): self
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
    public function isBrowserTest(bool $isBrowserTest = true): self
    {
        $this->config->isBrowserTest($isBrowserTest);
        return $this;
    }

    /**
     * Turn the is-browser-test setting off.
     *
     * @return static
     */
    public function isNotBrowserTest(): self
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
