<?php

namespace CodeDistortion\Adapt\Boot;

use CodeDistortion\Adapt\Boot\Traits\CheckLaravelChecksumPathsTrait;
use CodeDistortion\Adapt\Boot\Traits\HasMutexTrait;
use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DatabaseDefinition;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Exec;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Filesystem;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelArtisan;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelDB;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\DTO\RemoteShareDTO;
use CodeDistortion\Adapt\Exceptions\AdaptBootException;
use CodeDistortion\Adapt\Exceptions\AdaptBrowserTestException;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use CodeDistortion\Adapt\Support\Exceptions;
use CodeDistortion\Adapt\Support\Hasher;
use CodeDistortion\Adapt\Support\LaravelSupport;
use CodeDistortion\Adapt\Support\Settings;
use CodeDistortion\Adapt\Support\StorageDir;
use DateInterval;
use DateTime;
use DateTimeZone;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\ParallelTesting;
use Laravel\Dusk\Browser;
use Throwable;

/**
 * Bootstrap Adapt for Laravel tests.
 */
class BootTestLaravel extends BootTestAbstract
{
    use CheckLaravelChecksumPathsTrait;
    use HasMutexTrait;

    /** @var string[] The paths to the sharable config files, created during browser tests. */
    private $sharableConfigPaths = [];


    /**
     * Check that it's safe to run.
     *
     * @return void
     * @throws AdaptConfigException When the .env.testing file wasn't used to build the environment.
     */
    protected function isAllowedToRun()
    {
        $this->ensureEnvTestingFileExists();
        $this->ensureReCreateDatabasesIsntSet();
    }

    /**
     * Check that the .env.testing file exists.
     *
     * @return void
     * @throws AdaptConfigException When the .env.testing file wasn't used to build the environment.
     */
    private function ensureEnvTestingFileExists()
    {
        if ((new Filesystem())->fileExists(LaravelSupport::basePath(Settings::LARAVEL_ENV_TESTING_FILE))) {
            return;
        }

        throw AdaptConfigException::cannotLoadEnvTestingFile();
    }

    /**
     * Check that the --recreate-databases option hasn't been added when --parallel testing.
     *
     * Because Adapt dynamically decides which database/s to use based on the settings for each test, it's not
     * practical to pre-determine which ones to rebuild. And because of the nature of parallel testing, it's also not
     * possible to simply remove oll of the databases before running the tests.
     *
     * @return void
     * @throws AdaptBootException When the --recreate-databases option has been used when parallel testing.
     */
    public function ensureReCreateDatabasesIsntSet()
    {
        if (!$this->parallelTestingSaysRebuildDBs()) {
            return;
        }

        throw AdaptBootException::parallelTestingSaysRebuildDBs();
    }


    /**
     * Ensure the storage-directories exist.
     *
     * @return static
     * @throws AdaptConfigException When the storage directory cannot be created.
     */
    public function ensureStorageDirsExist()
    {
        StorageDir::ensureStorageDirsExist($this->storageDir(), new Filesystem(), $this->log);
        return $this;
    }


    /**
     * Build a default DIContainer object.
     *
     * @param string $connection The connection to start using.
     * @return DIContainer
     */
    protected function defaultDI($connection): DIContainer
    {
        return (new DIContainer())
            ->artisan(new LaravelArtisan())
            ->db((new LaravelDB())->useConnection($connection))
            ->log($this->log)
            ->exec(new Exec())
            ->filesystem(new Filesystem());
    }



    /**
     * Create a new DatabaseDefinition object based on the "default" database connection,
     * and add it to the list to use later.
     *
     * @return DatabaseDefinition
     * @throws AdaptBootException When the database name isn't valid.
     */
    protected function newDefaultDatabaseDefinition(): DatabaseDefinition
    {
        return $this->newDatabaseDefinitionFromConnection(LaravelSupport::configString('database.default'));
    }

