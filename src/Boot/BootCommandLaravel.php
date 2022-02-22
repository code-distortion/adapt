<?php

namespace CodeDistortion\Adapt\Boot;

use CodeDistortion\Adapt\Boot\Traits\CheckLaravelHashPathsTrait;
use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DI\Injectable\Interfaces\LogInterface;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Exec;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Filesystem;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelArtisan;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelConfig;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelDB;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelLog;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use CodeDistortion\Adapt\Support\Hasher;
use CodeDistortion\Adapt\Support\LaravelSupport;
use CodeDistortion\Adapt\Support\Settings;
use CodeDistortion\Adapt\Support\StorageDir;

/**
 * Bootstrap Adapt for Laravel commands.
 */
class BootCommandLaravel extends BootCommandAbstract
{
    use CheckLaravelHashPathsTrait;


    /**
     * Ensure the storage-directory exists.
     *
     * @return static
     * @throws AdaptConfigException When the storage directory cannot be created.
     */
    public function ensureStorageDirExists()
    {
        StorageDir::ensureStorageDirExists($this->storageDir(), new Filesystem(), $this->newLog());
        return $this;
    }


    /**
     * Create a new DatabaseBuilder object and set its initial values.
     *
     * @param string $connection The database connection to prepare.
     * @return DatabaseBuilder
     */
    public function makeNewBuilder($connection): DatabaseBuilder
    {
        $config = $this->newConfigDTO($connection, '');
        $di = $this->defaultDI($connection);
        $pickDriverClosure = function (string $connection): string {
            return LaravelSupport::configString("database.connections.$connection.driver", 'unknown');
        };
        StorageDir::ensureStorageDirExists($config->storageDir, $di->filesystem, $di->log);

        return new DatabaseBuilder(
            'laravel',
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
            ->log($this->newLog())
            ->exec(new Exec())
            ->filesystem(new Filesystem());
    }

    /**
     * Build a new Log instance.
     *
     * @return LogInterface
     */
    private function newLog(): LogInterface
    {
        return new LaravelLog(false, false);
    }

    /**
     * Create a new ConfigDTO object with default values.
     *
     * @param string $connection The connection to use.
     * @param string $testName   The current test's name.
     * @return ConfigDTO
     */
    private function newConfigDTO(string $connection, string $testName): configDTO
    {
        $c = Settings::LARAVEL_CONFIG_NAME;
        return (new ConfigDTO())
            ->projectName(config("$c.project_name"))
            ->testName($testName)
            ->connection($connection)
            ->connectionExists(!is_null(config("database.connections.$connection")))
            ->database(config("database.connections.$connection.database"))
            ->storageDir($this->storageDir())
            ->snapshotPrefix('snapshot.')
            ->databasePrefix('')
            ->hashPaths($this->checkLaravelHashPaths(config("$c.look_for_changes_in")))->buildSettings(config("$c.pre_migration_imports"), config("$c.migrations"), config("$c.seeders"), config("$c.remote_build_url"), false, false, config("session.driver"), null)->cacheTools(config("$c.reuse_test_dbs"), config("$c.scenario_test_dbs"))->snapshots(config("$c.use_snapshots_when_reusing_db"), config("$c.use_snapshots_when_not_reusing_db"))->mysqlSettings(config("$c.database.mysql.executables.mysql"), config("$c.database.mysql.executables.mysqldump"))->postgresSettings(config("$c.database.pgsql.executables.psql"), config("$c.database.pgsql.executables.pg_dump"))->staleGraceSeconds(config("$c.stale_grace_seconds", Settings::DEFAULT_STALE_GRACE_SECONDS));
    }

    /**
     * Get the storage directory.
     *
     * @return string
     */
    private function storageDir(): string
    {
        $c = Settings::LARAVEL_CONFIG_NAME;
        return rtrim(config("$c.storage_dir"), '\\/');
    }

    /**
     * Work out if stale things are allowed to be purged.
     *
     * @return boolean
     */
    public function canPurgeStaleThings(): bool
    {
        $c = Settings::LARAVEL_CONFIG_NAME;
        if (config("$c.remote_build_url")) {
            return false;
        }
        return (bool) config("$c.remove_stale_things", true);
    }
}
