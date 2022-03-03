<?php

namespace CodeDistortion\Adapt;

use CodeDistortion\Adapt\Adapters\LaravelMySQLAdapter;
use CodeDistortion\Adapt\Adapters\LaravelSQLiteAdapter;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\DTO\DatabaseMetaInfo;
use CodeDistortion\Adapt\DTO\ResolvedSettingsDTO;
use CodeDistortion\Adapt\DTO\SnapshotMetaInfo;
use CodeDistortion\Adapt\Exceptions\AdaptBuildException;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use CodeDistortion\Adapt\Exceptions\AdaptRemoteBuildException;
use CodeDistortion\Adapt\Exceptions\AdaptSnapshotException;
use CodeDistortion\Adapt\Support\DatabaseBuilderTraits\ConfigAdapterAndDriverTrait;
use CodeDistortion\Adapt\Support\DatabaseBuilderTraits\DatabaseTrait;
use CodeDistortion\Adapt\Support\DatabaseBuilderTraits\LogTrait;
use CodeDistortion\Adapt\Support\DatabaseBuilderTraits\OnlyExecuteOnceTrait;
use CodeDistortion\Adapt\Support\DatabaseBuilderTraits\TransactionTrait;
use CodeDistortion\Adapt\Support\HasConfigDTOTrait;
use CodeDistortion\Adapt\Support\Hasher;
use CodeDistortion\Adapt\Support\Settings;
use DateTime;
use DateTimeZone;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Build a database ready for use in tests.
 */
class DatabaseBuilder
{
    use ConfigAdapterAndDriverTrait;
    use DatabaseTrait;
    use HasConfigDTOTrait;
    use LogTrait;
    use OnlyExecuteOnceTrait;
    use TransactionTrait;

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