    /**
     * Create a new DatabaseDefinition object based on a connection, and add it to the list to use later.
     *
     * @param string $connection The database connection to prepare.
     * @return DatabaseDefinition
     * @throws AdaptBootException When the database name isn't valid.
     */
    public function newDatabaseDefinitionFromConnection($connection): DatabaseDefinition
    {
        $configDTO = $this->newConfigDTO($connection, $this->testName);
        $databaseDefinition = new DatabaseDefinition($configDTO);
        $this->addDatabaseDefinition($databaseDefinition);
        return $databaseDefinition;
    }



    /**
     * Create a new DatabaseDefinition object based on the "default" database connection.
     *
     * @param boolean $addToList Add this DatabaseBuilder to the list to use later or not.
     * @return DatabaseBuilder
     * @throws AdaptBootException When the database name isn't valid.
     */
    protected function newDefaultDatabaseBuilder($addToList = true): DatabaseBuilder
    {
        return $this->newDatabaseBuilderFromConnection(LaravelSupport::configString('database.default'), $addToList);
    }

    /**
     * Create a new DatabaseBuilder object based on a connection, and add it to the list to execute later.
     *
     * @param string  $connection The database connection to prepare.
     * @param boolean $addToList  Add this DatabaseBuilder to the list to use later or not.
     * @return DatabaseBuilder
     * @throws AdaptBootException When the database name isn't valid.
     */
    public function newDatabaseBuilderFromConnection($connection, $addToList = true): DatabaseBuilder
    {
        $configDTO = $this->newConfigDTO($connection, $this->testName);
        return $this->newDatabaseBuilderFromConfigDTO($configDTO, $addToList);
    }

    /**
     * Create a new DatabaseBuilder object based on a ConfigDTO, and add it to the list to execute later.
     *
     * @param ConfigDTO $configDTO The ConfigDTO to use, already defined.
     * @param boolean   $addToList Add this DatabaseBuilder to the list to use later or not.
     * @return DatabaseBuilder
     */
    protected function newDatabaseBuilderFromConfigDTO($configDTO, $addToList = true): DatabaseBuilder
    {
        $databaseBuilder = $this->createDatabaseBuilderFromConfigDTO($configDTO);

        if ($addToList) {
            $this->addDatabaseBuilder($databaseBuilder);
        }

        return $databaseBuilder;
    }



