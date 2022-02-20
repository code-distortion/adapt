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
use CodeDistortion\Adapt\Support\Exceptions;
use CodeDistortion\Adapt\Support\HasConfigDTOTrait;
use CodeDistortion\Adapt\Support\Hasher;
use CodeDistortion\Adapt\Support\Settings;
use DateTime;
use DateTimeZone;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Build a database ready for use in tests.
 */
class DatabaseBuilder
{
    use HasConfigDTOTrait;

    /** @var string The framework currently being used. */
    protected string $framework;

    /** @var string[][] The available database adapters. */
    private array $availableDBAdapters = [
        'laravel' => [
            'mysql' => LaravelMySQLAdapter::class,
            'sqlite' => LaravelSQLiteAdapter::class,
//            'pgsql' => LaravelPostgreSQLAdapter::class,
        ],
    ];

    /** @var DIContainer The dependency-injection container to use. */
    private DIContainer $di;

    /** @var callable The closure to call to get the driver for a connection. */
    private $pickDriverClosure;

    /** @var Hasher Builds and checks hashes. */
    private Hasher $hasher;


    /** @var boolean Whether this builder has been executed yet or not. */
    private bool $executed = false;

    /** @var DBAdapter|null The object that will do the database specific work. */
    private ?DBAdapter $dbAdapter = null;


    /**
     * Constructor.
     *
     * @param string      $framework         The framework currently being used.
     * @param DIContainer $di                The dependency-injection container to use.
     * @param ConfigDTO   $config            A DTO containing the settings to use.
     * @param Hasher      $hasher            The Hasher object to use.
     * @param callable    $pickDriverClosure A closure that will return the driver for the given connection.
     */
    public function __construct(
        string $framework,
        DIContainer $di,
        ConfigDTO $config,
        Hasher $hasher,
        callable $pickDriverClosure
    ) {
        $this->framework = $framework;
        $this->di = $di;
        $this->config = $config;
        $this->hasher = $hasher;
        $this->pickDriverClosure = $pickDriverClosure;
    }


