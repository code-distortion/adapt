<?php

namespace CodeDistortion\Adapt\Adapters\LaravelMySQL;

use CodeDistortion\Adapt\Adapters\Interfaces\BuildInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Adapters\Traits\Laravel\LaravelBuildTrait;
use CodeDistortion\Adapt\Adapters\Traits\Laravel\LaravelHelperTrait;
use CodeDistortion\Adapt\Support\Settings;

/**
 * Database-adapter methods related to building a Laravel/MySQL database.
 */
class LaravelMySQLBuild implements BuildInterface
{
    use InjectTrait, LaravelBuildTrait, LaravelHelperTrait;


    /**
     * Create the database if it doesn't exist, and wipe the database clean if it does.
     *
     * @return void
     */
    public function resetDB(): void
    {
        if ($this->di->db->currentDatabaseExists()) {
            $this->wipeDB();
        } else {
            $this->createDB();
        }
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
                $this->config->database,
                $this->conVal('charset', 'utf8mb4'),
                $this->conVal('collation', 'utf8mb4_unicode_ci')
            )
        );

        $this->di->log->info('Created database', $logTimer);
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

    /**
     * Determine if a transaction can (and should) be used on this database.
     *
     * @return boolean
     */
    public function isTransactionable(): bool
    {
        return true;
    }

    /**
     * Start the transaction that the test will be encapsulated in.
     *
     * @return void
     */
    public function applyTransaction(): void
    {
        $this->laravelApplyTransaction();
        $this->di->db->update("UPDATE`".Settings::REUSE_TABLE."` SET `inside_transaction` = 1");
    }
}
