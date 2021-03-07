<?php

namespace CodeDistortion\Adapt;

use CodeDistortion\Adapt\Adapters\DBAdapter;
use CodeDistortion\Adapt\Adapters\LaravelMySQLAdapter;
use CodeDistortion\Adapt\Adapters\LaravelSQLiteAdapter;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\DTO\DatabaseMetaInfo;
use CodeDistortion\Adapt\DTO\SnapshotMetaInfo;
use CodeDistortion\Adapt\Exceptions\AdaptBuildException;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use CodeDistortion\Adapt\Exceptions\AdaptSnapshotException;
use CodeDistortion\Adapt\Exceptions\AdaptTransactionException;
use CodeDistortion\Adapt\Support\HasConfigDTOTrait;
use CodeDistortion\Adapt\Support\Hasher;
use DateTime;
use DateTimeZone;
use PDOException;
use Throwable;

/**
 * Build a database ready for use in tests.
 */
class DatabaseBuilder
{
    use HasConfigDTOTrait;

    /** @var string The framework currently being used. */
    protected $framework;

    /** @var string The name of the current test. */
    protected $testName;

    /** @var string[][] The available database adapters. */
    private $availableDBAdapters = [
        'laravel' => [
            'mysql' => LaravelMySQLAdapter::class,
            'sqlite' => LaravelSQLiteAdapter::class,
//            'pgsql' => LaravelPostgreSQLAdapter::class,
        ],
    ];

    /** @var DIContainer The dependency-injection container to use. */
    private $di;

    /** @var callable The closure to call to get the driver for a connection. */
    private $pickDriverClosure;

    /** @var Hasher Builds and checks hashes. */
    private $hasher;


    /** @var boolean Whether this builder has been executed yet or not. */
    private $executed = false;

    /** @var DBAdapter|null The object that will do the database specific work. */
    private $dbAdapter = null;


    /**
     * Constructor.
     *
     * @param string      $framework         The framework currently being used.
     * @param string      $testName          The name of the test being run.
     * @param DIContainer $di                The dependency-injection container to use.
     * @param ConfigDTO   $config            A DTO containing the settings to use.
     * @param Hasher      $hasher            The Hasher object to use.
     * @param callable    $pickDriverClosure A closure that will return the driver for the given connection.
     */
    public function __construct(string $framework, string $testName, DIContainer $di, ConfigDTO $config, Hasher $hasher, callable $pickDriverClosure)
    {
        $this->framework = $framework;
        $this->testName = $testName;
        $this->di = $di;
        $this->config = $config;
        $this->hasher = $hasher;
        $this->pickDriverClosure = $pickDriverClosure;
    }


