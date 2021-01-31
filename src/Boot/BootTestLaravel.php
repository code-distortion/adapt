<?php

namespace CodeDistortion\Adapt\Boot;

use CodeDistortion\Adapt\Boot\Traits\CheckLaravelHashPathsTrait;
use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DI\Injectable\Exec;
use CodeDistortion\Adapt\DI\Injectable\Filesystem;
use CodeDistortion\Adapt\DI\Injectable\LaravelArtisan;
use CodeDistortion\Adapt\DI\Injectable\LaravelConfig;
use CodeDistortion\Adapt\DI\Injectable\LaravelDB;
use CodeDistortion\Adapt\DI\Injectable\LaravelLog;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Exceptions\AdaptBootException;
use CodeDistortion\Adapt\Exceptions\AdaptBrowserTestException;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use CodeDistortion\Adapt\Support\Settings;
use Config;
use Carbon\Carbon;
use Laravel\Dusk\Browser;

/**
 * Bootstrap Adapt for Laravel tests.
 */
class BootTestLaravel extends BootTestAbstract
{
    use CheckLaravelHashPathsTrait;

    /** @var string[] The paths to the temporary config files, created during browser tests. */
    private $tempConfigPaths = [];



    /**
     * Build a default DIContainer object.
     *
     * @param string $connection The connection to start using.
     * @return DIContainer
     * @throws AdaptBootException Thrown when a PropBag hasn't been set yet.
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
            ->log(new LaravelLog(
                (bool) $this->propBag->config('log.stdout'),
                (bool) $this->propBag->config('log.laravel')
            ))
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
        return $this->newBuilder(config('database.default'));
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
        if (!config("database.connections.$connection")) {
            throw AdaptConfigException::invalidConnection($connection);
        }
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
        $config = $this->newConfigDTO($connection);

        // @todo - work out how to inject the DIContainer
        // - clone the one that was passed in? pass in a closure to create one?
        $di = $this->defaultDI($connection);

        $pickDriverClosure = function (string $connection) {
            return config("database.connections.$connection.driver", 'unknown');
        };

//        return new DatabaseBuilder('laravel', $this->testName, $this->di, $config, $pickDriverClosure);
        return new DatabaseBuilder('laravel', (string) $this->testName, $di, $config, $pickDriverClosure);
    }

    /**
     * Create a new ConfigDTO object with default values.
     *
     * @param string $connection The connection to use.
     * @return ConfigDTO
     * @throws AdaptBootException Thrown when a PropBag hasn't been set yet.
     */
    private function newConfigDTO(string $connection): configDTO
    {
        if (!$this->propBag) {
            throw AdaptBootException::propBagNotSet();
        }

        $paraTestDBModifier = (string) getenv('TEST_TOKEN');

        return (new ConfigDTO())
            ->projectName($this->propBag->config('project_name'))
            ->connection($connection)
            ->database(config("database.connections.$connection.database"))
            ->databaseModifier($paraTestDBModifier)
            ->storageDir($this->storageDir())
            ->snapshotPrefix('snapshot.')
            ->databasePrefix('test_')
            ->hashPaths($this->checkLaravelHashPaths($this->propBag->config('look_for_changes_in')))
            ->buildSettings(
                $this->propBag->config('pre_migration_imports', 'preMigrationImports'),
                $this->propBag->config('migrations', 'migrations'),
                $this->propBag->config('seeders', 'seeders'),
                $this->propBag->prop('isBrowserTest', $this->browserTestDetected)
            )
            ->cacheTools(
                $this->propBag->config('reuse_test_dbs', 'reuseTestDBs'),
                $this->propBag->config('dynamic_test_dbs', 'dynamicTestDBs'),
                $this->propBag->config('transactions', 'transactions')
            )
            ->snapshots(
                $this->propBag->config('snapshots.enabled', 'snapshotsEnabled'),
                $this->propBag->config('snapshots.take_after_migrations', 'takeSnapshotAfterMigrations'),
                $this->propBag->config('snapshots.take_after_seeders', 'takeSnapshotAfterSeeders')
            )
            ->mysqlSettings(
                $this->propBag->config('database.mysql.executables.mysql'),
                $this->propBag->config('database.mysql.executables.mysqldump')
            )
            ->postgresSettings(
                $this->propBag->config('database.pgsql.executables.psql'),
                $this->propBag->config('database.pgsql.executables.pg_dump')
            );
    }

    /**
     * Get the storage directory.
     *
     * @return string
     */
    private function storageDir(): string
    {
        return $this->propBag
            ? rtrim($this->propBag->config('storage_dir'), '\\/')
            : '';
    }



    /**
     * Store the current config in the filesystem temporarily, and get the browsers refer to it in a cookie.
     *
     * @param Browser[] $browsers The browsers to update with the current config.
     * @return void
     */
    public function getBrowsersToPassThroughCurrentConfig(array $browsers)
    {
        if (!count($browsers)) {
            return;
        }

        $this->tempConfigPaths[] = $tempConfigPath = $this->storeTemporaryConfig();

        foreach ($browsers as $browser) {

            // make a small request first, so that cookies can then be set
            // (the browser will reject new cookies before it's loaded a webpage).
            if (!$this->hasTestSitePageLoaded($browser)) {
                $browser->visit(Settings::INITIAL_BROWSER_REQUEST_PATH);
            }

            $browser->addCookie(
                Settings::CONNECTIONS_COOKIE,
                base64_encode(serialize(['tempConfigPath' => $tempConfigPath])),
                null,
                [],
                false
            );
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
        $siteHost = config('app.url');
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
        $dateTime = Carbon::now()->format('YmdHis');
        $rand = md5(uniqid((string) mt_rand(), true));
        $filename = "config.$dateTime.$rand.php";
        $path = "{$this->storageDir()}/$filename";

        $content = '<?php' . PHP_EOL
            . 'return ' . var_export(Config::all(), true) . ';' . PHP_EOL;

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
    public function cleanUp()
    {
        // remove the temporary config files that were created in this test run (if this is a browser test)
        foreach ($this->tempConfigPaths as $path) {
            @unlink($path);
        }
    }

    /**
     * Remove any old (ie. orphaned) temporary config files.
     *
     * @return void
     */
    public function removeOldTempConfigFiles()
    {
        $nowUTC = Carbon::now();
        $paths = (new Filesystem())->filesInDir($this->storageDir());
        foreach ($paths as $path) {

            $filename = mb_substr($path, mb_strlen($this->storageDir() . '/'));
            $createdAtUTC = $this->detectConfigCreatedAt($filename);

            if (!$createdAtUTC) {
                continue;
            }

            // remove if older than 4 hours
            if ($createdAtUTC->diffInHours($nowUTC, false) >= 4) {
                @unlink($path);
            }
        }
    }

    /**
     * Look at a temporary config file's name and determine when it was created.
     *
     * @param string $filename The name of the temporary config file.
     * @return Carbon|null
     */
    private function detectConfigCreatedAt(string $filename): ?Carbon
    {
        if (!preg_match('/^config\.([0-9]{14})\.[0-9a-z]{32}\.php$/', $filename, $matches)) {
            return null;
        }

        $createdAtUTC = Carbon::createFromFormat('YmdHis', $matches[1], 'UTC');
        return $createdAtUTC ?: null;
    }
}
