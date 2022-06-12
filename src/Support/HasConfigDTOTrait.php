<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\Adapt\DTO\ConfigDTO;

/**
 * Provides methods to update the stored configDTO.
 */
trait HasConfigDTOTrait
{
    /** @var ConfigDTO A DTO containing the settings to use. */
    private ConfigDTO $configDTO;

    /**
     * Specify the database connection to prepare.
     *
     * @param string $connection The database connection to prepare.
     * @return static
     */
    public function connection(string $connection): self
    {
        $this->configDTO->connection($connection);
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
        $this->configDTO->databaseModifier($databaseModifier);
        return $this;
    }

    /**
     * Specify that no database-modifier is to be used.
     *
     * @return static
     */
    public function noDatabaseModifier(): self
    {
        $this->configDTO->databaseModifier('');
        return $this;
    }

    /**
     * Set the method to use when checking for source-file changes.
     *
     * @param string|boolean|null $cacheInvalidationMethod The method to use - 'content' / 'modified' / null (or bool).
     * @return static
     */
    public function cacheInvalidationMethod($cacheInvalidationMethod): self
    {
        $this->configDTO->cacheInvalidationMethod($cacheInvalidationMethod);
        return $this;
    }

    /**
     * Turn checking for source-file changes off.
     *
     * @return static
     */
    public function cacheInvalidationMethodOff(): self
    {
        $this->configDTO->cacheInvalidationMethod(null);
        return $this;
    }

    /**
     * Set the method to use when checking for source-file changes.
     *
     * @deprecated
     * @param boolean $checkForSourceChanges Whether build-checksums should be calculated or not.
     * @return static
     */
    public function checkForSourceChanges(bool $checkForSourceChanges = true): self
    {
        return $this->cacheInvalidationMethod($checkForSourceChanges);
    }

    /**
     * Turn checking for source-file changes off.
     *
     * @deprecated
     * @return static
     */
    public function dontCheckForSourceChanges(): self
    {
        return $this->cacheInvalidationMethodOff();
    }

    /**
     * Specify the database dump files to import before migrations run.
     *
     * @param string[]|string[][] $initialImports The database dump files to import, one per database type.
     * @return static
     */
    public function initialImports(array $initialImports = []): self
    {
        $this->configDTO->initialImports($initialImports);
        return $this;
    }

    /**
     * Specify the database dump files to import before migrations run.
     *
     * @deprecated
     * @param string[]|string[][] $preMigrationImports The database dump files to import, one per database type.
     * @return static
     */
    public function preMigrationImports(array $preMigrationImports = []): self
    {
        return $this->initialImports($preMigrationImports);
    }

    /**
     * Specify that no database dump files will be imported before migrations run.
     *
     * @return static
     */
    public function noInitialImports(): self
    {
        $this->configDTO->initialImports([]);
        return $this;
    }

    /**
     * Specify that no database dump files will be imported before migrations run.
     *
     * @deprecated
     * @return static
     */
    public function noPreMigrationImports(): self
    {
        return $this->noInitialImports();
    }

    /**
     * Turn migrations on (or off), or specify the location of the migrations to run.
     *
     * @param boolean|string $migrations Should the migrations be run? / the path of the migrations to run.
     * @return static
     */
    public function migrations($migrations = true): self
    {
        $this->configDTO->migrations($migrations);
        return $this;
    }

    /**
     * Turn migrations off.
     *
     * @return static
     */
    public function noMigrations(): self
    {
        $this->configDTO->migrations(false);
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
        $this->configDTO->seeders($seeders);
        return $this;
    }

    /**
     * Turn seeders off.
     *
     * @return static
     */
    public function noSeeders(): self
    {
        $this->configDTO->seeders([]);
        return $this;
    }

    /**
     * Specify the url to send "remote-build" requests to.
     *
     * @param string|null $remoteBuildUrl The remote Adapt installation to send "build" requests to.
     * @return static
     */
    public function remoteBuildUrl(?string $remoteBuildUrl): self
    {
        $this->configDTO->remoteBuildUrl($remoteBuildUrl);
        return $this;
    }

    /**
     * Turn remote building off.
     *
     * @return static
     */
    public function noRemoteBuildUrl(): self
    {
        $this->configDTO->remoteBuildUrl(null);
        return $this;
    }

    /**
     * Set the types of cache to use.
     *
     * @param boolean $reuseTransaction Reuse databases with a transaction?.
     * @param boolean $reuseJournal     Reuse databases with a journal?.
     * @param boolean $scenarioTestDBs  Create databases as needed for the database-scenario?.
     * @return static
     */
    public function cacheTools(
        bool $reuseTransaction,
        bool $reuseJournal,
        bool $scenarioTestDBs
    ): self {
        $this->configDTO->reuseTransaction($reuseTransaction);
        $this->configDTO->reuseJournal($reuseJournal);
        $this->configDTO->scenarioTestDBs($scenarioTestDBs);
        return $this;
    }