    /**
     * Create a new DatabaseBuilder object based on a ConfigDTO, and set its initial values.
     *
     * The initial values are based on the config + the properties of the
     * current test-class.
     *
     * @param ConfigDTO $configDTO The ConfigDTO to use, already defined.
     * @return DatabaseBuilder
     */
    private function createDatabaseBuilderFromConfigDTO(ConfigDTO $configDTO): DatabaseBuilder
    {
        // @todo - work out how to inject the DIContainer
        // - clone the one that was passed in? pass in a closure to create one?
        $di = $this->defaultDI($configDTO->connection);

        $pickDriverClosure = function (string $connection): string {
            if (!config("database.connections.$connection")) {
                throw AdaptConfigException::invalidConnection($connection);
            }
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
     * Create a new ConfigDTO object with default values.
     *
     * @param string $connection The connection to use.
     * @param string $testName   The current test's name.
     * @return ConfigDTO
     * @throws AdaptConfigException When the connection doesn't exist.
     * @throws AdaptBootException   When the database name isn't valid.
     */
    private function newConfigDTO(string $connection, string $testName): configDTO
    {
        if (!config("database.connections.$connection")) {
            throw AdaptConfigException::invalidConnection($connection);
        }



        $paraTestDBModifier = (string) getenv('TEST_TOKEN');
        $isParallelTest = mb_strlen($paraTestDBModifier) > 0;

        $c = Settings::LARAVEL_CONFIG_NAME;
        $pb = $this->propBag;



        $database = (string) $pb->config("database.connections.$connection.database");
        $this->isDatabaseNameOk($database);

        // accept the deprecated $preMigrationImports and config('...pre_migration_imports') settings
        $propVal = $pb->prop('preMigrationImports', null) ?? $pb->prop('initialImports', null);
        $configVal =
            config("$c.pre_migration_imports")
            ?? config("$c.initial_imports")
            ?? config("$c.build_sources.initial_imports");

        $initialImports = $configVal ?? [];
        $propVal = $propVal ?? [];
        foreach ($propVal as $key => $value) {
            $initialImports[$key] = $value;
        }

        // accept the deprecated $reuseTestDBs, $reuseTransaction and config('...reuse_test_dbs') settings
        $propVal = $pb->prop('reuseTestDBs', null)
            ?? $pb->prop('reuseTransaction', null)
            ?? $pb->prop('transactions', null);
        $configVal =
            config("$c.reuse_test_dbs")
            ?? config("$c.reuse.transactions")
            ?? config("$c.reuse_methods.transactions");
        $transaction = $propVal ?? $configVal;

        // accept the deprecated $journals and config('reuse.journals') settings
        $propVal = $pb->prop('reuseJournal', null) ?? $pb->prop('journals', null);
        $configVal = config("$c.reuse.journals") ?? config("$c.reuse_methods.journals");
        $journal = $propVal ?? $configVal;

        // accept the deprecated config('...scenario_test_dbs') settings
        $scenarios = config("$c.scenario_test_dbs") ?? config("$c.scenarios");

        $cacheInvalidationLocations = config("$c.look_for_changes_in") ?? config("$c.cache_invalidation.locations");
        $cacheInvalidationMethod =
            config("$c.check_for_source_changes")
            ?? config("$c.cache_invalidation_method")
            ?? config("$c.cache_invalidation.checksum_method");

        // accept the deprecated $preMigrationImports and config('...pre_migration_imports') settings
        $propVal = $pb->prop('migrations', null);
        $configVal =
            config("$c.migrations")
            ?? config("$c.build_sources.migrations");
        $migrations = $propVal ?? $configVal;



        $snapshots = $pb->adaptConfig('reuse_methods.snapshots', 'snapshots');

        // accept the deprecated $useSnapshotsWhenNotReusingDB
        // and config('...use_snapshots_when_not_reusing_db') settings
        $snapshotsWhenNotReusingDB = $pb->adaptConfig(
            'use_snapshots_when_not_reusing_db',
            'useSnapshotsWhenNotReusingDB'
        );
        if (!is_null($snapshotsWhenNotReusingDB)) {
            $snapshots = $snapshotsWhenNotReusingDB;
        }

        // accept the deprecated $useSnapshotsWhenReusingDB and config('...use_snapshots_when_reusing_db') settings
        $useSnapshotsWhenReusingDB = $pb->adaptConfig(
            'use_snapshots_when_reusing_db',
            'useSnapshotsWhenReusingDB'
        );
        if (!is_null($useSnapshotsWhenReusingDB)) {
            $snapshots = "!$useSnapshotsWhenReusingDB";
        }

        return (new ConfigDTO())
            ->projectName(config("$c.project_name"))
            ->testName($testName)
            ->connection($connection)
            ->isDefaultConnection(null)
            ->connectionExists(!is_null(config("database.connections.$connection")))
            ->origDatabase($database)
//            ->database($pb->adaptConfig("database.connections.$connection.database"))
            ->databaseModifier($paraTestDBModifier)
            ->storageDir($this->storageDir())
            ->snapshotPrefix('snapshot.')
            ->databasePrefix('')
            ->cacheInvalidationEnabled(config("$c.cache_invalidation.enabled"))
            ->cacheInvalidationMethod($cacheInvalidationMethod)
            ->checksumPaths($this->checkLaravelChecksumPaths($cacheInvalidationLocations))
            ->preCalculatedBuildChecksum(null)->buildSettings($initialImports, $migrations, $this->resolveSeeders(), $pb->adaptConfig('remote_build_url', 'remoteBuildUrl'), $pb->prop('isBrowserTest', $this->browserTestDetected), $isParallelTest, $this->usingPest, false, $pb->config('session.driver'), null)->dbAdapterSupport(true, true, true, true, true, true)->cacheTools($transaction, $journal, config("$c.verify_databases"), $scenarios)
            ->snapshots($snapshots)
            ->forceRebuild($this->parallelTestingSaysRebuildDBs())->mysqlSettings(config("$c.database.mysql.executables.mysql"), config("$c.database.mysql.executables.mysqldump"))->postgresSettings(config("$c.database.pgsql.executables.psql"), config("$c.database.pgsql.executables.pg_dump"))
            ->staleGraceSeconds($pb->adaptConfig('stale_grace_seconds'));
    }

    /**
     * Now that the databaseInit(..) method has been run, re-confirm whether the databases exist (because databaseInit
     * might have changed them), and re-pick the origDatabase.
     *
     * @return void
     */
    protected function reCheckIfConnectionsExist()
    {
        foreach ($this->databaseBuilders as $builder) {

            $connection = $builder->getConnection();
            $connectionExists = !is_null(config("database.connections.$connection"));
            $builder->connectionExists($connectionExists);

            $database = '';
            if ($connectionExists) {
                $database = (string) config("database.connections.$connection.database");
                $this->isDatabaseNameOk($database);
            }
            $builder->origDatabase($database);
        }
    }

    /**
     * Double check that the database name is ok.
     *
     * @param string $database The original database name.
     * @return void
     * @throws AdaptBootException
     */
    private function isDatabaseNameOk(string $database)
    {
        if (!mb_strlen($database)) {
            throw AdaptBootException::databaseNameIsInvalid($database);
        }
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
     * Look at the seeder properties and config value, and determine what the seeders should be.
     *
     * @return string[]
     */
    private function resolveSeeders(): array
    {
        $c = Settings::LARAVEL_CONFIG_NAME;
        $seeders =
            config("$c.seeders")
            ?? config("$c.build_sources.seeders");

        return LaravelSupport::resolveSeeders(
            $this->propBag->hasProp('seeders'),
            $this->propBag->prop('seeders', null),
            $this->propBag->hasProp('seeder'),
            $this->propBag->prop('seeder', null),
            $this->propBag->hasProp('seed'),
            $this->propBag->prop('seed', null),
            $seeders
        );
    }

    /**
     * Check to see if the --recreate-databases option was added when parallel testing.
     *
     * @return boolean
     */
    private function parallelTestingSaysRebuildDBs(): bool
    {
        if (!class_exists(ParallelTesting::class)) {
            return false;
        }

        return (bool) ParallelTesting::option('recreate_databases');
    }



    /**
     * Record the list of connections and their databases with the framework.
     *
     * @param array<string,string> $connectionDatabases The connections and the databases created for them.
     * @return void
     */
    protected function registerPreparedConnectionDBsWithFramework($connectionDatabases)
    {
        LaravelSupport::registerScoped(
            Settings::REMOTE_SHARE_CONNECTIONS_SINGLETON_NAME,
            function () use ($connectionDatabases) {
                return $connectionDatabases;
            }
        );
    }



    /**
     * Store the current config in the filesystem temporarily, and get the browsers refer to it in a cookie.
     *
     * @param Browser[]             $browsers      The browsers to update with the current config.
     * @param array<string, string> $connectionDBs The list of connections that have been prepared, and their
     *                                             corresponding databases from the framework.
     * @return void
     */
    public function haveBrowsersShareConfig($browsers, $connectionDBs)
    {
        if (!count($browsers)) {
            return;
        }

        $this->sharableConfigPaths[] = $sharableConfigPath = $this->storeSharableConfig();

        $remoteShareDTO = (new RemoteShareDTO())
            ->sharableConfigFile($sharableConfigPath)
            ->connectionDBs($connectionDBs);

        foreach ($browsers as $browser) {

            // make a small request first, so that cookies can then be set
            // (the browser will reject new cookies before it's loaded a webpage).
            if (!$this->hasTestSitePageLoaded($browser)) {
                $browser->visit(
                    LaravelSupport::configString('app.url') . Settings::INITIAL_BROWSER_COOKIE_REQUEST_PATH
                );
            }

            $browser->addCookie(Settings::REMOTE_SHARE_KEY, $remoteShareDTO->buildPayload(), null, [], false);
        }
    }

    /**
     * Check if the browser has a page of this website open.
     *
     * @param Browser $browser The browser to check.
     * @return boolean
     */
    private function hasTestSitePageLoaded(Browser $browser)
    {
        $currentURL = $browser->driver->getCurrentURL();
        $siteHost = LaravelSupport::configString('app.url');
        return mb_substr($currentURL, 0, mb_strlen($siteHost)) === $siteHost;
    }

    /**
     * Store the current config in a new sharable config file, and return its filename.
     *
     * @return string
     * @throws AdaptBrowserTestException When the sharable config file could not be saved.
     */
    private function storeSharableConfig(): string
    {
        $dateTime = (new DateTime('now', new DateTimeZone('UTC')))->format('YmdHis');
        $rand = md5(uniqid((string) mt_rand(), true));
        $filename = "config.$dateTime.$rand.php";
        $path = Settings::shareConfigDir($this->storageDir(), $filename);

        /** @var Repository $config */
        $config = config();

        $content = '<?php' . PHP_EOL
            . 'return ' . var_export($config->all(), true) . ';'
            . PHP_EOL;

        if (!(new Filesystem())->writeFile($path, 'w', $content)) {
            throw AdaptBrowserTestException::sharableConfigFileNotSaved($path);
        }
        return $path;
    }

    /**
     * Perform any clean-up needed after the test has finished.
     *
     * @return void
     */
    public function runPostTestCleanUp()
    {
        // remove the sharable config files that were created in this test run (if this is a browser test)
        foreach ($this->sharableConfigPaths as $path) {
            @unlink($path);
        }
    }



    /**
     * Remove stale databases, snapshots and orphaned config files.
     *
     * @param string[] $purgeConnections      The connections to purge stale databases from.
     * @param boolean  $purgeSnapshots        Whether to purge stale snapshot files or not.
     * @param boolean  $removeOrphanedConfigs Whether to remove orphaned sharable config files or not.
     * @return boolean
     */
    protected function performPurgeOfStaleThings(
        $purgeConnections,
        $purgeSnapshots,
        $removeOrphanedConfigs
    ): bool {

        if (!$this->canPurgeStaleThings()) {
            return false;
        }

        if (!$this->getMutexLock(Settings::baseStorageDir($this->storageDir(), "purge-lock"))) {
            return false;
        }

        $logTimer = $this->log->newTimer();
        $this->log->vDebug('Looking for stale things to remove');

        $removedCount = $this->purgeStaleDatabases($purgeConnections);
        $removedCount += $this->purgeStaleSnapshots($purgeSnapshots);
        $removedCount += $this->removeOrphanedSharableConfigFiles($removeOrphanedConfigs);

        $message = $removedCount
            ? 'Total time taken for removal'
            : 'Nothing found to remove - total time taken';
        $this->log->vDebug($message, $logTimer, true);

        $this->releaseMutexLock();

        return true;
    }

    /**
     * Work out if stale things are allowed to be purged.
     *
     * @return boolean
     */
    protected function canPurgeStaleThings(): bool
    {
        $c = Settings::LARAVEL_CONFIG_NAME;

        if (config("$c.cache_invalidation.enabled") === false) {
            return false;
        }

        if (config("$c.remote_build_url")) {
            return false;
        }

        return (bool) (config("$c.remove_stale_things") ?? config("$c.cache_invalidation.purge_stale") ?? true);
    }

    /**
     * Remove stale databases.
     *
     * @param string[] $purgeConnections The connections to purge stale databases from.
     * @return integer
     */
    private function purgeStaleDatabases(array $purgeConnections): int
    {
        $removedCount = 0;

        foreach ($purgeConnections as $connection) {

            $logTimer = $this->log->newTimer();

            try {
                $builder = $this->newDatabaseBuilderFromConnection((string) $connection, false);
            } catch (Throwable $exception) {

                // this is expected, as connections will probably exist where the database can't be connected to
                // e.g. other connections that aren't intended to be used. e.g. 'sqlsrv'
                $driver = $this->propBag->config("database.connections.$connection.driver");
                $connDetails = "(connection: \"$connection\", driver: \"$driver\")";
                $this->log->vvWarning("Could not retrieve database list $connDetails", $logTimer);

                continue;
            }

            foreach ($builder->buildDatabaseMetaInfos() as $dbMetaInfo) {

                if (!$dbMetaInfo->shouldPurgeNow()) {
                    continue;
                }

                $logTimer2 = $this->log->newTimer();

                $connDetails = "(connection: \"$dbMetaInfo->connection\", driver: \"$dbMetaInfo->driver\")";
                $dbMsg = "stale database - \"$dbMetaInfo->name\" $connDetails";

                try {
                    if ($dbMetaInfo->delete()) {
                        $removedCount++;
                        $this->log->vvDebug("Removed $dbMsg", $logTimer2);
                    }
                } catch (Throwable $e) {
//                    $this->log->vError("Could not remove $dbMsg", $logTimer2);
                    Exceptions::logException($this->log, $e);
                }
            }
        }
        return $removedCount;
    }

    /**
     * Remove stale snapshots.
     *
     * @param boolean $purgeSnapshots Whether to purge stale snapshot files or not.
     * @return integer
     */
    private function purgeStaleSnapshots(bool $purgeSnapshots): int
    {
        if (!$purgeSnapshots) {
            return 0;
        }

        $builder = $this->newDefaultDatabaseBuilder(false);
        $removedCount = 0;
        foreach ($builder->buildSnapshotMetaInfos() as $snapshotMetaInfo) {

            if (!$snapshotMetaInfo->shouldPurgeNow()) {
                continue;
            }

            $logTimer = $this->log->newTimer();

            try {
                if ($snapshotMetaInfo->delete()) {
                    $removedCount++;
                    $this->log->vvDebug("Removed stale snapshot \"$snapshotMetaInfo->path\"", $logTimer);
                }
            } catch (Throwable $e) {
//                $this->log->vError("Could not remove stale snapshot \"$snapshotMetaInfo->path\"", $logTimer);
                Exceptions::logException($this->log, $e);
            }
        }
        return $removedCount;
    }

    /**
     * Remove old (i.e. orphaned) sharable config files.
     *
     * @param boolean $removeOrphanedConfigs Whether to remove orphaned sharable config files or not.
     * @return integer
     */
    private function removeOrphanedSharableConfigFiles(bool $removeOrphanedConfigs): int
    {
        if (!$removeOrphanedConfigs) {
            return 0;
        }

        $removedCount = 0;

        $dir = Settings::shareConfigDir($this->storageDir());

        $nowUTC = new DateTime('now', new DateTimeZone('UTC'));
        $paths = (new Filesystem())->filesInDir($dir);
        foreach ($paths as $path) {

            $filename = mb_substr($path, mb_strlen($dir));
            $createdAtUTC = $this->detectConfigCreatedAt($filename);

            if (!$createdAtUTC) {
                continue;
            }

            // remove if older than 8 hours
            $purgeAfterUTC = (clone $createdAtUTC)->add(new DateInterval("PT8H"));
            if ($purgeAfterUTC <= $nowUTC) {
                @unlink($path);
                $this->log->vDebug("Removed orphaned sharable config file \"$filename\"");
                $removedCount++;
            }
        }

        return $removedCount;
    }

    /**
     * Look at a sharable config file's name and determine when it was created.
     *
     * @param string $filename The name of the sharable config file.
     * @return DateTime|null
     */
    private function detectConfigCreatedAt(string $filename)
    {
        if (!preg_match('/^config\.([0-9]{14})\.[0-9a-z]{32}\.php$/', $filename, $matches)) {
            return null;
        }

        $createdAtUTC = DateTime::createFromFormat('YmdHis', $matches[1], new DateTimeZone('UTC'));
        return $createdAtUTC ?: null;
    }
}
