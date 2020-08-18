<?php

namespace CodeDistortion\Adapt;

use CodeDistortion\Adapt\Adapters\DBAdapter;
use CodeDistortion\Adapt\Adapters\LaravelMySQLAdapter;
use CodeDistortion\Adapt\Adapters\LaravelSQLiteAdapter;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\DTO\DatabaseMetaDTO;
use CodeDistortion\Adapt\DTO\SnapshotMetaDTO;
use CodeDistortion\Adapt\Exceptions\AdaptBuildException;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use CodeDistortion\Adapt\Exceptions\AdaptSnapshotException;
use CodeDistortion\Adapt\Support\Hasher;
use Throwable;

/**
 * Build a database ready for use in tests.
 */
class DatabaseBuilder
{
    /**
     * The framework currently being used.
     *
     * @var string
     */
    protected string $framework;

    /**
     * The name of the current test.
     *
     * @var string
     */
    protected string $testName;

    /**
     * The available database adapters.
     *
     * @var string[][]
     */
    private array $availableDBAdapters = [
        'laravel' => [
            'mysql' => LaravelMySQLAdapter::class,
            'sqlite' => LaravelSQLiteAdapter::class,
//            'pgsql' => LaravelPostgreSQLAdapter::class,
        ],
    ];

    /**
     * The dependency-injection container to use.
     *
     * @var DIContainer
     */
    private DIContainer $di;

    /**
     * A DTO containing the settings to use.
     *
     * @var ConfigDTO
     */
    private ConfigDTO $config;

    /**
     * The closure to call to get the driver for a connection.
     *
     * @var callable
     */
    private $pickDriverClosure;

    /**
     * Builds and checks hashes.
     *
     * @var Hasher
     */
    private Hasher $hasher;


    /**
     * Whether this builder has been executed yet or not.
     *
     * @var boolean
     */
    private bool $executed = false;

    /**
     * Whether or not old snapshots have been cleaned up yet. Happens once per test-run.
     *
     * @var boolean[]
     */
    private static array $removedSnapshots = [];

    /**
     * Whether or not old databases have been cleaned up yet. Happens once per database.
     *
     * @var boolean[][][]
     */
    private static array $removedOldDatabases = [];

    /**
     * The object that will do the database specific work.
     *
     * @var DBAdapter|null
     */
    private ?DBAdapter $dbAdapter = null;

    /**
     * Whether this is the first test being run in the suite or not.
     *
     * @var boolean
     */
    private static bool $firstRun = true;


    /**
     * Constructor.
     *
     * @param string      $framework         The framework currently being used.
     * @param string      $testName          The name of the test being run.
     * @param DIContainer $di                The dependency-injection container to use.
     * @param ConfigDTO   $config            A DTO containing the settings to use.
     * @param callable    $pickDriverClosure A closure that will return the driver for the given connection.
     */
    public function __construct(
        string $framework,
        string $testName,
        DIContainer $di,
        ConfigDTO $config,
        callable $pickDriverClosure
    ) {
        $this->framework = $framework;
        $this->testName = $testName;
        $this->di = $di;
        $this->config = $config;
        $this->pickDriverClosure = $pickDriverClosure;

        $this->hasher = new Hasher($di, $config);
    }


    /**
     * Reset anything that should be reset between internal tests of the Adapt package.
     *
     * @return void
     */
    public static function resetStaticProps(): void
    {
        static::$removedSnapshots = static::$removedOldDatabases = [];
        static::$firstRun = true;
    }


//    /**
//     * Specify the database connection to prepare.
//     *
//     * @param string $connection The database connection to prepare.
//     * @return static
//     */
//    public function connection(string $connection): self
//    {
//        $this->config->connection($connection);
//        return $this;
//    }

    /**
     * Specify the database dump files to import before migrations run.
     *
     * @param string[]|string[][] $preMigrationImports The database dump files to import, one per database type.
     * @return static
     */
    public function preMigrationImports(array $preMigrationImports = []): self
    {
        $this->config->preMigrationImports($preMigrationImports);
        return $this;
    }