    /**
     * Turn the reuse-test-dbs setting on (or off).
     *
     * @deprecated
     * @param boolean $reuseTestDBs Reuse existing databases?.
     * @return static
     */
    public function reuseTestDBs(bool $reuseTestDBs = true): self
    {
        $this->reuseTransaction($reuseTestDBs);
        return $this;
    }

    /**
     * Turn the reuse-test-dbs setting off.
     *
     * @deprecated
     * @return static
     */
    public function noReuseTestDBs(): self
    {
        $this->reuseTransaction(false);
        return $this;
    }

    /**
     * Turn database re-use using transaction setting on (or off).
     *
     * @param boolean $reuseTransaction Reuse databases with a transaction?.
     * @return static
     */
    public function reuseTransaction(bool $reuseTransaction = true): self
    {
        $this->configDTO->reuseTransaction($reuseTransaction);
        return $this;
    }

    /**
     * Turn database re-use using transaction setting off.
     *
     * @return static
     */
    public function noReuseTransaction(): self
    {
        $this->configDTO->reuseTransaction(false);
        return $this;
    }

    /**
     * Turn database re-use using journaling setting on (or off).
     *
     * @param boolean $reuseJournal Reuse databases with a journal?.
     * @return static
     */
    public function reuseJournal(bool $reuseJournal = true): self
    {
        $this->configDTO->reuseJournal($reuseJournal);
        return $this;
    }

    /**
     * Turn database re-use using journaling setting off.
     *
     * @return static
     */
    public function noReuseJournal(): self
    {
        $this->configDTO->reuseJournal(false);
        return $this;
    }

    /**
     * Turn the scenario-test-dbs setting on (or off).
     *
     * @param boolean $scenarioTestDBs Create databases as needed for the database-scenario?.
     * @return static
     */
    public function scenarioTestDBs(bool $scenarioTestDBs = true): self
    {
        $this->configDTO->scenarioTestDBs($scenarioTestDBs);
        return $this;
    }

    /**
     * Turn the scenario-test-dbs setting off.
     *
     * @return static
     */
    public function noScenarioTestDBs(): self
    {
        $this->configDTO->scenarioTestDBs(false);
        return $this;
    }

    /**
     * Turn the snapshots setting on.
     *
     * @param string|boolean $useSnapshotsWhenReusingDB    Take and import snapshots when reusing databases?
     *                                                     false, 'afterMigrations', 'afterSeeders', 'both'.
     * @param string|boolean $useSnapshotsWhenNotReusingDB Take and import snapshots when NOT reusing databases?
     *                                                     false, 'afterMigrations', 'afterSeeders', 'both'.
     * @return static
     */
    public function snapshots($useSnapshotsWhenReusingDB, $useSnapshotsWhenNotReusingDB): self
    {
        $this->configDTO->snapshots($useSnapshotsWhenReusingDB, $useSnapshotsWhenNotReusingDB);
        return $this;
    }

    /**
     * Turn the snapshots setting off.
     *
     * @return static
     */
    public function noSnapshots(): self
    {
        $this->configDTO->snapshots(false, false);
        return $this;
    }

    /**
     * Turn the force-rebuild setting on (or off).
     *
     * @param boolean $forceRebuild Force the database to be rebuilt (or not).
     * @return static
     */
    public function forceRebuild(bool $forceRebuild = true): self
    {
        $this->configDTO->forceRebuild = $forceRebuild;
        return $this;
    }

    /**
     * Turn the force-rebuild setting off.
     *
     * @return static
     */
    public function dontForceRebuild(): self
    {
        $this->configDTO->forceRebuild = false;
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
        $this->configDTO->isBrowserTest($isBrowserTest);
        return $this;
    }

    /**
     * Turn the is-browser-test setting off.
     *
     * @return static
     */
    public function isNotBrowserTest(): self
    {
        $this->configDTO->isBrowserTest(false);
        return $this;
    }

    /**
     * Retrieve the connection being used.
     *
     * @return string
     */
    public function getConnection(): string
    {
        return $this->configDTO->connection;
    }

    /**
     * Retrieve the database being used.
     *
     * @return string|null
     */
    public function getDatabase(): ?string
    {
        return $this->configDTO->database;
    }



    /**
     * Check if this builder will build remotely.
     *
     * @return boolean
     */
    public function shouldBuildRemotely(): bool
    {
        return $this->configDTO->shouldBuildRemotely();
    }
}
