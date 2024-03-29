<?php

namespace CodeDistortion\Adapt\Adapters\LaravelSQLite;

use CodeDistortion\Adapt\Adapters\Interfaces\BuildInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Adapters\Traits\Laravel\LaravelBuildTrait;
use CodeDistortion\Adapt\Adapters\Traits\SQLite\SQLiteHelperTrait;
use CodeDistortion\Adapt\Exceptions\AdaptBuildException;
use Throwable;

/**
 * Database-adapter methods related to building a Laravel/SQLite database.
 */
class LaravelSQLiteBuild implements BuildInterface
{
    use InjectTrait;
    use LaravelBuildTrait;
    use SQLiteHelperTrait;



    /**
     * Check if this database will disappear after use.
     *
     * @return boolean
     */
    public function databaseIsEphemeral(): bool
    {
        // memory SQLite databases are only available to the current connection, and cannot be shared or reused
        return $this->isMemoryDatabase();
    }

    /**
     * Check if this database type supports database re-use.
     *
     * @return boolean
     */
    public function supportsReuse(): bool
    {
        // memory SQLite databases are only available to the current connection, and cannot be shared or reused
        return !$this->isMemoryDatabase();
    }

    /**
     * Check if this database type supports the use of scenario-databases.
     *
     * @return boolean
     */
    public function supportsScenarios(): bool
    {
        // memory SQLite databases are only available to the current connection, and cannot be shared or reused
        return !$this->isMemoryDatabase();
    }

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
     * Remove the database (if it exists), and create it.
     *
     * @param boolean $exists Whether the database exists or not.
     * @return void
     */
    public function resetDB($exists)
    {
//        if ($this->databaseExists()) {
        if ($exists) {
            $this->dropDBSQLite();
        }

        $this->createDB();
    }

//    /**
//     * Determine if the database exists for the given connection.
//     *
//     * @return boolean
//     */
//    private function databaseExists(): bool
//    {
//        return $this->isMemoryDatabase() || $this->di->filesystem->fileExists((string) $this->configDTO->database);
//    }

    /**
     * Wipe the database.
     *
     * @return void
     */
    private function dropDBSQLite()
    {
        if ($this->isMemoryDatabase()) {
            return;
        }

        $logTimer = $this->di->log->newTimer();

        // make sure we've disconnected from the database first
        $this->di->db->purge();

        $this->di->filesystem->unlink((string) $this->configDTO->database);

        $this->di->log->vDebug('Removed the existing database', $logTimer);
    }

    /**
     * Create a new database.
     *
     * @return void
     * @throws AdaptBuildException When the database couldn't be created.
     */
    private function createDB()
    {
        if ($this->isMemoryDatabase()) {
            return;
        }

        $logTimer = $this->di->log->newTimer();

        try {
            $this->di->filesystem->touch((string) $this->configDTO->database);
        } catch (Throwable $e) {
            throw AdaptBuildException::couldNotCreateDatabase(
                (string) $this->configDTO->database,
                (string) $this->configDTO->driver,
                $e
            );
        }

        $this->di->log->vDebug('Created the database', $logTimer);
    }

    /**
     * Migrate the database.
     *
     * @param string|null $migrationsPath The location of the migrations.
     * @return void
     */
    public function migrate($migrationsPath)
    {
        $this->laravelMigrate($migrationsPath);
    }

    /**
     * Run the given seeders.
     *
     * @param string[] $seeders The seeders to run.
     * @return void
     */
    public function seed($seeders)
    {
        $this->laravelSeed($seeders);
    }
}
