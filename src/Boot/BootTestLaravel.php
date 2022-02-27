<?php

namespace CodeDistortion\Adapt\Boot;

use CodeDistortion\Adapt\Boot\Traits\CheckLaravelHashPathsTrait;
use CodeDistortion\Adapt\Boot\Traits\HasMutexTrait;
use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Exec;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Filesystem;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelArtisan;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelConfig;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelDB;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\DTO\RemoteShareDTO;
use CodeDistortion\Adapt\Exceptions\AdaptBootException;
use CodeDistortion\Adapt\Exceptions\AdaptBrowserTestException;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use CodeDistortion\Adapt\Support\Hasher;
use CodeDistortion\Adapt\Support\LaravelSupport;
use CodeDistortion\Adapt\Support\Settings;
use CodeDistortion\Adapt\Support\StorageDir;
use DateInterval;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\ParallelTesting;
use Laravel\Dusk\Browser;
use PDOException;

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
    public function isAllowedToRun(): void
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
     * @throws AdaptBootException Thrown when a PropBagDTO hasn't been set yet.
     */
    protected function defaultDI(string $connection): DIContainer
    {
        if (!$this->propBag) {
            throw AdaptBootException::propBagNotSet();
        }

        return (new DIContainer())
            ->artisan(new LaravelArtisan())
            ->config(new LaravelConfig())
            ->db((new LaravelDB())->useConnection($connection))
            ->dbTransactionClosure($this->transactionClosure)
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
     * @throws AdaptConfigException Thrown when the connection doesn't exist.
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
        $config = $this->newConfigDTO($connection, (string) $this->testName);

        // @todo - work out how to inject the DIContainer
        // - clone the one that was passed in? pass in a closure to create one?
        $di = $this->defaultDI($connection);

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
     * Create a new ConfigDTO object with default values.
     *
     * @param string $connection The connection to use.
     * @param string $testName   The current test's name.
     * @return ConfigDTO
     * @throws AdaptBootException Thrown when a PropBag hasn't been set yet.
     */
    private function newConfigDTO(string $connection, string $testName): configDTO
    {
        if (!$this->propBag) {
            throw AdaptBootException::propBagNotSet();
        }

        $paraTestDBModifier = (string) getenv('TEST_TOKEN');

        return (new ConfigDTO())
            ->projectName($this->propBag->adaptConfig('project_name'))
            ->testName($testName)
            ->connection($connection)
            ->connectionExists(!is_null(config("database.connections.$connection")))
            ->database(config("database.connections.$connection.database"))
            ->databaseModifier($paraTestDBModifier)
            ->storageDir($this->storageDir())
            ->snapshotPrefix('snapshot.')
            ->databasePrefix('')
            ->checkForSourceChanges($this->propBag->adaptConfig('check_for_source_changes'))
            ->hashPaths($this->checkLaravelHashPaths($this->propBag->adaptConfig('look_for_changes_in')))
            ->preCalculatedBuildHash(null)
            ->buildSettings(
                $this->propBag->adaptConfig('pre_migration_imports', 'preMigrationImports'),
                $this->propBag->adaptConfig('migrations', 'migrations'),
                $this->resolveSeeders(),
                $this->propBag->adaptConfig('remote_build_url', 'remoteBuildUrl'),
                $this->propBag->prop('isBrowserTest', $this->browserTestDetected),
                false,
                $this->propBag->config('session.driver'),
                null,
            )
            ->cacheTools(
                $this->propBag->adaptConfig('reuse_test_dbs', 'reuseTestDBs'),
                $this->propBag->adaptConfig('scenario_test_dbs', 'scenarioTestDBs'),
            )
            ->snapshots(
                $this->propBag->adaptConfig('use_snapshots_when_reusing_db', 'useSnapshotsWhenReusingDB'),
                $this->propBag->adaptConfig('use_snapshots_when_not_reusing_db', 'useSnapshotsWhenNotReusingDB'),
            )
            ->forceRebuild($this->parallelTestingSaysRebuildDBs())
            ->mysqlSettings(
                $this->propBag->adaptConfig('database.mysql.executables.mysql'),
                $this->propBag->adaptConfig('database.mysql.executables.mysqldump'),
            )
            ->postgresSettings(
                $this->propBag->adaptConfig('database.pgsql.executables.psql'),
                $this->propBag->adaptConfig('database.pgsql.executables.pg_dump'),
            )
            ->staleGraceSeconds($this->propBag->adaptConfig(
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
        return $this->propBag
            ? rtrim($this->propBag->adaptConfig('storage_dir'), '\\/')
            : '';
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
     * Determine if the database should be reused or not.
     *
     * @return boolean
     */
    private function shouldReuseTestDB(): bool
    {
        if ($this->parallelTestingSaysRebuildDBs()) {
            return false;
        }
        return $this->propBag->adaptConfig('reuse_test_dbs', 'reuseTestDBs');
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

        $content = '<?php' . PHP_EOL
            . 'return ' . var_export(Config::all(), true) . ';'
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
    public function postTestCleanUp(): void
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
    public function purgeStaleThings(): void
    {
        if (!$this->canPurgeStaleThings()) {
            return;
        }

        if (!$this->getMutexLock("{$this->storageDir()}/purge-lock")) {
            return;
        }

        $this->purgeStaleDatabases();
        $this->purgeStaleSnapshots();
        $this->removeOrphanedTempConfigFiles();

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
     * @return void
     */
    private function purgeStaleDatabases(): void
    {
        $connections = LaravelSupport::configArray('database.connections');
        foreach (array_keys($connections) as $connection) {
            try {
                $builder = $this->createBuilder((string) $connection);
                foreach ($builder->buildDatabaseMetaInfos() as $databaseMetaInfo) {
                    $databaseMetaInfo->purgeIfNeeded();
                }
            } catch (AdaptConfigException $e) {
                // ignore exceptions caused because the database can't be connected to
                // e.g. other connections that aren't intended to be used. e.g. 'pgsql', 'sqlsrv'
            } catch (PDOException $e) {
                // same as above
            }
        }
    }

    /**
     * Remove stale snapshots.
     *
     * @return void
     */
    private function purgeStaleSnapshots(): void
    {
        $builder = $this->createBuilder(LaravelSupport::configString('database.default'));
        foreach ($builder->buildSnapshotMetaInfos() as $snapshotMetaInfo) {
            $snapshotMetaInfo->purgeIfNeeded();
        }
    }

    /**
     * Remove old (i.e. orphaned) temporary config files.
     *
     * @return void
     */
    private function removeOrphanedTempConfigFiles(): void
    {
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
            }
        }
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