    /**
     * Specify that no database dump files will be imported before migrations run.
     *
     * @return static
     */
    public function noPreMigrationImports(): self
    {
        $this->config->preMigrationImports([]);
        return $this;
    }

    /**
     * Turn migrations on (or off), or specify the location of the migrations to run.
     *
     * @param boolean|string $migrations Should the migrations be run? / the path of the migrations to run.
     * @return static
     */
    public function migrations($migrations = true): self
    {
        $this->config->migrations($migrations);
        return $this;
    }

    /**
     * Turn migrations off.
     *
     * @return static
     */
    public function noMigrations(): self
    {
        $this->config->migrations(false);
        return $this;
    }

    /**
     * Specify the seeders to run.
     *
     * @param string[] $seeders The seeders to run after migrating.
     * @return static
     */
    public function seeders(array $seeders): self
    {
        $this->config->seeders($seeders);
        return $this;
    }

    /**
     * Turn seeders off.
     *
     * @return static
     */
    public function noSeeders(): self
    {
        $this->config->seeders([]);
        return $this;
    }

    /**
     * Set the types of cache to use.
     *
     * @param boolean $reuseTestDBs   Reuse databases when possible (instead of rebuilding them)?.
     * @param boolean $dynamicTestDBs Create databases as needed for the database-scenario?.
     * @param boolean $transactions   Should tests be encapsulated within transactions?.
     * @return static
     */
    public function cacheTools(bool $reuseTestDBs, bool $dynamicTestDBs, bool $transactions): self
    {
        $this->config->cacheTools($reuseTestDBs, $dynamicTestDBs, $transactions);
        return $this;
    }

    /**
     * Turn the reuse-test-dbs setting on (or off).
     *
     * @param boolean $reuseTestDBs Reuse existing databases?.
     * @return static
     */
    public function reuseTestDBs(bool $reuseTestDBs = true): self
    {
        $this->config->reuseTestDBs($reuseTestDBs);
        return $this;
    }

    /**
     * Turn the reuse-test-dbs setting off.
     *
     * @return static
     */
    public function noReuseTestDBs(): self
    {
        $this->config->reuseTestDBs(false);
        return $this;
    }

    /**
     * Turn the dynamic-test-dbs setting on (or off).
     *
     * @param boolean $dynamicTestDBs Create databases as needed for the database-scenario?.
     * @return static
     */
    public function dynamicTestDBs(bool $dynamicTestDBs = true): self
    {
        $this->config->dynamicTestDBs($dynamicTestDBs);
        return $this;
    }

    /**
     * Turn the dynamic-test-dbs setting off.
     *
     * @return static
     */
    public function noDynamicTestDBs(): self
    {
        $this->config->dynamicTestDBs(false);
        return $this;
    }

    /**
     * Turn transactions on or off.
     *
     * @param boolean $transactions Should tests be encapsulated within transactions?.
     * @return static
     */
    public function transactions(bool $transactions = true): self
    {
        $this->config->transactions($transactions);
        return $this;
    }

    /**
     * Turn transactions off.
     *
     * @return static
     */
    public function noTransactions(): self
    {
        $this->config->transactions(false);
        return $this;
    }

    /**
     * Turn the snapshots setting on.
     *
     * @param boolean $takeSnapshotAfterMigrations Take a snapshot of the database after migrations have been run?.
     * @param boolean $takeSnapshotAfterSeeders    Take a snapshot of the database after seeders have been run?.
     * @return static
     */
    public function snapshots(bool $takeSnapshotAfterMigrations = false, bool $takeSnapshotAfterSeeders = true): self
    {
        $this->config->snapshots(true, $takeSnapshotAfterMigrations, $takeSnapshotAfterSeeders);
        return $this;
    }

    /**
     * Turn the snapshots setting off.
     *
     * @return static
     */
    public function noSnapshots(): self
    {
        $this->config->snapshots(false, false, false);
        return $this;
    }

    /**
     * Turn the is-browser-test setting on (or off).
     *
     * @param boolean $isBrowserTest Is this test a browser-test?.
     * @return static
     */
    public function isBrowserTest(bool $isBrowserTest = true): self
    {
        $this->config->isBrowserTest($isBrowserTest);
        return $this;
    }

