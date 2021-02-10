<?php

namespace CodeDistortion\Adapt\Boot;

use CodeDistortion\Adapt\Boot\Traits\CheckLaravelHashPathsTrait;
use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Exec;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Filesystem;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelArtisan;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelConfig;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelDB;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelLog;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Support\Hasher;
use CodeDistortion\Adapt\Support\Settings;

/**
 * Bootstrap Adapt for Laravel commands.
 */
class BootCommandLaravel extends BootCommandAbstract
{
    use CheckLaravelHashPathsTrait;

    /**
     * Create a new DatabaseBuilder object and set its initial values.
     *
     * @param string $connection The database connection to prepare.
     * @return DatabaseBuilder
     */
    public function makeNewBuilder(string $connection): DatabaseBuilder
    {
        $config = $this->newConfigDTO($connection);
        $di = $this->defaultDI($connection);
        $pickDriverClosure = function (string $connection) {
            return config("database.connections.$connection.driver", 'unknown');
        };

        $testName = '';
        return new DatabaseBuilder(
            'laravel',
            $testName,
            $di,
            $config,
            new Hasher($di, $config),
            $pickDriverClosure
        );
    }

    /**
     * Build a default DIContainer object.
     *
     * @param string $connection The connection to start using.
     * @return DIContainer
     */
    private function defaultDI(string $connection): DIContainer
    {
        return (new DIContainer())
            ->artisan(new LaravelArtisan())
            ->config(new LaravelConfig())
            ->db((new LaravelDB())->useConnection($connection))
            ->dbTransactionClosure(function () {
            })
            ->log(new LaravelLog(false, false))
            ->exec(new Exec())
            ->filesystem(new Filesystem());
    }

    /**
     * Create a new ConfigDTO object with default values.
     *
     * @param string $connection The connection to use.
     * @return ConfigDTO
     */
    private function newConfigDTO(string $connection): configDTO
    {
        $c = Settings::LARAVEL_CONFIG_NAME;
        return (new ConfigDTO())
            ->projectName(config("$c.project_name"))
            ->connection($connection)
            ->database(config("database.connections.$connection.database"))
            ->storageDir(rtrim(config("$c.storage_dir"), '\\/'))
            ->snapshotPrefix('snapshot.')
            ->databasePrefix('test_')
            ->hashPaths($this->checkLaravelHashPaths(config("$c.look_for_changes_in")))
            ->buildSettings(
                config("$c.pre_migration_imports"),
                config("$c.migrations"),
                config("$c.seeders"),
                false
            )
            ->cacheTools(
                config("$c.reuse_test_dbs"),
                config("$c.scenario_test_dbs")
            )
            ->snapshots(
                config("$c.snapshots.enabled"),
                config("$c.snapshots.take_after_migrations"),
                config("$c.snapshots.take_after_seeders")
            )
            ->mysqlSettings(
                config("$c.database.mysql.executables.mysql"),
                config("$c.database.mysql.executables.mysqldump")
            )
            ->postgresSettings(
                config("$c.database.pgsql.executables.psql"),
                config("$c.database.pgsql.executables.pg_dump")
            )
            ->invalidationGraceSeconds(
                config("$c.invalidation_grace_seconds") ?? Settings::DEFAULT_INVALIDATION_GRACE_SECONDS
            );
    }
}
