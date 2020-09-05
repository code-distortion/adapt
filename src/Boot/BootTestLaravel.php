<?php

namespace CodeDistortion\Adapt\Boot;

use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DI\Injectable\Exec;
use CodeDistortion\Adapt\DI\Injectable\Filesystem;
use CodeDistortion\Adapt\DI\Injectable\LaravelArtisan;
use CodeDistortion\Adapt\DI\Injectable\LaravelConfig;
use CodeDistortion\Adapt\DI\Injectable\LaravelDB;
use CodeDistortion\Adapt\DI\Injectable\LaravelLog;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Exceptions\AdaptBootException;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;

/**
 * Bootstrap Adapt for Laravel tests.
 */
class BootTestLaravel extends BootTestAbstract
{
    /**
     * Build a default DIContainer object.
     *
     * @param string $connection The connection to start using.
     * @return DIContainer
     * @throws AdaptBootException Thrown when a PropBag hasn't been set yet.
     */
    protected function defaultDI(string $connection): DIContainer
    {
        if (!$this->propBag) {
            throw AdaptBootException::propBagNotSet();
        }

        return (new DIContainer())
            ->artisan(new LaravelArtisan)
            ->config(new LaravelConfig)
            ->db((new LaravelDB)->useConnection($connection))
            ->dbTransactionClosure($this->transactionClosure)
            ->log(new LaravelLog(
                (bool) $this->propBag->config('log.stdout'),
                (bool) $this->propBag->config('log.laravel')
            ))
            ->exec(new Exec)
            ->filesystem(new Filesystem);
    }


    /**
     * Create a new DatabaseBuilder object based on the "default" database connection.
     *
     * @return DatabaseBuilder
     */
    protected function newDefaultBuilder(): DatabaseBuilder
    {
        return $this->newBuilder(config('database.default'));
    }

    /**
     * Create a new DatabaseBuilder object, and add it to the list to execute later.
     *
     * @param string $connection The database connection to prepare.
     * @return DatabaseBuilder
     * @throws AdaptConfigException Thrown when the connection doesn't exist.
     */
    public function newBuilder(string $connection): DatabaseBuilder
    {
        if (!config("database.connections.$connection")) {
            throw AdaptConfigException::invalidConnection($connection);
        }
        $builder = $this->createBuilder($connection);
        $this->addBuilder($builder);
        return $builder;
    }

    /**
     * Create a new DatabaseBuilder object and set its initial values.
     *
     * The initial values are based on the config + the properties of the
     * current test-class.
     *
     * @param string $connection The database connection to prepare.
     * @return DatabaseBuilder
     */
    private function createBuilder(string $connection): DatabaseBuilder
    {
        $config = $this->newConfigDTO($connection);

        // @todo - work out how to inject the DIContainer
        // - clone the one that was passed in? pass in a closure to create one?
        $di = $this->defaultDI($connection);

        $pickDriverClosure = function (string $connection) {
            return config("database.connections.$connection.driver", 'unknown');
        };

//        return new DatabaseBuilder('laravel', $this->testName, $this->di, $config, $pickDriverClosure);
        return new DatabaseBuilder('laravel', (string) $this->testName, $di, $config, $pickDriverClosure);
    }

    /**
     * Create a new ConfigDTO object with default values.
     *
     * @param string $connection The connection to use.
     * @return ConfigDTO
     * @throws AdaptBootException Thrown when a PropBag hasn't been set yet.
     */
    private function newConfigDTO(string $connection): configDTO
    {
        if (!$this->propBag) {
            throw AdaptBootException::propBagNotSet();
        }

        $paraTestDBModifier = (string) getenv('TEST_TOKEN');

        return (new ConfigDTO)
            ->projectName($this->propBag->config('project-name'))
            ->connection($connection)
            ->database(config("database.connections.$connection.database"))
            ->databaseModifier($paraTestDBModifier)
            ->storageDir(rtrim($this->propBag->config('storage-dir'), '\\/'))
            ->snapshotPrefix('snapshot.')
            ->databasePrefix('')
            ->hashPaths($this->propBag->config('look-for-changes-in'))
            ->buildSettings(
                $this->propBag->config('pre-migration-imports', 'preMigrationImports'),
                $this->propBag->config('migrations', 'migrations'),
                $this->propBag->config('seeders', 'seeders'),
                $this->propBag->prop('isBrowserTest', $this->browserTestDetected)
            )
            ->cacheTools(
                $this->propBag->config('reuse-test-dbs', 'reuseTestDBs'),
                $this->propBag->config('dynamic-test-dbs', 'dynamicTestDBs'),
                $this->propBag->config('transactions', 'transactions')
            )
            ->snapshots(
                $this->propBag->config('snapshots.enabled', 'snapshotsEnabled'),
                $this->propBag->config('snapshots.take-after-migrations', 'takeSnapshotAfterMigrations'),
                $this->propBag->config('snapshots.take-after-seeders', 'takeSnapshotAfterSeeders')
            )
            ->mysqlSettings(
                $this->propBag->config('database.mysql.executables.mysql'),
                $this->propBag->config('database.mysql.executables.mysqldump')
            )
            ->postgresSettings(
                $this->propBag->config('database.pgsql.executables.psql'),
                $this->propBag->config('database.pgsql.executables.pg_dump')
            );
    }
}
