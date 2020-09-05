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
use CodeDistortion\Adapt\Support\HasConfigDTOTrait;
use CodeDistortion\Adapt\Support\Hasher;
use Throwable;

/**
 * Build a database ready for use in tests.
 */
class DatabaseBuilder
{
    use HasConfigDTOTrait;

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
        if ((!$this->config->snapshotsEnabled) && (!$this->config->isBrowserTest)) {
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
        if ((!$this->config->snapshotsEnabled) && (!$this->config->isBrowserTest)) {
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
            $this->hasher->currentSourceFilesHash();
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
            $dbNameHash = $this->hasher->generateDBNameHash(
                $this->config->pickSeedersToInclude(),
                $this->config->databaseModifier
            );
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

        if ($this->canReuseDB()) {
            $this->di->log->info('Reusing the existing database', $logTimer);
        } else {
            $this->buildDBFresh();
        }

        $this->dbAdapter()->reuse->writeReuseData(
            $this->origDBName(),
            $this->hasher->currentSourceFilesHash(),
            $this->hasher->currentScenarioHash(),
            $this->dbWillBeReusable()
        );
    }

    /**
     * Check if the current database can be re-used.
     *
     * @return boolean
     */
    private function canReuseDB(): bool
    {
        if (!$this->usingReuseTestDBs()) {
            return false;
        }

        if (!$this->dbAdapter()->reuse->dbIsCleanForReuse(
            $this->hasher->currentSourceFilesHash(),
            $this->hasher->currentScenarioHash()
        )) {
            return false;
        }

        return true;
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

        $this->di->log->info('Database total build time', $logTimer);
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
     * @throws AdaptConfigException When the migration path isn't valid.
     */
    private function migrate(): void
    {
        if (!$this->config->migrations) {
            return;
        }

        if ((is_string($this->config->migrations))
        && (!$this->di->filesystem->dirExists((string) realpath($this->config->migrations)))) {
            throw AdaptConfigException::migrationsPathInvalid($this->config->migrations);
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
     * @throws AdaptSnapshotException When snapshots aren't allowed for this type of database.
     */
    private function importPreMigrationDumps(): void
    {
        $preMigrationDumps = $this->config->pickPreMigrationDumps();
        if (!count($preMigrationDumps)) {
            return;
        }

        if (!$this->dbAdapter()->snapshot->isSnapshottable()) {
            throw AdaptSnapshotException::importsNotAllowed(
                (string) $this->config->driver,
                (string) $this->config->database
            );
        }

        foreach ($preMigrationDumps as $path) {
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
            if ($this->trySnapshot($seeders)) {
                return true;
            }
            if (!count($seeders)) {
                return false;
            }
            array_unshift($seedersLeftToRun, array_pop($seeders));
        } while (true);
    }

    /**
     * Generate the path that will be used for the snapshot.
     *
     * @param string[] $seeders The seeders that are included in the snapshot.
     * @return string
     */
    private function generateSnapshotPath(array $seeders): string
    {
        return $this->dbAdapter()->name->generateSnapshotPath(
            $this->hasher->generateSnapshotHash($seeders)
        );
    }

    /**
     * Use the snapshot if it exits.
     *
     * @param string[] $seeders The seeders to include.
     * @return boolean
     */
    private function trySnapshot(array $seeders): bool
    {
        $logTimer = $this->di->log->newTimer();

        $snapshotPath = $this->generateSnapshotPath($seeders);

        if (!$this->di->filesystem->fileExists($snapshotPath)) {
            $this->di->log->info('Snapshot NOT FOUND: "'.$snapshotPath.'"', $logTimer);
            return false;
        }

        if (!$this->dbAdapter()->snapshot->importSnapshot($snapshotPath)) {
            $this->di->log->info('Import of snapshot FAILED: "'.$snapshotPath.'"', $logTimer);
            return false;
        }

        $this->di->log->info('Import of snapshot SUCCESSFUL: "'.$snapshotPath.'"', $logTimer);
        return true;
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
            $filename = mb_substr($filename, mb_strlen($prefix));

            $filesHashMatched = $this->hasher->filenameHasSourceFilesHash($filename);

            if (($detectOld) && (!$filesHashMatched)) {
                return true;
            }
            if (($detectCurrent) && ($filesHashMatched)) {
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
            $this->hasher->currentSourceFilesHash(),
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

        // build a new one...
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
}