    /**
     * Turn the is-browser-test setting off.
     *
     * @return static
     */
    public function isNotBrowserTest(): self
    {
        $this->config->isBrowserTest(false);
        return $this;
    }

    /**
     * Set the this builder's database connection to be the "default" one.
     *
     * @return static
     */
    public function makeDefault(): self
    {
        $this->dbAdapter()->connection->makeThisConnectionDefault();
        return $this;
    }


    /**
     * Retrieve the connection being used.
     *
     * @return string
     */
    public function getConnection(): string
    {
        return $this->config->connection;
    }

    /**
     * Retrieve the name of the database being used.
     *
     * @return string
     */
    public function getDatabase(): string
    {
        if (!$this->config->database) {
            $this->pickDatabaseNameAndUse();
        }
        return (string) $this->config->database;
    }


    /**
     * Retrieve the database-driver being used.
     *
     * @return string
     */
    public function getDriver(): string
    {
        return $this->pickDriver();
    }


    /**
     * Build a database ready to test with - migrate and seed the database etc (only if it's not ready-to-go already).
     *
     * @return static
     */
    public function execute(): self
    {
        $this->onlyExecuteOnce();
        $this->initialise();
        $this->prepareDB();
        return $this;
    }

    /**
     * Return whether this object has been executed yet.
     *
     * @return boolean
     */
    public function hasExecuted(): bool
    {
        return $this->executed;
    }


    /**
     * Make sure this object is only "executed" once.
     *
     * @return void
     * @throws AdaptBuildException Thrown when this object is executed more than once.
     */
    private function onlyExecuteOnce(): void
    {
        if ($this->hasExecuted()) {
            throw AdaptBuildException::databaseBuilderAlreadyExecuted();
        }
        $this->executed = true;
    }

    /**
     * Resolve whether reuseTestDBs is to be used.
     *
     * @return boolean
     */
    private function usingReuseTestDBs(): bool
    {
        return (($this->config->reuseTestDBs) && ($this->config->transactions) && (!$this->config->isBrowserTest));
    }

    /**
     * Resolve whether the database being created can be reused later.
     *
     * @return boolean
     */
    private function dbWillBeReusable(): bool
    {
        return (($this->config->transactions) && (!$this->config->isBrowserTest));
    }

    /**
     * Resolve whether dynamicTestDBs is to be used.
     *
     * @return boolean
     */
    private function usingDynamicTestDBs(): bool
    {
        return (($this->config->dynamicTestDBs) && (!$this->config->isBrowserTest));
    }

    /**
     * Resolve whether transactions are to be used.
     *
     * @return boolean
     */
    private function usingTransactions(): bool
    {
        return (($this->config->transactions) && (!$this->config->isBrowserTest));
    }

    /**
     * Resolve whether seeding is allowed.
     *
     * @return boolean
     */
    private function seedingIsAllowed(): bool
    {
        return $this->config->migrations !== false;
    }

    /**
     * Derive if a snapshot should be taken after the migrations have been run.
     *
     * @return boolean
     */
    private function shouldTakeSnapshotAfterMigrations(): bool
    {
        if (!$this->config->snapshotsEnabled) {
            return false;
        }

        // take in to consideration when there are no seeders to run, but a snapshot should be taken after seeders
        $seeders = $this->config->pickSeedersToInclude();
        return (count($seeders))
            ? $this->config->takeSnapshotAfterMigrations
            : $this->config->takeSnapshotAfterMigrations || $this->config->takeSnapshotAfterSeeders;
    }

    /**
     * Derive if a snapshot should be taken after the seeders have been run.
     *
     * @return boolean
     */
    private function shouldTakeSnapshotAfterSeeders(): bool
    {
        if (!$this->config->snapshotsEnabled) {
            return false;
        }

        $seeders = $this->config->pickSeedersToInclude();
        return ((count($seeders)) && ($this->config->takeSnapshotAfterSeeders));
    }

