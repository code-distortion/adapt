<?php

namespace CodeDistortion\Adapt\Boot;

use CodeDistortion\Adapt\Boot\Traits\CheckLaravelChecksumPathsTrait;
use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Exec;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Filesystem;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelArtisan;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelDB;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Exceptions\AdaptBootException;
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
    use CheckLaravelChecksumPathsTrait;


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
     * @param ConfigDTO $remoteConfigDTO The config from the remote Adapt installation.
     * @return DatabaseBuilder
     * @throws AdaptRemoteShareException When the session drivers don't match during browser tests.
     */
    public function makeNewBuilder(ConfigDTO $remoteConfigDTO): DatabaseBuilder
    {
        $configDTO = $this->newConfigDTO($remoteConfigDTO);
        $di = $this->defaultDI($remoteConfigDTO->connection);
        $pickDriverClosure = function (string $connection): string {
            return LaravelSupport::configString("database.connections.$connection.driver", 'unknown');
        };

        return new DatabaseBuilder(
            'laravel',
            $di,
            $configDTO,
            new Hasher($di, $configDTO),
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
            ->log($this->log)
            ->exec(new Exec())
            ->filesystem(new Filesystem());
    }

    /**
     * Create a new ConfigDTO object with default values.
     *
     * @param ConfigDTO $remoteConfigDTO The config from the remote Adapt installation.
     * @return ConfigDTO
     * @throws AdaptBootException When the database name isn't valid.
     */
    private function newConfigDTO(ConfigDTO $remoteConfigDTO): configDTO
    {
        $c = Settings::LARAVEL_CONFIG_NAME;
        $connection = $remoteConfigDTO->connection;

        $database = (string) config("database.connections.$connection.database");
        if (!mb_strlen($database)) {
            throw AdaptBootException::databaseNameIsInvalid($database);
        }

        return (new ConfigDTO())
            ->projectName($remoteConfigDTO->projectName)
            ->testName($remoteConfigDTO->testName)
            ->connection($connection)
            ->connectionExists(!is_null(config("database.connections.$connection")))
            ->origDatabase($database)
//            ->database(config("database.connections.$connection.database"))
            ->databaseModifier($remoteConfigDTO->databaseModifier)
            ->storageDir($this->storageDir())
            ->snapshotPrefix('snapshot.')
            ->databasePrefix('')
            ->cacheInvalidationMethod(config("$c.cache_invalidation_method"))
            ->checksumPaths($this->checkLaravelChecksumPaths(config("$c.look_for_changes_in")))
            ->preCalculatedBuildChecksum($remoteConfigDTO->preCalculatedBuildChecksum)
            ->buildSettings(
                $remoteConfigDTO->initialImports,
                $remoteConfigDTO->migrations,
                $remoteConfigDTO->seeders,
                null, // don't forward again
                $remoteConfigDTO->isBrowserTest,
                $remoteConfigDTO->isParallelTest,
                true, // yes, a remote database is being built here now, locally
                config("session.driver"),
                $remoteConfigDTO->sessionDriver,
            )
            ->dbAdapterSupport(
                true,
                true,
                true,
                true,
                true,
                true,
            )
            ->cacheTools(
                $remoteConfigDTO->reuseTransaction,
                $remoteConfigDTO->reuseJournal,
                $remoteConfigDTO->verifyDatabase,
                $remoteConfigDTO->scenarioTestDBs,
            )
            ->snapshots(
                $remoteConfigDTO->useSnapshotsWhenReusingDB,
                $remoteConfigDTO->useSnapshotsWhenNotReusingDB,
            )
            ->forceRebuild($remoteConfigDTO->forceRebuild)
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
