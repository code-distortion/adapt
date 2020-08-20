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
use CodeDistortion\Adapt\Support\Settings;

/**
 * Bootstrap Adapt for Laravel commands.
 */
class BootCommandLaravel extends BootCommandAbstract
{
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
        return new DatabaseBuilder('laravel', $testName, $di, $config, $pickDriverClosure);
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
            ->artisan(new LaravelArtisan)
            ->config(new LaravelConfig)
            ->db((new LaravelDB)->useConnection($connection))
            ->dbTransactionClosure(function () {
            })
            ->log(new LaravelLog(false, false))
            ->exec(new Exec)
            ->filesystem(new Filesystem);
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
        return (new ConfigDTO)
            ->projectName(config("$c.project-name"))
            ->connection($connection)
            ->database(config("database.connections.$connection.database"))
            ->storageDir(rtrim(config("$c.storage-dir"), '\\/'))
            ->snapshotPrefix('snapshot.')
            ->databasePrefix('')
            ->hashPaths(config("$c.look-for-changes-in"))
            ->buildSettings(
                config("$c.pre-migration-imports"),
                config("$c.migrations"),
                config("$c.seeders"),
                false
            )
            ->cacheTools(
                config("$c.reuse-test-dbs"),
                config("$c.dynamic-test-dbs"),
                config("$c.transactions")
            )
            ->snapshots(
                config("$c.snapshots.enabled"),
                config("$c.snapshots.take-after-migrations"),
                config("$c.snapshots.take-after-seeders")
            )
            ->mysqlSettings(
                config("$c.database.mysql.executables.mysql"),
                config("$c.database.mysql.executables.mysqldump")
            )
            ->postgresSettings(
                config("$c.database.pgsql.executables.psql"),
                config("$c.database.pgsql.executables.pg_dump")
            );
    }
}