    /**
     * Initialise this object ready for running.
     *
     * @return void
     */
    private function initialise(): void
    {
        $this->ensureStorageDirExists();
        $this->pickDriver();

        if (static::$firstRun) {
            static::$firstRun = false;

            $this->di->log->info('==== Adapt initialisation ================');

            Hasher::resetStaticProps();
            $this->hasher->generateSourceFilesHash();
            $this->removeSnapshots(true, false);
        }
    }

    /**
     * Reuse the existing database, populate it from a snapshot or build it from scratch - whatever is necessary.
     *
     * @return void
     */
    private function prepareDB(): void
    {
        $this->di->log->info('---- Preparing a database for test: '.$this->testName.' ----------------');
        $this->di->log->info('Using connection "'.$this->config->connection.'" (driver "'.$this->config->driver.'")');

        $this->removeDatabases(true, true, false);
        $this->pickDatabaseNameAndUse();
        $this->buildOrReuseDB();
        if ($this->usingTransactions()) {
            $this->dbAdapter()->build->applyTransaction();
        }
    }

    /**
     * Use the desired database.
     *
     * @return void
     */
    private function pickDatabaseNameAndUse(): void
    {
        $this->dbAdapter()->connection->useDatabase($this->pickDatabaseName());
    }

    /**
     * Choose the name of the database to use.
     *
     * @return string
     */
    private function pickDatabaseName(): string
    {
        // generate a new name
        if ($this->usingDynamicTestDBs()) {
            $dbNameHash = $this->hasher->generateDBNameHash($this->config->pickSeedersToInclude());
            return $this->dbAdapter()->name->generateDynamicDBName($dbNameHash);
        }
        // or return the original name
        return $this->origDBName();
    }

    /**
     * Build or reuse an existing database.
     *
     * @return void
     */
    private function buildOrReuseDB(): void
    {
        $logTimer = $this->di->log->newTimer();

        $snapshotHash = $this->hasher->currentSnapshotHash();
        if (($this->usingReuseTestDBs()) && ($this->dbAdapter()->reuse->dbIsCleanForReuse($snapshotHash))) {
            $this->di->log->info('Reusing the existing database', $logTimer);
        } else {
            $this->buildDBFresh();
        }

        $this->dbAdapter()->reuse->writeReuseData(
            $this->origDBName(),
            $snapshotHash,
            $this->dbWillBeReusable()
        );
    }

    /**
     * Pick a process and build the database fresh.
     *
     * @return void
     */
    private function buildDBFresh(): void
    {
        $this->di->log->info('Building database…');
        $logTimer = $this->di->log->newTimer();

        if (!$this->dbAdapter()->snapshot->snapshotFilesAreSimplyCopied()) {
            $this->dbAdapter()->build->resetDB();
        }

        if (($this->config->snapshotsEnabled) && ($this->dbAdapter()->snapshot->isSnapshottable())) {
            $this->buildDBFromSnapshot();
        } else {
            $this->buildDBFromScratch();
        }

        $this->di->log->info('Database built - total time', $logTimer);
    }

    /**
     * Build the database fresh, loading from a snapshot if available.
     *
     * @return void
     */
    private function buildDBFromSnapshot(): void
    {
        $seeders = $this->config->pickSeedersToInclude();
        $seedersLeftToRun = [];

        if ($this->trySnapshots($seeders, $seedersLeftToRun)) {
            if (!count($seedersLeftToRun)) {
                return;
            }
            $this->seed($seedersLeftToRun);
        } else {
            $this->buildDBFromScratch();
        }
    }

