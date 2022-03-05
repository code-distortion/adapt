<?php

namespace CodeDistortion\Adapt\Boot;

use CodeDistortion\Adapt\Boot\Traits\CheckLaravelHashPathsTrait;
use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Exec;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Filesystem;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelArtisan;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelDB;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use CodeDistortion\Adapt\Exceptions\AdaptRemoteShareException;
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
        StorageDir::ensureStorageDirExists($this->storageDir(), new Filesystem(), $this->log);
        return $this;
    }


    /**
     * Create a new DatabaseBuilder object and set its initial values.
     *
     * @param ConfigDTO $remoteConfig The config from the remote Adapt installation.
     * @return DatabaseBuilder
     * @throws AdaptRemoteShareException When the session drivers don't match during browser tests.
     */
    public function makeNewBuilder(ConfigDTO $remoteConfig): DatabaseBuilder
    {
        $config = $this->newConfigDTO($remoteConfig);
        $di = $this->defaultDI($remoteConfig->connection);
        $pickDriverClosure = function (string $connection): string {
            return LaravelSupport::configString("database.connections.$connection.driver", 'unknown');
        };

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
            ->db((new LaravelDB())->useConnection($connection))
            ->dbTransactionClosure(fn() => null)
            ->log($this->log)
            ->exec(new Exec())
            ->filesystem(new Filesystem());
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
            ->origDatabase(config("database.connections.$connection.database"))
//            ->database(config("database.connections.$connection.database"))
            ->databaseModifier($remoteConfig->databaseModifier)
            ->storageDir($this->storageDir())
            ->snapshotPrefix('snapshot.')
            ->databasePrefix('')
            ->checkForSourceChanges($remoteConfig->checkForSourceChanges)
            ->hashPaths($this->checkLaravelHashPaths(config("$c.look_for_changes_in")))
            ->preCalculatedBuildHash($remoteConfig->preCalculatedBuildHash)
            ->buildSettings(
                $remoteConfig->preMigrationImports,
                $remoteConfig->migrations,
                $remoteConfig->seeders,
                null, // don't forward again
                $remoteConfig->isBrowserTest,
                true, // yes, a remote database is being built here now, locally
                config("session.driver"),
                $remoteConfig->sessionDriver,
            )
            ->cacheTools(
                $remoteConfig->reuseTestDBs,
                $remoteConfig->scenarioTestDBs,
            )
            ->snapshots(
                $remoteConfig->useSnapshotsWhenReusingDB,
                $remoteConfig->useSnapshotsWhenNotReusingDB,
            )
            ->forceRebuild($remoteConfig->forceRebuild)
            ->mysqlSettings(
                config("$c.database.mysql.executables.mysql"),
                config("$c.database.mysql.executables.mysqldump"),
            )
            ->postgresSettings(
                config("$c.database.pgsql.executables.psql"),
                config("$c.database.pgsql.executables.pg_dump"),
            )
            ->staleGraceSeconds(
                config("$c.stale_grace_seconds", Settings::DEFAULT_STALE_GRACE_SECONDS),
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
        $return = config("$c.storage_dir");
        $return = is_string($return) ? $return : '';
        return rtrim($return, '\\/');
    }
}
