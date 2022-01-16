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
 * Bootstrap Adapt to build a database remotely.
 */
class BootRemoteBuildLaravel extends BootRemoteBuildAbstract
{
    use CheckLaravelHashPathsTrait;


    /**
     * Ensure the storage-directory exists.
     *
     * @return static
     * @throws AdaptConfigException When the storage directory cannot be created.
     */
    public function ensureStorageDirExists(): self
    {
        StorageDir::ensureStorageDirExists($this->storageDir(), new Filesystem(), $this->newLog());
        return $this;
    }


    /**
     * Create a new DatabaseBuilder object and set its initial values.
     *
     * @param ConfigDTO $remoteConfig The config from the remote Adapt installation.
     * @return DatabaseBuilder
     */
    public function makeNewBuilder(ConfigDTO $remoteConfig): DatabaseBuilder
    {
        $config = $this->newConfigDTO($remoteConfig);
        $di = $this->defaultDI($remoteConfig->connection);
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
            ->dbTransactionClosure(fn() => null)
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
        $useLaravelLog = config(Settings::LARAVEL_CONFIG_NAME . '.log.laravel');

        // don't use stdout debugging, it will ruin the output being generated that the calling Adapt instance reads.
        return new LaravelLog(false, $useLaravelLog);
    }

    /**
     * Create a new ConfigDTO object with default values.
     *
     * @param ConfigDTO $remoteConfig The config from the remote Adapt installation.
     * @return ConfigDTO
     */
    private function newConfigDTO(ConfigDTO $remoteConfig): configDTO
    {
        $c = Settings::LARAVEL_CONFIG_NAME;
        $connection = $remoteConfig->connection;
        return (new ConfigDTO())
            ->projectName($remoteConfig->projectName)
            ->testName($remoteConfig->testName)
            ->connection($connection)
            ->connectionExists(!is_null(config("database.connections.$connection")))
            ->database(config("database.connections.$connection.database"))
            ->databaseModifier($remoteConfig->databaseModifier)
            ->storageDir($this->storageDir())
            ->snapshotPrefix('snapshot.')
            ->databasePrefix('')
            ->hashPaths($this->checkLaravelHashPaths(config("$c.look_for_changes_in")))
            ->buildSettings(
                $remoteConfig->preMigrationImports,
                $remoteConfig->migrations,
                $remoteConfig->seeders,
                null, // don't forward again
                $remoteConfig->isBrowserTest,
                true // yes, a remote database is being built here now, locally
            )
            ->cacheTools(
                $remoteConfig->reuseTestDBs,
                $remoteConfig->scenarioTestDBs
            )
            ->snapshots(
                $remoteConfig->useSnapshotsWhenReusingDB,
                $remoteConfig->useSnapshotsWhenNotReusingDB,
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
                config("$c.invalidation_grace_seconds", Settings::DEFAULT_INVALIDATION_GRACE_SECONDS)
            );
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
}
