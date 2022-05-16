<?php

namespace CodeDistortion\Adapt\Adapters\LaravelSQLite;

use CodeDistortion\Adapt\Adapters\Interfaces\BuildInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Adapters\Traits\Laravel\LaravelBuildTrait;
use CodeDistortion\Adapt\Adapters\Traits\SQLite\SQLiteHelperTrait;

/**
 * Database-adapter methods related to building a Laravel/SQLite database.
 */
class LaravelSQLiteBuild implements BuildInterface
{
    use InjectTrait;
    use LaravelBuildTrait;
    use SQLiteHelperTrait;



    /**
     * Check if this database type can be built remotely.
     *
     * @return boolean
     */
    public function canBeBuiltRemotely(): bool
    {
        return false;
    }

    /**
     * Check if this database type can be used when browser testing.
     *
     * @return boolean
     */
    public function isCompatibleWithBrowserTests(): bool
    {
        // memory SQLite databases are only available to the current connection, and cannot be shared or reused
        return !$this->isMemoryDatabase();
    }

    /**
     * Create the database if it doesn't exist, and wipe the database clean if it does.
     *
     * @return void
     */
    public function resetDB(): void
    {
        if ($this->databaseExists()) {
            $this->dropDBSQLite();
        }

        $this->createDB();
    }

    /**
     * Determine if the database exists for the given connection.
     *
     * @return boolean
     */
    private function databaseExists(): bool
    {
        return $this->isMemoryDatabase() || $this->di->filesystem->fileExists((string)$this->configDTO->database);
    }

    /**
     * Wipe the database.
     *
     * @return void
     */
    private function dropDBSQLite(): void
    {
        if ($this->isMemoryDatabase()) {
            return;
        }

        $logTimer = $this->di->log->newTimer();

        // make sure we've disconnected from the database first
        $this->di->db->purge();

        $this->di->filesystem->unlink((string) $this->configDTO->database);

        $this->di->log->debug('Removed the existing database', $logTimer);
    }

    /**
     * Create a new database.
     *
     * @return void
     */
    private function createDB(): void
    {
        if ($this->isMemoryDatabase()) {
            return;
        }

        $logTimer = $this->di->log->newTimer();

        $this->di->filesystem->touch((string) $this->configDTO->database);

        $this->di->log->debug('Created the database', $logTimer);
    }

    /**
     * Migrate the database.
     *
     * @param string|null $migrationsPath The location of the migrations.
     * @return void
     */
    public function migrate(?string $migrationsPath): void
    {
        $this->laravelMigrate($migrationsPath);
    }

    /**
     * Run the given seeders.
     *
     * @param string[] $seeders The seeders to run.
     * @return void
     */
    public function seed(array $seeders): void
    {
        $this->laravelSeed($seeders);
    }
}