    /**
     * Build the database fresh (no import from snapshot).
     *
     * @return void
     */
    private function buildDBFromScratch(): void
    {
        if ($this->dbAdapter()->snapshot->snapshotFilesAreSimplyCopied()) {
            $this->dbAdapter()->build->resetDB();
        }

        $this->importPreMigrationDumps();
        $this->migrate();
        $this->seed();
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    private function migrate(): void
    {
        if (!$this->config->migrations) {
            return;
        }

        $migrationsPath = (is_string($this->config->migrations) ? $this->config->migrations : null);
        $this->dbAdapter()->build->migrate($migrationsPath);

        if ($this->shouldTakeSnapshotAfterMigrations()) {
            $seedersRun = []; // ie. no seeders
            $this->takeDBSnapshot($seedersRun);
        }
    }

    /**
     * Run the seeders.
     *
     * @param string[]|null $seeders The seeders to run - will run all if not passed.
     * @return void
     */
    private function seed(array $seeders = null): void
    {
        if (!$this->seedingIsAllowed()) {
            return;
        }

        if (is_null($seeders)) {
            $seeders = $this->config->pickSeedersToInclude();
        }
        if (!count($seeders)) {
            return;
        }

        $this->dbAdapter()->build->seed($seeders);

        if ($this->shouldTakeSnapshotAfterSeeders()) {
            $seedersRun = $this->config->pickSeedersToInclude(); // ie. all seeders
            $this->takeDBSnapshot($seedersRun);
        }
    }

    /**
     * Take a snapshot (dump) of the current database.
     *
     * @param string[] $seeders The seeders that are included in this database.
     * @return void
     */
    private function takeDBSnapshot(array $seeders): void
    {
        if (($this->config->snapshotsEnabled) && ($this->dbAdapter()->snapshot->isSnapshottable())) {
            $logTimer = $this->di->log->newTimer();
            $snapshotPath = $this->generateSnapshotPath($seeders);
            $this->dbAdapter()->snapshot->takeSnapshot($snapshotPath);
            $this->di->log->info('Snapshot save SUCCESSFUL: "'.$snapshotPath.'"', $logTimer);
        }
    }

    /**
     * Import the database dumps needed before the migrations run.
     *
     * @return void
     */
    private function importPreMigrationDumps(): void
    {
        foreach ($this->config->pickPreMigrationDumps() as $path) {
            $logTimer = $this->di->log->newTimer();
            $this->dbAdapter()->snapshot->importSnapshot($path, true);
            $this->di->log->info('Import of pre-migration dump SUCCESSFUL: "'.$path.'"', $logTimer);
        }
    }

    /**
     * Create the storage directory if it doesn't exist.
     *
     * @return void
     * @throws AdaptConfigException Thrown when the directory could not be created.
     */
    private function ensureStorageDirExists(): void
    {
        $storageDir = $this->config->storageDir;

        if ($this->di->filesystem->pathExists($storageDir)) {
            if ($this->di->filesystem->isFile($storageDir)) {
                throw AdaptConfigException::storageDirIsAFile($storageDir);
            }
        } else {

            $e = null;
            try {
                $logTimer = $this->di->log->newTimer();

                // create the storage directory
                if ($this->di->filesystem->mkdir($storageDir, 0777, true)) {
                    // create a .gitignore file
                    $this->di->filesystem->writeFile($storageDir.'/.gitignore', 'w', '*'.PHP_EOL.'!.gitignore'.PHP_EOL);
                }
                $this->di->log->info('Created adapt-test-storage dir: "'.$storageDir.'"', $logTimer);
            } catch (Throwable $e) {
            }

            if (($e) || (!$this->di->filesystem->dirExists($storageDir))) {
                throw AdaptConfigException::cannotCreateStorageDir($storageDir, $e);
            }
        }
    }

    /**
     * Use the snapshot if it exits, trying one less seeder each time until one is found (or exhausted).
     *
     * @param string[] $seeders          The seeders to include.
     * @param string[] $seedersLeftToRun Array that will be populated with seeders that haven't been included.
     * @return boolean
     */
    private function trySnapshots(array $seeders, array &$seedersLeftToRun): bool
    {
        $seedersLeftToRun = [];
        do {
            $logTimer = $this->di->log->newTimer();

            $snapshotPath = $this->generateSnapshotPath($seeders);

            if ($this->di->filesystem->fileExists($snapshotPath)) {
                if ($this->dbAdapter()->snapshot->importSnapshot($snapshotPath)) {
                    $this->di->log->info('Import of snapshot SUCCESSFUL: "'.$snapshotPath.'"', $logTimer);
                    return true;
                } else {
                    $this->di->log->info('Import of snapshot FAILED: "'.$snapshotPath.'"', $logTimer);
                }
            } else {
                $this->di->log->info('Snapshot NOT FOUND: "'.$snapshotPath.'"', $logTimer);
            }

            if (!count($seeders)) {
                return false;
            }
            array_unshift($seedersLeftToRun, array_pop($seeders));
        } while (true);
    }

    /**
     * find old/current snapshots in the storage directory.
     *
     * @param boolean $findOld     Remove old snapshots.
     * @param boolean $findCurrent Remove current snapshots.
     * @return SnapshotMetaDTO[]
     * @throws AdaptSnapshotException Thrown when a snapshot file couldn't be removed.
     */
    public function findSnapshots(bool $findOld, bool $findCurrent): array
    {
        return $this->removeSnapshots($findOld, $findCurrent, false, true);
    }

    /**
     * Remove old/current snapshots from the storage directory.
     *
     * @param boolean $removeOld      Remove old snapshots.
     * @param boolean $removeCurrent  Remove current snapshots.
     * @param boolean $actuallyDelete Should files actually be deleted?.
     * @param boolean $getSize        Should the sizes of the snapshot files be added?.
     * @return SnapshotMetaDTO[]
     * @throws AdaptSnapshotException Thrown when a snapshot file couldn't be removed.
     */
    private function removeSnapshots(
        bool $removeOld,
        bool $removeCurrent,
        bool $actuallyDelete = true,
        bool $getSize = false
    ): array {

        if (!$this->di->filesystem->dirExists($this->config->storageDir)) {
            return [];
        }

        $key = (int) $removeOld.(int) $removeCurrent.(int) $actuallyDelete;
        if (isset(static::$removedSnapshots[$key])) {
            return [];
        }
        static::$removedSnapshots[$key] = true;

        $snapshotMetaDTOs = [];
        try {
            $filePaths = $this->di->filesystem->filesInDir($this->config->storageDir);
            foreach ($filePaths as $path) {

                if ($this->isSnapshotRelevant($path, $removeOld, $removeCurrent)) {

                    $snapshotMetaDTOs[] = (new SnapshotMetaDTO)
                        ->path($path)
                        ->size($getSize ? $this->di->filesystem->size($path) : null);

                    if ($actuallyDelete) {
                        $isOld = ($removeOld && !$removeCurrent);
                        $this->removeSnapshotFile($path, $isOld);
                    }
                }
            }
        } catch (Throwable $e) {
            if ($actuallyDelete) {
                throw AdaptSnapshotException::couldNotRemoveSnapshots($e);
            } else {
                throw AdaptSnapshotException::hadTroubleFindingSnapshots($e);
            }
        }
        return $snapshotMetaDTOs;
    }

    /**
     * Check if the given file is a snapshot, and if it's relevant.
     *
     * @param string  $path          The file to potentially remove.
     * @param boolean $detectOld     Detect old snapshots.
     * @param boolean $detectCurrent Detect current snapshots.
     * @return boolean
     */
    private function isSnapshotRelevant(
        string $path,
        bool $detectOld,
        bool $detectCurrent
    ): bool {

        $temp = explode('/', $path);
        $filename = (string) array_pop($temp);
        $prefix = $this->config->snapshotPrefix;

        if (mb_substr($filename, 0, mb_strlen($prefix)) == $prefix) {

            $filesHashMatched = $this->hasher->filenameHasFilesHash($filename);
            if ((($detectOld) && (!$filesHashMatched))
                || (($detectCurrent) && ($filesHashMatched))) {

                return true;
            }
        }
        return false;
    }

    /**
     * Remove the given snapshot file.
     *
     * @param string  $path  The file to remove.
     * @param boolean $isOld If this snapshot is "old" - affects the log message.
     * @return void
     */
    private function removeSnapshotFile(string $path, bool $isOld = false): void
    {
        $logTimer = $this->di->log->newTimer();
        $this->di->filesystem->unlink($path);
        $this->di->log->info('Removed '.($isOld ? 'old ' : '').'snapshot: "'.$path.'"', $logTimer);
    }

    /**
     * find old/current databases.
     *
     * @param boolean $lockToOrigDB Only look at test databases related to the original database this connection uses?.
     * @param boolean $findOld      Find old databases.
     * @param boolean $findCurrent  Find new databases.
     * @return DatabaseMetaDTO[]
     */
    public function findDatabases(bool $lockToOrigDB, bool $findOld, bool $findCurrent): array
    {
        return $this->removeDatabases($lockToOrigDB, $findOld, $findCurrent, false, true);
    }

    /**
     * Remove old/current databases.
     *
     * @param boolean $lockToOrigDB   Only look at test dbs related to the original database this connection uses?
     *                                Otherwise all databases will be looked at.
     * @param boolean $removeOld      Remove old databases.
     * @param boolean $removeCurrent  Remove new databases.
     * @param boolean $actuallyDelete Should databases actually be deleted?.
     * @param boolean $getSize        Should the sizes of the snapshot files be added?.
     * @return DatabaseMetaDTO[]
     */
    private function removeDatabases(
        bool $lockToOrigDB,
        bool $removeOld,
        bool $removeCurrent,
        bool $actuallyDelete = true,
        bool $getSize = false
    ): array {

        // we only want to remove databases related to the current database
        // otherwise this might conflict with databases from other projects
        // - or even ones with a different name in the same project
        $key = (int) $lockToOrigDB.(int) $removeOld.(int) $removeCurrent.(int) $actuallyDelete;
        $database = $this->pickDatabaseName();
        if (isset(static::$removedOldDatabases[$key][$this->config->driver][$database])) {
            return [];
        }
        static::$removedOldDatabases[$key][$this->config->driver][$database] = true;

        $databases = $this->dbAdapter()->reuse->findRelevantDatabases(
            ($lockToOrigDB ? $this->origDBName() : null),
            $this->hasher->generateSourceFilesHash(),
            $removeOld,
            $removeCurrent
        );

        $databaseMetaDTOs = [];
        foreach ($databases as $database) {

            $databaseMetaDTOs[] = (new DatabaseMetaDTO)
                ->name($database)
                ->size($getSize ? $this->dbAdapter()->reuse->size($database) : null);

            if ($actuallyDelete) {
                $isOld = ($removeOld && !$removeCurrent);
                $this->dbAdapter()->reuse->removeDatabase($database, $isOld);
            }
        }

        return $databaseMetaDTOs;
    }

    /**
     * Remove the given database.
     *
     * @param string $database The database to remove.
     * @return boolean
     */
    public function removeDatabase(string $database): bool
    {
        return $this->dbAdapter()->reuse->removeDatabase($database);
    }

    /**
     * Create a database adapter to do the database specific work.
     *
     * @return DBAdapter
     * @throws AdaptConfigException Thrown when the type of database isn't recognised.
     */
    private function dbAdapter(): DBAdapter
    {
        if (!is_null($this->dbAdapter)) {
            return $this->dbAdapter;
        }

        // build a new one
        $driver = $this->pickDriver();
        $framework = $this->framework;
        if ((!isset($this->availableDBAdapters[$framework]))
            || (!isset($this->availableDBAdapters[$framework][$driver]))) {
            throw AdaptConfigException::unsupportedDriver($this->config->connection, $driver);
        }

        $adapterClass = $this->availableDBAdapters[$framework][$driver];
        $this->dbAdapter = new $adapterClass($this->di, $this->config, $this->hasher);

        return $this->dbAdapter;
    }

    /**
     * Pick a database driver for the given connection.
     *
     * @return string
     */
    private function pickDriver(): string
    {
        $pickDriver = $this->pickDriverClosure;
        return $this->config->driver = $pickDriver($this->config->connection);
    }

    /**
     * Retrieve the current connection's original database name.
     *
     * @return string
     */
    private function origDBName(): string
    {
        return $this->di->config->origDBName($this->config->connection);
    }

    /**
     * Generate the path that will be used for the snapshot.
     *
     * @param string[] $seeders The seeders that are included in the snapshot.
     * @return string
     */
    private function generateSnapshotPath(array $seeders): string
    {
        $snapshotHash = $this->hasher->generateSnapshotHash($seeders);
        return $this->dbAdapter()->name->generateSnapshotPath(
            $snapshotHash ?? $this->hasher->currentSnapshotHash()
        );
    }
}
