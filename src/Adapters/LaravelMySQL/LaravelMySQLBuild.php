<?php

namespace CodeDistortion\Adapt\Adapters\LaravelMySQL;

use CodeDistortion\Adapt\Adapters\Interfaces\BuildInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Adapters\Traits\Laravel\LaravelBuildTrait;
use CodeDistortion\Adapt\Adapters\Traits\Laravel\LaravelHelperTrait;

/**
 * Database-adapter methods related to building a Laravel/MySQL database.
 */
class LaravelMySQLBuild implements BuildInterface
{
    use InjectTrait;
    use LaravelBuildTrait;
    use LaravelHelperTrait;



    /**
     * Check if this database type can be built remotely.
     *
     * @return boolean
     */
    public function canBeBuiltRemotely(): bool
    {
        return true;
    }

    /**
     * Check if this database type can be used when browser testing.
     *
     * @return boolean
     */
    public function isCompatibleWithBrowserTests(): bool
    {
        return true;
    }

    /**
     * Create the database if it doesn't exist, and wipe the database clean if it does.
     *
     * @return void
     */
    public function resetDB(): void
    {
        if ($this->di->db->currentDatabaseExists()) {
            $this->dropDB();
        }

        $this->createDB();
    }

    /**
     * Create a new database.
     *
     * @return void
     */
    private function createDB(): void
    {
        $logTimer = $this->di->log->newTimer();

        $this->di->db->newPDO()->createDatabase(
            sprintf(
                'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE %s',
                $this->configDTO->database,
                $this->conVal('charset', 'utf8mb4'),
                $this->conVal('collation', 'utf8mb4_unicode_ci')
            )
        );

        $this->di->log->debug('Created a new database', $logTimer);
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
