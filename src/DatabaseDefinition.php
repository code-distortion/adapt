<?php

namespace CodeDistortion\Adapt;

use CodeDistortion\Adapt\DTO\ConfigDTO;

/**
 * Define how a database should be built.
 */
class DatabaseDefinition
{
    /**
     * Constructor.
     */
    public function __construct(private ConfigDTO $configDTO)
    {
    }



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
     * Set this builder's database connection to be the "default" one.
     *
     * @return static
     */
    public function makeDefault(): self
    {
        $this->configDTO->isDefaultConnection(true);
        return $this;
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
     * Turn migrations on (or off), or specify the location of the migrations to run.
     *
     * @param boolean|string $migrations Should the migrations be run? / the path of the migrations to run.
     * @return static
     */
    public function migrations(bool|string $migrations = true): self
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
     * @param string|string[] $seeders The seeders to run after migrating.
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
     * Turn the is-browser-test setting on (or off).
     *
     * @param boolean $isBrowserTest Is this test a browser-test?.
     * @return static
     */
    public function isABrowserTest(bool $isBrowserTest = true): self
    {
        $this->configDTO->isBrowserTest($isBrowserTest);
        return $this;
    }

    /**
     * Turn the is-browser-test setting off.
     *
     * @return static
     */
    public function isNotABrowserTest(): self
    {
        $this->configDTO->isBrowserTest(false);
        return $this;
    }



    /**
     * Turn database transaction re-use setting on (or off).
     *
     * @param boolean $transaction Reuse databases with a transaction?.
     * @return static
     */
    public function transaction(bool $transaction = true): self
    {
        $this->configDTO->reuseTransaction($transaction);
        return $this;
    }

    /**
     * Turn database transaction re-use setting off.
     *
     * @return static
     */
    public function noTransaction(): self
    {
        $this->configDTO->reuseTransaction(false);
        return $this;
    }



    /**
     * Turn database re-use using journaling setting on (or off).
     *
     * @param boolean $journal Reuse databases with a journal?.
     * @return static
     */
    public function journal(bool $journal = true): self
    {
        $this->configDTO->reuseJournal($journal);
        return $this;
    }

    /**
     * Turn database journaling re-use setting off.
     *
     * @return static
     */
    public function noJournal(): self
    {
        $this->configDTO->reuseJournal(false);
        return $this;
    }



    /**
     * Set the snapshot settings.
     *
     * @param string|boolean|null $snapshots Take and import snapshots when reusing databases?
     *                                       false
     *                                       / "afterMigrations" / "afterSeeders" / "both"
     *                                       / "!afterMigrations" / "!afterSeeders" / "!both"
     * @return static
     */
    public function snapshots(string|bool|null $snapshots): self
    {
        $this->configDTO->snapshots($snapshots);
        return $this;
    }

    /**
     * Turn the snapshots setting off.
     *
     * @return static
     */
    public function noSnapshots(): self
    {
        $this->configDTO->snapshots(null);
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
     * Turn the force-rebuild setting on (or off).
     *
     * @param boolean $forceRebuild Force the database to be rebuilt (or not).
     * @return static
     */
    public function forceRebuild(bool $forceRebuild = true): self
    {
        $this->configDTO->forceRebuild($forceRebuild);
        return $this;
    }

    /**
     * Turn the force-rebuild setting off.
     *
     * @return static
     */
    public function dontForceRebuild(): self
    {
        $this->configDTO->forceRebuild(false);
        return $this;
    }
}