    /** @var ResolvedSettingsDTO|null The build-settings when they've been resolved. */
    private ?ResolvedSettingsDTO $resolvedSettingsDTO = null;



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
     * Build a database ready to test with - migrate and seed the database etc (only if it's not ready-to-go already).
     *
     * @return static
     * @throws Throwable When something goes wrong.
     */
    public function execute(): self
    {
        $logTimer = $this->di->log->newTimer();

        $this->onlyExecuteOnce();
//        $this->primeTheBuildHash();
        $this->prePreparationChecks();
        $this->prepareDB();

        $this->di->log->debug('Total preparation time', $logTimer, true);

        return $this;
    }



    /**
     * Pre-generate the build-hash, so the "Generated the build-hash" log line appears before the rest of the logs.
     *
     * @return void
     * @throws AdaptConfigException
     */
    private function primeTheBuildHash(): void
    {
        $resolvedSettingsDTO = $this->getTheRelevantPreviousResolvedSettingsDTO();
        if ($resolvedSettingsDTO) {
            $this->hasher->buildHashWasPreCalculated($resolvedSettingsDTO->buildHash);
        } elseif (!$this->config->shouldBuildRemotely()) {
            $this->hasher->getBuildHash();
        }
    }

    /**
     * Perform any checks that that need to happen before building a database.
     *
     * @return void
     */
    private function prePreparationChecks(): void
    {
        $this->config->checkThatSessionDriversMatch();
    }



    /**
     * Reuse the existing database, populate it from a snapshot or build it from scratch - whatever is necessary.
     *
     * @return void
     */
    private function prepareDB(): void
    {
        $logTimer = $this->di->log->newTimer();

        $reusedLocally = $this->reuseLocallyIfPossible();
        if ($reusedLocally) {
            $this->di->log->debug('Reusing the existing database', $logTimer);
            return;
        }

        $this->logTitle();

        $forceRebuild = ($reusedLocally === false); // false means it was built before, but couldn't be reused

        $this->config->shouldBuildRemotely()
            ? $this->buildDBRemotely($forceRebuild, $logTimer)
            : $this->buildDBLocally($forceRebuild, $logTimer);
    }





    /**
     * If the database has been resolved before (within the same test-run).
     *
     * @return boolean|null
     * @throws Throwable
     */
    private function reuseLocallyIfPossible(): ?bool
    {
        $reuseLocally = $this->checkLocallyIfCanReuseDB();

        if ($reuseLocally) {
            $this->locallyReuseDB();
        }

        return $reuseLocally;
    }

    /**
     * Check if the current database can be re-used.
     *
     * @return boolean|null
     */
    private function checkLocallyIfCanReuseDB(): ?bool
    {
        try {

            if (!$this->config->shouldBuildRemotely()) {
                return null;
            }

            $resolvedSettingsDTO = $this->getTheRelevantPreviousResolvedSettingsDTO();
            if (!$resolvedSettingsDTO) {
                return null;
            }

            $origDatabase = $this->getCurrentDatabase();
            $this->silentlyUseDatabase($resolvedSettingsDTO->database);

            $return = $this->canReuseDB(
                $resolvedSettingsDTO->buildHash,
                $resolvedSettingsDTO->scenarioHash,
                $resolvedSettingsDTO->database,
            );

            $this->silentlyUseDatabase($origDatabase); // restore it back so it can be officially changed later

            return $return;

        } catch (Throwable $e) {
            throw $this->transformAnAccessDeniedException($e);
        }
    }

    /**
     * Grab the previously built ResolvedSettingsDTO (if it exists).
     *
     * @return ResolvedSettingsDTO|null
     */
    private function getTheRelevantPreviousResolvedSettingsDTO(): ?ResolvedSettingsDTO
    {
        if (!$this->config->connectionExists) {
            return null;
        }

        return Settings::getResolvedSettingsDTO($this->hasher->currentScenarioHash());
    }

    /**
     * Reuse the datbase.
     *
     * @return void
     */
    private function locallyReuseDB(): void
    {
        try {

            if ($this->config->shouldInitialise()) {
                $this->initialise();
            }

            $this->resolvedSettingsDTO = $this->getTheRelevantPreviousResolvedSettingsDTO();

            $connection = $this->config->connection;
            $database = $this->resolvedSettingsDTO->database;
            $this->config->remoteBuildUrl = null; // stop the debug output from showing that it's being built remotely
            $this->hasher->buildHashWasPreCalculated($this->resolvedSettingsDTO->buildHash);

            $this->logTitle();

            if (!$this->config->shouldInitialise()) {
//                $this->config->database($database);
                $this->di->log->debug("Not using connection \"$connection\" locally");
                return;
            }

            $this->logTheUsedSettings();
            $this->useDatabase($database);

        } catch (Throwable $e) {
            throw $this->transformAnAccessDeniedException($e);
        }
    }





    /**
     * Perform the process of building (or reuse an existing) database - locally.
     *
     * @param boolean $forceRebuild Should the database be rebuilt anyway (no need to double-check)?.
     * @param integer $logTimer     The timer, started a little earlier.
     * @return void
     * @throws AdaptConfigException Thrown when building failed.
     */
    private function buildDBLocally(bool $forceRebuild, int $logTimer): void
    {
        $this->initialise();

        $this->hasher->buildHashWasPreCalculated($this->config->preCalculatedBuildHash); // only uses when not null

        $this->resolvedSettingsDTO = $this->buildResolvedSettingsDTO($this->pickDatabaseName());

        $this->logTheUsedSettings();

        $this->pickDatabaseNameAndUse();
        $this->buildOrReuseDBLocally($forceRebuild, $logTimer);
    }

    /**
     * Initialise this object ready for running.
     *
     * @return void
     * @throws AdaptConfigException When the connection doesn't exist.
     * @throws AdaptBuildException When the database isn't compatible with browser tests.
     */
    private function initialise(): void
    {
        if (!$this->config->connectionExists) {
            throw AdaptConfigException::invalidConnection($this->config->connection);
        }

        $this->pickDriver();

        if (($this->config->isBrowserTest) && (!$this->dbAdapter()->build->isCompatibleWithBrowserTests())) {
            throw AdaptBuildException::databaseNotCompatibleWithBrowserTests($this->config->driver);
        }
    }

    /**
     * Build or reuse an existing database - locally.
     *
     * @param boolean $forceRebuild Should the database be rebuilt anyway (no need to double-check)?.
     * @param integer $logTimer     The timer, started a little earlier.
     * @return void
     * @throws Throwable Thrown when the database couldn't be used.
     */
    private function buildOrReuseDBLocally(bool $forceRebuild, int $logTimer): void
    {
        try {

            $canReuse = !$forceRebuild && $this->canReuseDB(
                $this->hasher->getBuildHash(),
                $this->hasher->currentScenarioHash(),
                $this->config->database,
            );

            if ($canReuse) {
                $this->di->log->debug('Reusing the existing database', $logTimer);
            } else {
                $this->buildDBFresh();
            }

            $this->writeReuseMetaData(
                $this->hasher->currentScenarioHash(),
                $this->config->dbWillBeReusable()
            );

        } catch (Throwable $e) {
            throw $this->transformAnAccessDeniedException($e);
        }
    }



    /**
     * Check if the current database can be re-used.
     *
     * @return boolean
     */
    private function canReuseDB(
        string $buildHash,
        string $scenarioHash,
        string $database
    ): bool {

        if (!$this->config->usingReuseTestDBs()) {
            return false;
        }

        if ($this->config->forceRebuild) {
            return false;
        }

        return $this->dbAdapter()->reuse->dbIsCleanForReuse(
            $buildHash,
            $scenarioHash,
            $this->config->projectName,
            $database
        );
    }

    /**
     * Create the re-use meta-data table.
     *
     * @param boolean $reusable Whether this database can be reused or not.
     * @return void
     */
    private function writeReuseMetaData(string $scenarioHash, bool $reusable)
    {
        $this->dbAdapter()->reuse->writeReuseMetaData(
            $this->origDBName(),
            $this->hasher->getBuildHash(),
            $this->hasher->currentSnapshotHash(),
            $scenarioHash,
            $reusable
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
            $this->writeReuseMetaData($this->hasher->currentScenarioHash(), false);
        }

        ($this->config->snapshotsAreEnabled()) && ($this->dbAdapter()->snapshot->isSnapshottable())
            ? $this->buildDBFromSnapshot()
            : $this->buildDBFromScratch();
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
            $this->writeReuseMetaData($this->hasher->currentScenarioHash(), false); // put the meta-table there straight away
        }

        $this->importPreMigrationImports();
        $this->migrate();
        $this->seed();
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

        if ($this->config->shouldTakeSnapshotAfterMigrations()) {
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
        if (!$this->config->seedingIsAllowed()) {
            return;
        }

        if (is_null($seeders)) {
            $seeders = $this->config->pickSeedersToInclude();
        }
        if (!count($seeders)) {
            return;
        }

        $this->dbAdapter()->build->seed($seeders);

        if ($this->config->shouldTakeSnapshotAfterSeeders()) {
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
        if (!$this->config->snapshotsAreEnabled()) {
            return;
        }
        if (!$this->dbAdapter()->snapshot->isSnapshottable()) {
            return;
        }

        $logTimer = $this->di->log->newTimer();

        $this->dbAdapter()->reuse->removeReuseMetaTable(); // remove the meta-table for the snapshot

        $snapshotPath = $this->generateSnapshotPath($seeders);
        $this->dbAdapter()->snapshot->takeSnapshot($snapshotPath);

        // put the meta-table back
        $this->writeReuseMetaData($this->hasher->currentScenarioHash(), $this->config->dbWillBeReusable());

        $this->di->log->debug('Snapshot save: "' . $snapshotPath . '" - successful', $logTimer);
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
     * @param boolean $forceRebuild Should the database be rebuilt anyway (no need to double-check)?.
     * @param integer $logTimer     The timer, started a little earlier.
     * @return void
     * @throws AdaptBuildException When the database type isn't allowed to be built remotely.
     */
    private function buildDBRemotely(bool $forceRebuild, int $logTimer): void
    {
        if (!$this->dbAdapter()->build->canBeBuiltRemotely()) {
            throw AdaptRemoteBuildException::databaseTypeCannotBeBuiltRemotely($this->config->driver);
        }

        if ($this->config->shouldInitialise()) {
            $this->initialise();
        }



        $this->di->log->debug("Building the database remotely…");

        $this->resolvedSettingsDTO = $this->sendBuildRemoteRequest($forceRebuild);
        Settings::storeResolvedSettingsDTO($this->hasher->currentScenarioHash(), $this->resolvedSettingsDTO);

        $connection = $this->resolvedSettingsDTO->connection;
        $database = $this->resolvedSettingsDTO->database;

        $this->config->dbWillBeReusable()
            ? $this->di->log->debug("Database \"$database\" was built or reused. Remote preparation time", $logTimer)
            : $this->di->log->debug("Database \"$database\" was built. Remote preparation time", $logTimer);

        if (!$this->config->shouldInitialise()) {
//            $this->config->database($database);
            $this->di->log->debug("Not using connection \"$connection\" locally");
            return;
        }

        $this->logTheUsedSettings();
        $this->useDatabase($database);
    }

    /**
     * Send the http request to build the database remotely.
     *
     * @param boolean $forceRebuild Should the database be rebuilt anyway (no need to double-check)?.
     * @return ResolvedSettingsDTO
     * @throws AdaptRemoteBuildException Thrown when the database couldn't be built.
     */
    private function sendBuildRemoteRequest(bool $forceRebuild): ResolvedSettingsDTO
    {
        $httpClient = new HttpClient(['timeout' => 60 * 10]);
        $url = $this->buildRemoteUrl();
        $data = ['configDTO' => $this->prepareConfigForRemoteRequest($url, $forceRebuild)->buildPayload()];

        try {
            $response = $httpClient->post(
                $this->buildRemoteUrl(),
                ['form_params' => $data]
            );

            $resolvedSettingsDTO = ResolvedSettingsDTO::buildFromPayload((string) $response->getBody());
            $resolvedSettingsDTO->builtRemotely(true, $url);

            Hasher::rememberRemoteBuildHash($url, $resolvedSettingsDTO->buildHash);

            return $resolvedSettingsDTO;

        } catch (GuzzleException $e) {
            throw $this->buildAdaptRemoteBuildException($url, $e);
        }
    }

    /**
     * Build a ConfigDTO ready to send in the remote-build request.
     *
     * @param string $remoteBuildUrl The remote-build url
     * @return ConfigDTO
     */
    private function prepareConfigForRemoteRequest(string $remoteBuildUrl, bool $forceRebuild): ConfigDTO
    {
        $config = clone $this->config;

        if ($forceRebuild) {
            $config->forceRebuild = true;
        }

        // save time by telling the remote Adapt installation what the build-hash was from last time.
        $config->preCalculatedBuildHash(Hasher::getRemoteBuildHash($remoteBuildUrl));
        return $config;
    }

    /**
     * Build a AdaptRemoteBuildException.
     *
     * @param string          $url The remote-build url.
     * @param GuzzleException $e   The exception that occurred.
     * @return AdaptRemoteBuildException
     */
    private function buildAdaptRemoteBuildException(string $url, GuzzleException $e): AdaptRemoteBuildException
    {
        /** @var ?ResponseInterface $response */
        $response = method_exists($e, 'getResponse') ? $e->getResponse() : null;

        return AdaptRemoteBuildException::remoteBuildFailed(
            $this->config->connection,
            $url,
            $response,
            $e,
            $this->di->log->someLoggingIsOn()
        );
    }

    /**
     * Build the url to use when building the database remotely.
     *
     * @return string
     * @throws AdaptRemoteBuildException Thrown when the url is invalid.
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
            throw AdaptRemoteBuildException::remoteBuildUrlInvalid($origUrl);
        }
        if ((!isset($parts['scheme'])) || (!isset($parts['host']))) {
            throw AdaptRemoteBuildException::remoteBuildUrlInvalid($origUrl);
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
     * Throw a custom exception if the given exception is, or contains a PDOException - "access denied" exception.
     *
     * @param Throwable $e The exception to check.
     * @return Throwable
     */
    private function transformAnAccessDeniedException(Throwable $e): Throwable
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
     * Build a ResolvedSettingsDTO from the current preparation.
     *
     * @param string $database The database used.
     * @return ResolvedSettingsDTO
     */
    private function buildResolvedSettingsDTO(string $database): ResolvedSettingsDTO
    {
        $canHash = $this->config->usingScenarioTestDBs() && !$this->config->shouldBuildRemotely();

        return (new ResolvedSettingsDTO())
            ->projectName($this->config->projectName)
            ->testName($this->config->testName)
            ->connection($this->config->connection)
            ->driver($this->config->driver)
            ->host($this->di->db->getHost())
            ->database($database)
            ->storageDir($this->config->storageDir)
            ->preMigrationImports($this->config->pickPreMigrationImports())
            ->migrations($this->config->migrations)
            ->seeders($this->config->seedingIsAllowed(), $this->config->seeders)
            ->builtRemotely(
                $this->config->shouldBuildRemotely(),
                $this->config->shouldBuildRemotely() ? $this->buildRemoteUrl() : null
            )
            ->snapshotType(
                $this->config->snapshotType(),
                $this->config->useSnapshotsWhenReusingDB,
                $this->config->useSnapshotsWhenNotReusingDB,
            )
            ->isBrowserTest($this->config->isBrowserTest)
            ->sessionDriver($this->config->sessionDriver)
            ->databaseIsReusable($this->config->dbWillBeReusable())
            ->forceRebuild($this->config->forceRebuild)
            ->scenarioTestDBs(
                $this->config->usingScenarioTestDBs(),
                $canHash ? $this->hasher->getBuildHash() : null,
                $canHash ? $this->hasher->currentSnapshotHash() : null,
                $canHash ? $this->hasher->currentScenarioHash() : null
            );
    }

    /**
     * Get the ResolvedSettingsDTO representing the settings that were used.
     *
     * @return ResolvedSettingsDTO
     */
    public function getResolvedSettingsDTO(): ResolvedSettingsDTO
    {
        return $this->resolvedSettingsDTO;
    }
}
