<?php

namespace CodeDistortion\Adapt\Boot;

use CodeDistortion\Adapt\Boot\Traits\CheckLaravelHashPathsTrait;
use CodeDistortion\Adapt\Boot\Traits\HasMutexTrait;
use CodeDistortion\Adapt\DatabaseBuilder;
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
    use CheckLaravelHashPathsTrait;
    use HasMutexTrait;

    /** @var string[] The paths to the temporary config files, created during browser tests. */
    private array $tempConfigPaths = [];



    /**
     * Check that it's safe to run.
     *
     * @return void
     * @throws AdaptConfigException When the .env.testing file wasn't used to build the environment.
     */
    protected function isAllowedToRun(): void
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
    private function ensureEnvTestingFileExists(): void
    {
        if ((new Filesystem())->fileExists('.env.testing')) {
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
    public function ensureReCreateDatabasesIsntSet(): void
    {
        if (!$this->parallelTestingSaysRebuildDBs()) {
            return;
        }

        throw AdaptBootException::parallelTestingSaysRebuildDBs();
    }





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
     * Build a default DIContainer object.
     *
     * @param string $connection The connection to start using.
     * @return DIContainer
     * @throws AdaptBootException When a PropBagDTO hasn't been set yet.
     */
    protected function defaultDI(string $connection): DIContainer
    {
        return (new DIContainer())
            ->artisan(new LaravelArtisan())
            ->db((new LaravelDB())->useConnection($connection))
            ->log($this->log)
            ->exec(new Exec())
            ->filesystem(new Filesystem());
    }

    /**
     * Create a new DatabaseBuilder object based on the "default" database connection.
     *
     * @return DatabaseBuilder
     */
    protected function newDefaultBuilder(): DatabaseBuilder
    {
        return $this->newBuilder(LaravelSupport::configString('database.default'));
    }

    /**
     * Create a new DatabaseBuilder object, and add it to the list to execute later.
     *
     * @param string $connection The database connection to prepare.
     * @return DatabaseBuilder
     * @throws AdaptConfigException When the connection doesn't exist.
     */
    public function newBuilder(string $connection): DatabaseBuilder
    {
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
        $configDTO = $this->newConfigDTO($connection, $this->testName);

        // @todo - work out how to inject the DIContainer
        // - clone the one that was passed in? pass in a closure to create one?
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
     * Create a new ConfigDTO object with default values.
     *
     * @param string $connection The connection to use.
     * @param string $testName   The current test's name.
     * @return ConfigDTO
     * @throws AdaptBootException When the database name isn't valid.
     */
    private function newConfigDTO(string $connection, string $testName): configDTO
    {
        $paraTestDBModifier = (string) getenv('TEST_TOKEN');
        $isParallelTest = mb_strlen($paraTestDBModifier) > 0;

        $c = Settings::LARAVEL_CONFIG_NAME;
        $pb = $this->propBag;

        // accept the deprecated $reuseTestDBs and config('...reuse_test_dbs') settings
        $propVal = $pb->prop('reuseTestDBs', null) ?? $pb->prop('reuseTransaction', null);
        $configVal = config("$c.reuse_test_dbs") ?? config("$c.reuse.transactions");
        $reuseTransaction = $propVal ?? $configVal;

        $database = $pb->config("database.connections.$connection.database");
        if (!mb_strlen($database)) {
            throw AdaptBootException::databaseNameNotAString($database);
        }

        return (new ConfigDTO())
            ->projectName($pb->adaptConfig('project_name'))
            ->testName($testName)
            ->connection($connection)
            ->connectionExists(!is_null(config("database.connections.$connection")))
            ->origDatabase($database)
//            ->database($pb->adaptConfigString("database.connections.$connection.database"))
            ->databaseModifier($paraTestDBModifier)
            ->storageDir($this->storageDir())
            ->snapshotPrefix('snapshot.')
            ->databasePrefix('')
            ->checkForSourceChanges($pb->adaptConfig('check_for_source_changes'))
            ->hashPaths($this->checkLaravelHashPaths($pb->adaptConfig('look_for_changes_in')))
            ->preCalculatedBuildHash(null)
            ->buildSettings(
                $pb->adaptConfig('pre_migration_imports', 'preMigrationImports'),
                $pb->adaptConfig('migrations', 'migrations'),
                $this->resolveSeeders(),
                $pb->adaptConfig('remote_build_url', 'remoteBuildUrl'),
                $pb->prop('isBrowserTest', $this->browserTestDetected),
                $isParallelTest,
                false,
                $pb->config('session.driver'),
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
                $reuseTransaction,
                $pb->adaptConfig('reuse.journals', 'reuseJournal'),
                $pb->adaptConfig('verify_databases'),
                $pb->adaptConfig('scenario_test_dbs', 'scenarioTestDBs'),
            )
            ->snapshots(
                $pb->adaptConfig('use_snapshots_when_reusing_db', 'useSnapshotsWhenReusingDB'),
                $pb->adaptConfig('use_snapshots_when_not_reusing_db', 'useSnapshotsWhenNotReusingDB'),
            )
            ->forceRebuild($this->parallelTestingSaysRebuildDBs())
            ->mysqlSettings(
                $pb->adaptConfig('database.mysql.executables.mysql'),
                $pb->adaptConfig('database.mysql.executables.mysqldump'),
            )
            ->postgresSettings(
                $pb->adaptConfig('database.pgsql.executables.psql'),
                $pb->adaptConfig('database.pgsql.executables.pg_dump'),
            )
            ->staleGraceSeconds($pb->adaptConfig(
                'stale_grace_seconds',
                null,
                Settings::DEFAULT_STALE_GRACE_SECONDS,
            ));
    }

    /**
     * Get the storage directory.
     *
     * @return string
     */
    private function storageDir(): string
    {
        return rtrim($this->propBag->adaptConfig('storage_dir'), '\\/');
    }

    /**
     * Look at the seeder properties and config value, and determine what the seeders should be.
     *
     * @return string[]
     */
    private function resolveSeeders(): array
    {
        return LaravelSupport::resolveSeeders(
            $this->propBag->hasProp('seeders'),
            $this->propBag->prop('seeders', null),
            $this->propBag->hasProp('seed'),
            $this->propBag->prop('seed', null),
            config(Settings::LARAVEL_CONFIG_NAME . '.seeders')
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
    protected function registerPreparedConnectionDBsWithFramework(array $connectionDatabases): void
    {
        LaravelSupport::registerPreparedConnectionDBsWithFramework($connectionDatabases);
    }



    /**
     * Store the current config in the filesystem temporarily, and get the browsers refer to it in a cookie.
     *
     * @param Browser[]             $browsers      The browsers to update with the current config.
     * @param array<string, string> $connectionDBs The list of connections that have been prepared, and their
     *                                             corresponding databases from the framework.
     * @return void
     */
    public function haveBrowsersShareConfig(array $browsers, array $connectionDBs): void
    {
        if (!count($browsers)) {
            return;
        }

        $this->tempConfigPaths[] = $tempConfigPath = $this->storeTemporaryConfig();

        $remoteShareDTO = (new RemoteShareDTO())
            ->tempConfigFile($tempConfigPath)
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
     * Store the current config in a new temporary config file, and return its filename.
     *
     * @return string
     * @throws AdaptBrowserTestException When the temporary config file could not be saved.
     */
    private function storeTemporaryConfig(): string
    {
        $dateTime = (new DateTime('now', new DateTimeZone('UTC')))->format('YmdHis');
        $rand = md5(uniqid((string) mt_rand(), true));
        $filename = "config.$dateTime.$rand.php";
        $path = "{$this->storageDir()}/$filename";

        /** @var Repository $config */
        $config = config();

        $content = '<?php' . PHP_EOL
            . 'return ' . var_export($config->all(), true) . ';'
            . PHP_EOL;

        if (!(new Filesystem())->writeFile($path, 'w', $content)) {
            throw AdaptBrowserTestException::tempConfigFileNotSaved($path);
        }
        return $path;
    }

    /**
     * Perform any clean-up needed after the test has finished.
     *
     * @return void
     */
    public function runPostTestCleanUp(): void
    {
        // remove the temporary config files that were created in this test run (if this is a browser test)
        foreach ($this->tempConfigPaths as $path) {
            @unlink($path);
        }
    }



    /**
     * Remove stale databases, snapshots and orphaned config files.
     *
     * @return void
     */
    public function performPurgeOfStaleThings(): void
    {
        if (!$this->canPurgeStaleThings()) {
            return;
        }

        if (!$this->getMutexLock("{$this->storageDir()}/purge-lock")) {
            return;
        }

        $logTimer = $this->log->newTimer();
        $this->log->vDebug('Looking for stale things to remove');

        $removedCount = $this->purgeStaleDatabases();
        $removedCount += $this->purgeStaleSnapshots();
        $removedCount += $this->removeOrphanedTempConfigFiles();

        $message = $removedCount
            ? 'Total time taken for removal'
            : 'Nothing found to remove - total time taken';
        $this->log->vDebug($message, $logTimer, true);

        $this->releaseMutexLock();
    }

    /**
     * Work out if stale things are allowed to be purged.
     *
     * @return boolean
     */
    protected function canPurgeStaleThings(): bool
    {
        if ($this->propBag->adaptConfig('remote_build_url')) {
            return false;
        }
        return (bool) $this->propBag->adaptConfig('remove_stale_things', null, true);
    }

    /**
     * Remove stale databases.
     *
     * @return integer
     */
    private function purgeStaleDatabases(): int
    {
        $removedCount = 0;

        $connections = LaravelSupport::configArray('database.connections');
        foreach (array_keys($connections) as $connection) {

            $logTimer = $this->log->newTimer();

            try {
                $builder = $this->createBuilder((string) $connection);
            } catch (Throwable $e) {

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
     * @return integer
     */
    private function purgeStaleSnapshots(): int
    {
        $builder = $this->createBuilder(LaravelSupport::configString('database.default'));
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
     * Remove old (i.e. orphaned) temporary config files.
     *
     * @return integer
     */
    private function removeOrphanedTempConfigFiles(): int
    {
        $removedCount = 0;

        $nowUTC = new DateTime('now', new DateTimeZone('UTC'));
        $paths = (new Filesystem())->filesInDir($this->storageDir());
        foreach ($paths as $path) {

            $filename = mb_substr($path, mb_strlen($this->storageDir() . '/'));
            $createdAtUTC = $this->detectConfigCreatedAt($filename);

            if (!$createdAtUTC) {
                continue;
            }

            // remove if older than 8 hours
            $purgeAfterUTC = (clone $createdAtUTC)->add(new DateInterval("PT8H"));
            if ($purgeAfterUTC <= $nowUTC) {
                @unlink($path);
                $this->log->vDebug("Removed orphaned temporary config file \"$filename\"");
                $removedCount++;
            }
        }

        return $removedCount;
    }

    /**
     * Look at a temporary config file's name and determine when it was created.
     *
     * @param string $filename The name of the temporary config file.
     * @return DateTime|null
     */
    private function detectConfigCreatedAt(string $filename): ?DateTime
    {
        if (!preg_match('/^config\.([0-9]{14})\.[0-9a-z]{32}\.php$/', $filename, $matches)) {
            return null;
        }

        $createdAtUTC = DateTime::createFromFormat('YmdHis', $matches[1], new DateTimeZone('UTC'));
        return $createdAtUTC ?: null;
    }
}
