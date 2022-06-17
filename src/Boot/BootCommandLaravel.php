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
use CodeDistortion\Adapt\Support\Hasher;
use CodeDistortion\Adapt\Support\LaravelSupport;
use CodeDistortion\Adapt\Support\Settings;
use CodeDistortion\Adapt\Support\StorageDir;

/**
 * Bootstrap Adapt for Laravel commands.
 */
class BootCommandLaravel extends BootCommandAbstract
{
    use CheckLaravelChecksumPathsTrait;


    /**
     * Ensure the storage-directories exist.
     *
     * @return static
     * @throws AdaptConfigException When the storage directory cannot be created.
     */
    public function ensureStorageDirsExist(): self
    {
        StorageDir::ensureStorageDirsExist(
            LaravelSupport::storageDir(),
            new Filesystem(),
            LaravelSupport::newLaravelLogger()
        );
        return $this;
    }


    /**
     * Create a new DatabaseBuilder object and set its initial values.
     *
     * @param string $connection The database connection to prepare.
     * @return DatabaseBuilder
     */
    public function makeNewBuilder(string $connection): DatabaseBuilder
    {
        $configDTO = $this->newConfigDTO($connection, '');
        $di = $this->defaultDI($connection);
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
            ->log(LaravelSupport::newLaravelLogger())
            ->exec(new Exec())
            ->filesystem(new Filesystem());
    }

    /**
     * Create a new ConfigDTO object with default values.
     *
     * @param string $connection The connection to use.
     * @param string $testName   The current test's name.
     * @return ConfigDTO
     * @throws AdaptBootException When the database name is invalid.
     */
    private function newConfigDTO(string $connection, string $testName): configDTO
    {
        $c = Settings::LARAVEL_CONFIG_NAME;

        $database = (string) config("database.connections.$connection.database");
        if (!mb_strlen($database)) {
            throw AdaptBootException::databaseNameIsInvalid($database);
        }

        return (new ConfigDTO())
            ->projectName(config("$c.project_name"))
            ->testName($testName)
            ->connection($connection)
            ->connectionExists(!is_null(config("database.connections.$connection")))
            ->origDatabase($database)
//            ->database(config("database.connections.$connection.database"))
            ->databaseModifier('')
            ->storageDir(LaravelSupport::storageDir())
            ->snapshotPrefix('snapshot.')
            ->databasePrefix('')
            ->cacheInvalidationMethod(config("$c.check_for_source_changes") ?? config("$c.cache_invalidation_method"))
            ->checksumPaths($this->checkLaravelChecksumPaths(config("$c.look_for_changes_in")))
            ->preCalculatedBuildChecksum(null)
            ->buildSettings(
                // accept the deprecated config('...pre_migration_imports') setting
                config("$c.pre_migration_imports") ?? config("$c.initial_imports"),
                config("$c.migrations"),
                config("$c.seeders"),
                config("$c.remote_build_url"),
                false,
                false,
                false,
                config("session.driver"),
                null,
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
                // accept the deprecated config('...reuse_test_dbs') setting
                config("$c.reuse_test_dbs") ?? config("$c.reuse.transactions"),
                config("$c.reuse.journals"),
                config("$c.verify_databases"),
                // accept the deprecated config('...scenario_test_dbs') setting
                config("$c.scenario_test_dbs") ?? config("$c.scenarios"),
            )
            ->snapshots(
                config("$c.use_snapshots_when_reusing_db"),
                config("$c.use_snapshots_when_not_reusing_db"),
            )
            ->forceRebuild(false)
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