    /**
     * Set this builder's database connection to be the "default" one.
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
        try {

            $this->onlyExecuteOnce();
            $this->prepareDB();
            return $this;

        } catch (Throwable $e) {

            $exceptionClass = Exceptions::resolveExceptionClass($e);
            $this->di->log->error("$exceptionClass: {$e->getMessage()}");
            throw $e;

        } finally {
            $this->di->log->debug(''); // delimiter between each database being built
        }
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
        if ($this->config->isRemoteBuild) {
            return false;
        }
        if (!$this->config->connectionExists) {
            return false;
        }
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
     * Check if the database should be built remotely (instead of locally).
     *
     * @return boolean
     */
    private function shouldBuildRemotely(): bool
    {
        return mb_strlen((string) $this->config->remoteBuildUrl) > 0;
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

        // take into consideration when there are no seeders to run, but a snapshot should be taken after seeders
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
     * Reuse the existing database, populate it from a snapshot or build it from scratch - whatever is necessary.
     *
     * @return void
     */
    private function prepareDB(): void
    {
        $logTimer = $this->di->log->newTimer();
        $this->logTitle();

        $this->shouldBuildRemotely()
            ? $this->buildDBRemotely()
            : $this->buildDBLocally();

        $this->di->log->debug('Total preparation time', $logTimer);
    }

    /**
     * Perform the process of building (or reuse an existing) database - locally.
     *
     * @return void
     * @throws AdaptConfigException Thrown when building failed.
     */
    private function buildDBLocally(): void
    {
        $this->initialise();
        $this->logSettingsUsed($this->pickDatabaseName());
        $this->pickDatabaseNameAndUse();
        $this->buildOrReuseDBLocally();
    }

    /**
     * Check if initialisation is possible.
     *
     * @return boolean
     */
    private function shouldInitialise(): bool
    {
        return $this->config->connectionExists;
    }

    /**
     * Initialise this object ready for running.
     *
     * @return void
     * @throws AdaptConfigException Thrown when the connection doesn't exist.
     */
    private function initialise(): void
    {
        if (!$this->config->connectionExists) {
            throw AdaptConfigException::invalidConnection($this->config->connection);
        }

        $this->pickDriver();
    }

    /**
     * Use the desired database.
     *
     * @return void
     */
    private function pickDatabaseNameAndUse(): void
    {
        if ($this->shouldBuildRemotely()) {
            return;
        }

        $this->useDatabase($this->pickDatabaseName());
    }

    /**
     * Choose the name of the database to use.
     *
     * @return string
     */
    private function pickDatabaseName(): string
    {
        // return the original name
        if (!$this->usingScenarioTestDBs()) {
            $this->origDBName();
        }

        // or generate a new name
        $dbNameHash = $this->hasher->generateDatabaseNameHashPart(
            $this->config->pickSeedersToInclude(),
            $this->config->databaseModifier
        );
        return $this->dbAdapter()->name->generateScenarioDBName($dbNameHash);
    }

    /**
     * Use the desired database.
     *
     * @param string $name The database to use.
     * @return void
     */
    private function useDatabase(string $name): void
    {
        $this->dbAdapter()->connection->useDatabase($name);
    }

    /**
     * Build or reuse an existing database - locally.
     *
     * @return void
     * @throws Throwable Thrown when the database couldn't be used.
     */
    private function buildOrReuseDBLocally(): void
    {
        $logTimer = $this->di->log->newTimer();

        try {
            if ($this->canReuseDB()) {
                $this->di->log->debug('Reusing the existing database', $logTimer);
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
        $this->dbAdapter()->reuse->writeReuseMetaData(
            $this->origDBName(),
            $this->hasher->getBuildHash(),
            $this->hasher->currentSnapshotHash(),
            $this->hasher->currentScenarioHash(),
            $reusable
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

        return $this->dbAdapter()->reuse->dbIsCleanForReuse(
            $this->hasher->getBuildHash(),
            $this->hasher->currentScenarioHash()
        );
    }

    /**
     * Build the database fresh.
     *
     * @return void
     */
    private function buildDBFresh(): void
    {
        $this->di->log->debug('Building the database…');

        if (!$this->dbAdapter()->snapshot->snapshotFilesAreSimplyCopied()) {
            $this->dbAdapter()->build->resetDB();
            // put the meta-table there straight away (even though it hasn't been built yet)
            // so another instance will identify that this database is an Adapt one
            $this->writeReuseMetaData(false);
        }

        if (($this->snapshotsAreEnabled()) && ($this->dbAdapter()->snapshot->isSnapshottable())) {
            $this->buildDBFromSnapshot();
        } else {
            $this->buildDBFromScratch();
        }
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
        // the db may have been reset above in buildDBFresh(),
        // if it wasn't, do it now to make sure it exists and is empty
        if ($this->dbAdapter()->snapshot->snapshotFilesAreSimplyCopied()) {
            $this->dbAdapter()->build->resetDB();
            $this->writeReuseMetaData(false); // put the meta-table there straight away
        }

        $this->importPreMigrationImports();
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
        $migrationsPath = is_string($this->config->migrations)
            ? $this->config->migrations
            : (bool) $this->config->migrations;

        if (!mb_strlen((string) $migrationsPath)) {
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
            $seedersRun = []; // i.e. no seeders
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
            $seedersRun = $this->config->pickSeedersToInclude(); // i.e. all seeders
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
        if (!$this->snapshotsAreEnabled()) {
            return;
        }
        if (!$this->dbAdapter()->snapshot->isSnapshottable()) {
            return;
        }

        $logTimer = $this->di->log->newTimer();

        $this->dbAdapter()->reuse->removeReuseMetaTable(); // remove the meta-table for the snapshot

        $snapshotPath = $this->generateSnapshotPath($seeders);
        $this->dbAdapter()->snapshot->takeSnapshot($snapshotPath);

        $this->writeReuseMetaData($this->dbWillBeReusable()); // put the meta-table back

        $this->di->log->debug('Snapshot save: "' . $snapshotPath . '" - successful', $logTimer);
    }

    /**
     * Import the database dumps needed before the migrations run.
     *
     * @return void
     * @throws AdaptSnapshotException When snapshots aren't allowed for this type of database.
     */
    private function importPreMigrationImports(): void
    {
        $preMigrationImports = $this->config->pickPreMigrationImports();
        if (!count($preMigrationImports)) {
            return;
        }

        if (!$this->dbAdapter()->snapshot->isSnapshottable()) {
            throw AdaptSnapshotException::importsNotAllowed(
                (string) $this->config->driver,
                (string) $this->config->database
            );
        }

        foreach ($preMigrationImports as $path) {
            $logTimer = $this->di->log->newTimer();
            $this->dbAdapter()->snapshot->importSnapshot($path, true);
            $this->di->log->debug('Import of pre-migration dump: "' . $path . '" - successful', $logTimer);
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
            $this->hasher->generateSnapshotFilenameHashPart($seeders)
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
            $this->di->log->debug('Snapshot import: "' . $snapshotPath . '" - not found', $logTimer);
            return false;
        }

        if (!$this->dbAdapter()->snapshot->importSnapshot($snapshotPath)) {
            $this->di->log->debug('Snapshot import: "' . $snapshotPath . '" - FAILED', $logTimer);
            return false;
        }

        $this->di->filesystem->touch($snapshotPath); // stale grace-period will start "now"

        $this->di->log->debug('Snapshot import: "' . $snapshotPath . '" - successful', $logTimer);
        return true;
    }



    /**
     * Perform the process of building (or reuse an existing) database - remotely.
     *
     * @return void
     */
    private function buildDBRemotely(): void
    {
        $this->di->log->debug("Building the database remotely…");
        $logTimer = $this->di->log->newTimer();

        $database = $this->sendBuildRemoteRequest();

        $this->dbWillBeReusable()
            ? $this->di->log->debug("Database \"$database\" was built or reused. Remote preparation time", $logTimer)
            : $this->di->log->debug("Database \"$database\" was built. Remote preparation time", $logTimer);

        if (!$this->shouldInitialise()) {
            $this->config->database($database);
            $this->di->log->debug("Not using connection \"{$this->config->connection}\" locally");
            return;
        }

        $this->initialise();
        $this->logSettingsUsed($database);
        $this->useDatabase($database);
    }

    /**
     * Send the http request to build the database remotely.
     *
     * @return string
     * @throws AdaptBuildException Thrown when the database couldn't be built.
     */
    private function sendBuildRemoteRequest(): string
    {
        $url = $this->buildRemoteUrl();
        $httpClient = new HttpClient(['timeout' => 60 * 10]);
        $data = ['configDTO' => $this->config->buildPayload()];

        try {
            $response = $httpClient->post(
                $this->buildRemoteUrl(),
                ['form_params' => $data]
            );
        } catch (GuzzleException $e) {
            $extraDetails = $this->interpretSendRemoteException($url, $e);
            throw AdaptBuildException::remoteBuildFailed($this->config->connection, $extraDetails, $e);
        }

        $database = (string) $response->getBody();
        return $database;
    }

    /**
     * Generate a readable string of text based on a GuzzleException.
     *
     * @param string          $url The remote-build url.
     * @param GuzzleException $e   The exception that occurred.
     * @return string
     */
    private function interpretSendRemoteException(string $url, GuzzleException $e): string
    {
        $responseMessage = null;
        if (method_exists($e, 'getResponse')) {

            /** @var ResponseInterface $response */
            $response = $e->getResponse();

            // don't bother with a message if it's a 404 - it's pretty self-explanatory
            if ($response->getStatusCode() != 404) {
                $responseMessage = $response->getBody()->getContents();
                $responseMessage = mb_strlen($responseMessage) > 200
                    ? mb_substr($responseMessage, 0, 200) . '…'
                    : $responseMessage;
            }
        }

        if ($e instanceof ConnectException) {
            return "Could not connect to $url";
        } elseif ($e instanceof BadResponseException) {
            return $responseMessage
                ? "$url ({$e->getCode()}) - remote error message: \"{$responseMessage}\""
                : "$url ({$e->getCode()})";
        } elseif (!is_null($responseMessage)) {
            return "Remote error message: \"{$responseMessage}\"";
        }
        return "Unknown error";
    }

    /**
     * Build the url to use when building the database remotely.
     *
     * @return string
     * @throws AdaptBuildException Thrown when the url is invalid.
     */
    private function buildRemoteUrl(): string
    {
        $remoteUrl = $origUrl = (string) $this->config->remoteBuildUrl;
        $pos = mb_strpos($remoteUrl, '?');
        if ($pos !== false) {
            $remoteUrl = mb_substr($remoteUrl, 0, $pos);
        }

        $remoteUrl = "$remoteUrl/" . Settings::REMOTE_BUILD_REQUEST_PATH;

        $parts = parse_url($remoteUrl);
        if (!is_array($parts)) {
            throw AdaptBuildException::remoteBuildUrlInvalid($origUrl);
        }

        $origPath = $parts['path'] ?? '';
        $path = (string) preg_replace('%//+%', '/', $origPath);
        return str_replace($origPath, $path, $remoteUrl);
    }



    /**
     * Build DatabaseMetaInfo objects for the existing databases.
     *
     * @return DatabaseMetaInfo[]
     */
    public function buildDatabaseMetaInfos(): array
    {
        return $this->dbAdapter()->reuse->findDatabases(
            $this->origDBName(),
            $this->hasher->getBuildHash()
        );
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
    private function buildSnapshotMetaInfo(string $path): ?SnapshotMetaInfo
    {
        $temp = explode('/', $path);
        $filename = (string) array_pop($temp);
        $prefix = $this->config->snapshotPrefix;

        if (mb_substr($filename, 0, mb_strlen($prefix)) != $prefix) {
            return null;
        }

        $filename = mb_substr($filename, mb_strlen($prefix));

        $accessTS = fileatime($path);
        $accessDT = new DateTime("@$accessTS");
        $accessDT->setTimezone(new DateTimeZone('UTC'));

        $snapshotMetaInfo = new SnapshotMetaInfo(
            $path,
            $filename,
            $accessDT,
            $this->hasher->filenameHasBuildHash($filename),
            fn() => $this->di->filesystem->size($path),
            $this->config->staleGraceSeconds
        );
        $snapshotMetaInfo->setDeleteCallback(fn() => $this->removeSnapshotFile($snapshotMetaInfo));
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
            $this->di->log->debug(
                'Removed ' . (!$snapshotMetaInfo->isValid ? 'old ' : '') . "snapshot: \"$snapshotMetaInfo->path\"",
                $logTimer
            );
            return true;
        }
        return false;
    }


    /**
     * Start the database transaction.
     *
     * @return void
     */
    public function applyTransaction(): void
    {
        if (!$this->usingTransactions()) {
            return;
        }

        $this->dbAdapter()->build->applyTransaction();
    }

    /**
     * Check to see if any of the transaction was committed (if relevant), and generate a warning.
     *
     * @return void
     * @throws AdaptTransactionException Thrown when the test committed the test-transaction.
     */
    public function checkForCommittedTransaction(): void
    {
        if (!$this->usingTransactions()) {
            return;
        }
        if (!$this->dbAdapter()->reuse->wasTransactionCommitted()) {
            return;
        }

        $this->di->log->warning(
            "The {$this->config->testName} test committed the transaction wrapper - "
            . "turn \$reuseTestDBs off to isolate it from other "
            . "tests that don't commit their transactions"
        );

        throw AdaptTransactionException::testCommittedTransaction($this->config->testName);
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
        /** @var DBAdapter $dbAdapter */
        $dbAdapter = new $adapterClass($this->di, $this->config, $this->hasher);
        $this->dbAdapter = $dbAdapter;

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





    /**
     * Log the details about the settings being used.
     *
     * @param string $database The database being used.
     * @return void
     */
    private function logSettingsUsed(string $database): void
    {
        $remoteExtra = ($this->shouldBuildRemotely() ? ' (remote)' : '');

        $snapshotStorageDir = null;
        if ($this->snapshotsAreEnabled()) {
            $snapshotStorageDir = $this->shouldBuildRemotely()
                ? '(Handled remotely)'
                : "\"{$this->config->storageDir}\"";
        }

        $temp = $this->config->pickPreMigrationImports();
        foreach ($temp as $index => $temp2) {
            $temp[$index] = "\"$temp2\"" . $remoteExtra;
        }
        $preMigrationImports = count($temp)
            ? implode(PHP_EOL, $temp)
            : 'None';

        $migrations = is_bool($this->config->migrations)
            ? $this->config->migrations ? 'Yes' : 'No'
            : "\"" . $this->config->migrations . "\"";
        $migrations .= $remoteExtra;

        if ($this->seedingIsAllowed()) {
            $seeders = $this->config->seeders;
            foreach ($seeders as $index => $seeder) {
                $seeders[$index] = "\"$seeder\"" . $remoteExtra;
            }
            $seeders = count($seeders)
                ? implode(PHP_EOL, $seeders)
                : 'None';
        } else {
            $seeders = 'n/a';
        }

        $buildHash = null;
        if ($this->usingScenarioTestDBs()) {
            $buildHash = $this->shouldBuildRemotely()
                ? '(Handled remotely)'
                : "\"{$this->hasher->getBuildHash()}\"";
        }

        $scenarioHash = null;
        if ($this->usingScenarioTestDBs()) {
            $scenarioHash = $this->shouldBuildRemotely()
                ? '(Handled remotely)'
                : "\"{$this->hasher->currentSnapshotHash()}\"";
        }

        $extendedScenarioHash = null;
        if ($this->usingScenarioTestDBs()) {
            $extendedScenarioHash = $this->shouldBuildRemotely()
                ? '(Handled remotely)'
                : "\"{$this->hasher->currentScenarioHash()}\"";
        }

        $lines = array_filter([
            'Project' => $this->config->projectName ? "\"{$this->config->projectName}\"": 'n/a',
            'Remote-build url' => $this->shouldBuildRemotely() ? "\"{$this->buildRemoteUrl()}\"" : null,
            'Snapshots enabled?' => $this->snapshotsAreEnabled() ? 'Yes' : 'No',
            'Snapshot storage' => $snapshotStorageDir,
            'Pre-migration import/s' => $preMigrationImports,
            'Migrations' => $migrations,
            'Seeder/s' => $seeders,
            'Using scenarios?' => $this->usingScenarioTestDBs() ? 'Yes' : 'No',
            '- Build-hash' => $buildHash,
            '- Scenario-hash' => $extendedScenarioHash,
            '- Snapshot-hash' => $scenarioHash,
            'Is a browser test?' => $this->config->isBrowserTest ? 'Yes' : 'No',
            'Is reusable?' => $this->dbWillBeReusable() ? 'Yes' : 'No - will be re-built each time',
        ]);
        $lines = $this->padList($lines);

        $this->logBox($lines, 'Build Settings');

//        $this->di->log->debug('Build Settings:');
//        foreach ($lines as $line) {
//            $this->di->log->debug($line);
//        }



        $host = $this->di->db->getHost();

        $lines = array_filter([
            'Connection' => "\"{$this->config->connection}\"",
            'Driver' => "\"{$this->config->driver}\"",
            'Host' => $host ? "\"{$host}\"" : null,
            'Database' => "\"{$database}\"",
        ]);
        $lines = $this->padList($lines);

        $this->logBox($lines, 'Resolved Database');

//        $this->di->log->debug('Resolved Database:');
//        foreach ($lines as $line) {
//            $this->di->log->debug($line);
//        }
    }

    /**
     * Log the title line.
     *
     * @return void
     */
    private function logTitle(): void
    {
        if ($this->shouldBuildRemotely()) {
            $prepLine = "Preparing the \"{$this->config->connection}\" database remotely";
        } else if ($this->config->isRemoteBuild) {
            $prepLine = "Preparing the \"{$this->config->connection}\" database locally, for another Adapt installation";
        } else {
            $prepLine = "Preparing the \"{$this->config->connection}\" database";
        }

        $this->logBox(
            [$prepLine, "For test \"{$this->config->testName}\""],
            'ADAPT - Preparing a Test-Database'
        );
    }

    /**
     * Log some lines in a box
     *
     * @param string|string[] $lines The lines to log in a table.
     * @param string|null     $title The title to add to the top line.
     * @return void
     */
    private function logBox($lines, ?string $title = null): void
    {
        $lines = !is_array($lines) ? [$lines] : $lines;

        if (!count(array_filter($lines))) {
            return;
        }

        $title = mb_strlen($title) ? " $title " : '';

        $maxLength = mb_strlen($title);
        foreach ($lines as $line) {
            $maxLength = max($maxLength, mb_strlen($line));
        }

        $this->di->log->debug('┌──' . $title . str_repeat('─', $maxLength - mb_strlen($title)) . '┐');

        foreach ($lines as $line) {
            $line = str_pad($line, $maxLength, ' ', STR_PAD_RIGHT);
            $this->di->log->debug("│ $line │");
        }

        $this->di->log->debug('└' . str_repeat('─', $maxLength + 2) . '┘');
    }

    /**
     * Add the array keys to the values,  padded based on the length of the longest key.
     *
     * @param array<string, string> $lines The lines to process
     * @return void
     */
    private function padList(array $lines): array
    {
        $maxLength = 0;
        foreach (array_keys($lines) as $key) {
            $maxLength = max($maxLength, mb_strlen($key));
        }

        $newLines = [];
        foreach ($lines as $key => $line) {
            $line = str_replace(["\r\n", "\r", "\n"], "\n", $line);
            $partialLines = explode("\n", $line);
            $count = 0;
            foreach ($partialLines as $partialLine) {
                $tempKey = $count++ == 0 ? "$key:" : '';
                $newLines[] = str_pad($tempKey, $maxLength + 2, ' ', STR_PAD_RIGHT) . $partialLine;
            }
        }

        return $newLines;
    }
}