    /**
     * Set the this builder's database connection to be the "default" one.
     *
     * @return static
     */
    public function makeDefault()
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
    public function execute()
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
    private function onlyExecuteOnce()
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
        return (($this->config->reuseTestDBs) && (!$this->config->isBrowserTest));
    }

    /**
     * Resolve whether the database being created can be reused later.
     *
     * @return boolean
     */
    private function dbWillBeReusable(): bool
    {
        return $this->usingReuseTestDBs();
    }

    /**
     * Resolve whether transactions are to be used.
     *
     * @return boolean
     */
    private function usingTransactions(): bool
    {
        return $this->usingReuseTestDBs();
    }

    /**
     * Resolve whether scenarioTestDBs is to be used.
     *
     * @return boolean
     */
    private function usingScenarioTestDBs(): bool
    {
        return $this->config->scenarioTestDBs;
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
     * Resolve whether snapshots are enabled or not.
     *
     * @return boolean
     */
    private function snapshotsAreEnabled(): bool
    {
        return $this->usingReuseTestDBs()
            ? in_array($this->config->useSnapshotsWhenReusingDB, ['afterMigrations', 'afterSeeders', 'both'], true)
            : in_array($this->config->useSnapshotsWhenNotReusingDB, ['afterMigrations', 'afterSeeders', 'both'], true);
    }

    /**
     * Derive if a snapshot should be taken after the migrations have been run.
     *
     * @return boolean
     */
    private function shouldTakeSnapshotAfterMigrations(): bool
    {
        if (!$this->snapshotsAreEnabled()) {
            return false;
        }

        // take in to consideration when there are no seeders to run, but a snapshot should be taken after seeders
        $setting = $this->usingReuseTestDBs()
            ? $this->config->useSnapshotsWhenReusingDB
            : $this->config->useSnapshotsWhenNotReusingDB;

        return count($this->config->pickSeedersToInclude())
            ? in_array($setting, ['afterMigrations', 'both'], true)
            : in_array($setting, ['afterMigrations', 'afterSeeders', 'both'], true);
    }

    /**
     * Derive if a snapshot should be taken after the seeders have been run.
     *
     * @return boolean
     */
    private function shouldTakeSnapshotAfterSeeders(): bool
    {
        if (!$this->snapshotsAreEnabled()) {
            return false;
        }
        if (!count($this->config->pickSeedersToInclude())) {
            return false;
        }

        $setting = $this->usingReuseTestDBs()
            ? $this->config->useSnapshotsWhenReusingDB
            : $this->config->useSnapshotsWhenNotReusingDB;

        return in_array($setting, ['afterSeeders', 'both'], true);
    }

    /**
     * Initialise this object ready for running.
     *
     * @return void
     */
    private function initialise()
    {
        $this->pickDriver();
    }

    /**
     * Reuse the existing database, populate it from a snapshot or build it from scratch - whatever is necessary.
     *
     * @return void
     */
    private function prepareDB()
    {
        $this->di->log->info('---- Preparing a database for test: ' . $this->testName . ' ----------------');
        $this->di->log->info('Using connection "' . $this->config->connection . '" (driver "' . $this->config->driver . '")');

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
    private function pickDatabaseNameAndUse()
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
        if ($this->usingScenarioTestDBs()) {
            $dbNameHash = $this->hasher->generateDBNameHash($this->config->pickSeedersToInclude(), $this->config->databaseModifier);
            return $this->dbAdapter()->name->generateScenarioDBName($dbNameHash);
        }
        // or return the original name
        return $this->origDBName();
    }

    /**
     * Build or reuse an existing database.
     *
     * @return void
     */
    private function buildOrReuseDB()
    {
        $logTimer = $this->di->log->newTimer();

        try {

            if ($this->canReuseDB()) {
                $this->di->log->info('Reusing the existing database', $logTimer);
            } else {
                $this->buildDBFresh();
            }
            $this->writeReuseMetaData($this->dbWillBeReusable());

        } catch (Throwable $e) {
            throw $this->transformAccessDeniedException($e);
        }
    }

    /**
     * Create the re-use meta-data table.
     *
     * @param boolean $reusable Whether this database can be reused or not.
     * @return void
     */
    private function writeReuseMetaData(bool $reusable)
    {
        $this->dbAdapter()->reuse->writeReuseMetaData($this->origDBName(), $this->hasher->currentSourceFilesHash(), $this->hasher->currentScenarioHash(), $reusable);
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

        return $this->dbAdapter()->reuse->dbIsCleanForReuse($this->hasher->currentSourceFilesHash(), $this->hasher->currentScenarioHash());
    }

    /**
     * Pick a process and build the database fresh.
     *
     * @return void
     */
    private function buildDBFresh()
    {
        $this->di->log->info('Building database…');
        $logTimer = $this->di->log->newTimer();

        if (!$this->dbAdapter()->snapshot->snapshotFilesAreSimplyCopied()) {
            $this->dbAdapter()->build->resetDB();
            $this->writeReuseMetaData(false); // put the meta-table there straight away
        }

        if (($this->snapshotsAreEnabled()) && ($this->dbAdapter()->snapshot->isSnapshottable())) {
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
    private function buildDBFromSnapshot()
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
    private function buildDBFromScratch()
    {
        // the db may have been reset above in buildDBFresh(),
        // if it wasn't, do it now to make sure it exists and is empty
        if ($this->dbAdapter()->snapshot->snapshotFilesAreSimplyCopied()) {
            $this->dbAdapter()->build->resetDB();
            $this->writeReuseMetaData(false); // put the meta-table there straight away
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
    private function migrate()
    {
        $migrationsPath = is_string($this->config->migrations)
            ? $this->config->migrations
            : (bool) $this->config->migrations;

        if (!mb_strlen($migrationsPath)) {
            return;
        }

        if (is_string($migrationsPath)) {
            if (!$this->di->filesystem->dirExists((string) realpath($migrationsPath))) {
                throw AdaptConfigException::migrationsPathInvalid($migrationsPath);
            }
        } else {
            $migrationsPath = null;
        }

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
    private function seed(array $seeders = null)
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
    private function takeDBSnapshot(array $seeders)
    {
        if (!$this->snapshotsAreEnabled()) {
            return;
        }
        if (!$this->dbAdapter()->snapshot->isSnapshottable()) {
            return;
        }
        $logTimer = $this->di->log->newTimer();
        $this->dbAdapter()->reuse->removeReuseMetaTable();
        // remove the meta-table for the snapshot
        $snapshotPath = $this->generateSnapshotPath($seeders);
        $this->dbAdapter()->snapshot->takeSnapshot($snapshotPath);
        $this->writeReuseMetaData($this->dbWillBeReusable());
        // put the meta-table back
        $this->di->log->info('Snapshot save SUCCESSFUL: "' . $snapshotPath . '"', $logTimer);
    }

    /**
     * Import the database dumps needed before the migrations run.
     *
     * @return void
     * @throws AdaptSnapshotException When snapshots aren't allowed for this type of database.
     */
    private function importPreMigrationDumps()
    {
        $preMigrationDumps = $this->config->pickPreMigrationDumps();
        if (!count($preMigrationDumps)) {
            return;
        }

        if (!$this->dbAdapter()->snapshot->isSnapshottable()) {
            throw AdaptSnapshotException::importsNotAllowed((string) $this->config->driver, (string) $this->config->database);
        }

        foreach ($preMigrationDumps as $path) {
            $logTimer = $this->di->log->newTimer();
            $this->dbAdapter()->snapshot->importSnapshot($path, true);
            $this->di->log->info('Import of pre-migration dump SUCCESSFUL: "' . $path . '"', $logTimer);
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
        return $this->dbAdapter()->name->generateSnapshotPath($this->hasher->generateSnapshotHash($seeders));
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
            $this->di->log->info('Snapshot NOT FOUND: "' . $snapshotPath . '"', $logTimer);
            return false;
        }
        if (!$this->dbAdapter()->snapshot->importSnapshot($snapshotPath)) {
            $this->di->log->info('Import of snapshot FAILED: "' . $snapshotPath . '"', $logTimer);
            return false;
        }
        $this->di->filesystem->touch($snapshotPath);
        // invalidation grace-period will start "now"
        $this->di->log->info('Import of snapshot SUCCESSFUL: "' . $snapshotPath . '"', $logTimer);
        return true;
    }



    /**
     * Build DatabaseMetaInfo objects for the existing databases.
     *
     * @return DatabaseMetaInfo[]
     */
    public function buildDatabaseMetaInfos(): array
    {
        return $this->dbAdapter()->reuse->findDatabases($this->origDBName(), $this->hasher->currentSourceFilesHash());
    }



    /**
     * Build SnapshotMetaInfo objects for the snapshots in the storage directory.
     *
     * @return SnapshotMetaInfo[]
     * @throws AdaptSnapshotException Thrown when a snapshot file couldn't be used.
     */
    public function buildSnapshotMetaInfos(): array
    {
        if (!$this->di->filesystem->dirExists($this->config->storageDir)) {
            return [];
        }

        try {
            $snapshotMetaInfos = [];
            $filePaths = $this->di->filesystem->filesInDir($this->config->storageDir);
            foreach ($filePaths as $path) {
                $snapshotMetaInfos[] = $this->buildSnapshotMetaInfo($path);
            }
            return array_values(array_filter($snapshotMetaInfos));
        } catch (Throwable $e) {
            throw AdaptSnapshotException::hadTroubleFindingSnapshots($e);
        }
    }

    /**
     * Build a SnapshotMetaInfo object for a snapshot.
     *
     * @param string $path The file to build a SnapshotMetaInfo object for.
     * @return SnapshotMetaInfo|null
     */
    private function buildSnapshotMetaInfo(string $path)
    {
        $temp = explode('/', $path);
        $filename = (string) array_pop($temp);
        $prefix = $this->config->snapshotPrefix;
        if (mb_substr($filename, 0, mb_strlen($prefix)) != $prefix) {
            return null;
        }
        $filename = mb_substr($filename, mb_strlen($prefix));
        $accessTS = fileatime($path);
        $accessDT = new DateTime("@$accessTS") ?: null;
        $accessDT ? $accessDT->setTimezone(new DateTimeZone('UTC')) : null;
        $snapshotMetaInfo = new SnapshotMetaInfo($path, $filename, $accessDT, $this->hasher->filenameHasSourceFilesHash($filename), function () use ($path) {
            return $this->di->filesystem->size($path);
        }, $this->config->invalidationGraceSeconds);
        $snapshotMetaInfo->setDeleteCallback(function () use ($snapshotMetaInfo) {
            return $this->removeSnapshotFile($snapshotMetaInfo);
        });
        return $snapshotMetaInfo;
    }

    /**
     * Remove the given snapshot file.
     *
     * @param SnapshotMetaInfo $snapshotMetaInfo The info object representing the snapshot file.
     * @return boolean
     */
    private function removeSnapshotFile(SnapshotMetaInfo $snapshotMetaInfo): bool
    {
        $logTimer = $this->di->log->newTimer();
        if ($this->di->filesystem->unlink($snapshotMetaInfo->path)) {
            $this->di->log->info('Removed ' . (!$snapshotMetaInfo->isValid ? 'old ' : '') . "snapshot: \"$snapshotMetaInfo->path\"", $logTimer);
            return true;
        }
        return false;
    }


    /**
     * Check to see if any of the transaction was committed (if relevant), and generate a warning.
     *
     * @return void
     * @throws AdaptTransactionException Thrown when the test committed the test-transaction.
     */
    public function checkForCommittedTransaction()
    {
        if (!$this->usingTransactions()) {
            return;
        }
        if (!$this->dbAdapter()->reuse->wasTransactionCommitted()) {
            return;
        }

        $this->di->log->warning("The $this->testName test committed the transaction wrapper - "
            . "turn \$reuseTestDBs off to isolate it from other "
            . "tests that don't commit their transactions");

        throw AdaptTransactionException::testCommittedTransaction($this->testName);
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
        if (
            (!isset($this->availableDBAdapters[$framework]))
            || (!isset($this->availableDBAdapters[$framework][$driver]))
        ) {
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
     * Throw a custom exception if the given exception is, or contains a PDOException - "access denied" exception.
     *
     * @param Throwable $e The exception to check.
     * @return Throwable
     */
    private function transformAccessDeniedException(Throwable $e): Throwable
    {
        $previous = $e;
        do {
            if ($previous instanceof PDOException) {
                if ($previous->getCode() == 1044) {
                    return AdaptBuildException::accessDenied($e);
                }
            }
            $previous = $previous->getPrevious();
        } while ($previous);
        return $e;
    }
}
