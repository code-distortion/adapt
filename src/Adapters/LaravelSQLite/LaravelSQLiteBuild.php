<?php

namespace CodeDistortion\Adapt\Adapters\LaravelSQLite;

use CodeDistortion\Adapt\Adapters\Interfaces\BuildInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Adapters\Traits\Laravel\LaravelBuildTrait;
use CodeDistortion\Adapt\Adapters\Traits\Laravel\LaravelHelperTrait;
use CodeDistortion\Adapt\Adapters\Traits\SQLite\SQLiteHelperTrait;
use CodeDistortion\Adapt\Support\Settings;

/**
 * Database-adapter methods related to building a Laravel/SQLite database.
 */
class LaravelSQLiteBuild implements BuildInterface
{
    use InjectTrait;
    use LaravelBuildTrait;
    use LaravelHelperTrait;
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
        return !$this->isMemoryDatabase();
    }

    /**
     * Create the database if it doesn't exist, and wipe the database clean if it does.
     *
     * @return void
     */
    public function resetDB()
    {
        if ($this->databaseExists()) {
            $this->wipeDBSQLite();
        } else {
            $this->createDB();
        }
    }

    /**
     * Determine if the database exists for the given connection.
     *
     * @return boolean
     */
    private function databaseExists(): bool
    {
        return ($this->isMemoryDatabase()
            ? true
            : $this->di->filesystem->fileExists((string) $this->config->database));
    }

    /**
     * Wipe the database.
     *
     * @return void
     */
    private function wipeDBSQLite()
    {
        if ($this->isMemoryDatabase()) {
            return;
        }

        $logTimer = $this->di->log->newTimer();

        // make sure we've disconnected from the database first
        $this->di->db->purge();

        $this->di->filesystem->unlink((string) $this->config->database);
        $this->createDB();

        $this->di->log->debug('Wiped the database', $logTimer);
    }

    /**
     * Create a new database.
     *
     * @return void
     */
    private function createDB()
    {
        if ($this->isMemoryDatabase()) {
            return;
        }

        $logTimer = $this->di->log->newTimer();

        $this->di->filesystem->touch((string) $this->config->database);

        $this->di->log->debug('Created the database', $logTimer);
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

    /**
     * Determine if a transaction can (and should) be used on this database.
     *
     * @return boolean
     */
    public function isTransactionable(): bool
    {
        // the database connection is closed between tests,
        // which causes :memory: databases to disappear,
        // so transactions can't be used on them between tests
        return !$this->isMemoryDatabase();
    }

    /**
     * Start the transaction that the test will be encapsulated in.
     *
     * @return void
     */
    public function applyTransaction()
    {
        $this->laravelApplyTransaction();
        $this->di->db->update("UPDATE`" . Settings::REUSE_TABLE . "` SET `inside_transaction` = 1");
    }
}
