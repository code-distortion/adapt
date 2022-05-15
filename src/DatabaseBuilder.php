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
use CodeDistortion\Adapt\Exceptions\AdaptTransactionException;
use CodeDistortion\Adapt\Support\DatabaseBuilderTraits\ConfigAdapterAndDriverTrait;
use CodeDistortion\Adapt\Support\DatabaseBuilderTraits\DatabaseTrait;
use CodeDistortion\Adapt\Support\DatabaseBuilderTraits\LogTrait;
use CodeDistortion\Adapt\Support\DatabaseBuilderTraits\OnlyExecuteOnceTrait;
use CodeDistortion\Adapt\Support\DatabaseBuilderTraits\ReuseTransactionTrait;
use CodeDistortion\Adapt\Support\DatabaseBuilderTraits\ReuseJournalTrait;
use CodeDistortion\Adapt\Support\DatabaseBuilderTraits\VerificationTrait;
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
    use ReuseTransactionTrait;
    use ReuseJournalTrait;
    use VerificationTrait;



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
     * @param ConfigDTO   $configDTO         A DTO containing the settings to use.
     * @param Hasher      $hasher            The Hasher object to use.
     * @param callable    $pickDriverClosure A closure that will return the driver for the given connection.
     */
    public function __construct(
        string $framework,
        DIContainer $di,
        ConfigDTO $configDTO,
        Hasher $hasher,
        callable $pickDriverClosure
    ) {
        $this->framework = $framework;
        $this->di = $di;
        $this->configDTO = $configDTO;
        $this->hasher = $hasher;
        $this->pickDriverClosure = $pickDriverClosure;

        // update $configDTO with some extra settings now that the driver is known
        $this->configDTO->dbIsTransactionable($this->dbAdapter()->reuseTransaction->isTransactionable());
        $this->configDTO->dbIsJournalable($this->dbAdapter()->reuseJournal->isJournalable());
        $this->configDTO->dbIsVerifiable($this->dbAdapter()->verifier->isVerifiable());
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
        $this->prePreparationChecks();
        $this->prepareDB();

        $this->di->log->debug("Total preparation time for database \"{$this->configDTO->database}\"", $logTimer, true);

        return $this;
    }

    /**
     * Perform things after building, but BEFORE the test has run.
     *
     * @return void
     */
    public function runPostBuildSteps()
    {
        $this->recordVerificationStart();
        $this->recordJournalingStart();
        $this->applyTransaction();
    }

    /**
     * Perform things after the TEST has run.
     *
     * @param boolean $isLast Whether this is the last Builder to run or not.
     * @return void
     * @throws AdaptTransactionException When the re-use transaction was committed.
     */
    public function runPostTestSteps(bool $isLast)
    {
        // work out when the new-line should be added
        $c = $isLast && $this->configDTO->shouldVerifyData();
        $b = $isLast && $this->configDTO->shouldUseJournal() && !$c;
        $a = $isLast && $this->configDTO->shouldVerifyStructure() && !$b && !$c;

        $this->checkForCommittedTransaction();
        $this->verifyDatabaseStructure($a);
        $this->reverseJournal($b);
        $this->verifyDatabaseData($c);
        $this->recordVerificationStop();
    }



    /**
     * Perform any checks that that need to happen before building a database.
     *
     * @return void
     */
    private function prePreparationChecks(): void
    {
        $this->configDTO->ensureThatSessionDriversMatch();
    }



    /**
     * Reuse the existing database, populate it from a snapshot or build it from scratch - whatever is necessary.
     *
     * @return void
     */
    private function prepareDB(): void
    {
        $logTimer = $this->di->log->newTimer();

        $reusedLocally = $this->reuseRemotelyBuiltDBLocallyIfPossible($logTimer);
        if ($reusedLocally) {
            return;
        }

        $this->logTitle();

        $forceRebuild = ($reusedLocally === false); // false means it was built before, but couldn't be reused

        $this->configDTO->shouldBuildRemotely()
            ? $this->buildDBRemotely($forceRebuild, $logTimer)
            : $this->buildDBLocally($forceRebuild);
    }





    /**
     * Check if the database was built remotely, earlier in the test-run. Then re-use it if it can be.
     *
     * (This avoids needing to make a remote request to build when the hashes have already been calculated).
     *
     * @param integer $logTimer The timer, started a little earlier.
     * @return boolean|null Null = irrelevant (just build it), false = it exists but can't be reused (force-rebuild).
     */
    private function reuseRemotelyBuiltDBLocallyIfPossible(int $logTimer): ?bool
    {
        $reuseLocally = $this->checkLocallyIfRemotelyBuiltDBCanBeReused();

        if ($reuseLocally) {
            $this->reuseRemotelyBuiltDB($logTimer);
        }

        return $reuseLocally;
    }

    /**
     * Check if the current database was built remotely earlier in the test-run, and can be re-used now.
     *
     * @return boolean|null Null = irrelevant (just build it), false = it exists but can't be reused (force-rebuild).
     * @throws Throwable When something goes wrong.
     */
    private function checkLocallyIfRemotelyBuiltDBCanBeReused(): ?bool
    {
        try {

            if (!$this->configDTO->shouldBuildRemotely()) {
                return null;
            }

            $resolvedSettingsDTO = $this->getTheRelevantPreviousResolvedSettingsDTO();
            if (!$resolvedSettingsDTO) {
                return null;
            }

            $origDatabase = $this->getCurrentDatabase();
            $this->silentlyUseDatabase((string) $resolvedSettingsDTO->database);

            $return = $this->canReuseDB(
                (string) $resolvedSettingsDTO->buildHash,
                (string) $resolvedSettingsDTO->scenarioHash,
                (string) $resolvedSettingsDTO->database
            );

            // restore it back so it can be officially changed later
            $this->silentlyUseDatabase((string) $origDatabase);

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
        if (!$this->configDTO->connectionExists) {
            return null;
        }

        return Settings::getResolvedSettingsDTO($this->hasher->currentScenarioHash());
    }

    /**
     * Reuse the database that was built remotely.
     *
     * @param integer $logTimer The timer, started a little earlier.
     * @return void
     * @throws Throwable When something goes wrong.
     */
    private function reuseRemotelyBuiltDB(int $logTimer): void
    {
        try {

            if ($this->configDTO->shouldInitialise()) {
                $this->initialise();
            }

            $this->resolvedSettingsDTO = $this->getTheRelevantPreviousResolvedSettingsDTO();

            $connection = $this->configDTO->connection;
            $database = $this->resolvedSettingsDTO ? (string) $this->resolvedSettingsDTO->database : '';
            $buildHash = $this->resolvedSettingsDTO ? $this->resolvedSettingsDTO->buildHash : null;
            $this->configDTO->remoteBuildUrl = null; // stop debug output from showing that it's being built remotely
            $this->hasher->buildHashWasPreCalculated($buildHash);

            $this->logTitle();
            $this->logHttpRequestWasSaved($database, $logTimer);

            if (!$this->configDTO->shouldInitialise()) {
                $this->di->log->debug("Not using connection \"$connection\" locally");
                return;
            }

            $this->logTheUsedSettings();
            $this->useDatabase($database);
            $this->di->log->debug("Reusing the existing \"$database\" database 😎");
            $this->updateMetaTableLastUsed();

        } catch (Throwable $e) {
            throw $this->transformAnAccessDeniedException($e);
        }
    }





    /**
     * Perform the process of building (or reuse an existing) database - locally.
     *
     * @param boolean $forceRebuild Should the database be rebuilt anyway (no need to double-check)?.
     * @return void
     * @throws AdaptConfigException When building failed.
     */
    private function buildDBLocally(bool $forceRebuild): void
    {
        $this->initialise();

        $this->hasher->buildHashWasPreCalculated($this->configDTO->preCalculatedBuildHash); // only used when not null

        $this->resolvedSettingsDTO = $this->buildResolvedSettingsDTO($this->pickDatabaseName());

        $this->logTheUsedSettings();

        $this->pickDatabaseNameAndUse();

        $reused = $this->buildOrReuseDBLocally($forceRebuild);

        if ($this->resolvedSettingsDTO) { // for phpstan
            $this->resolvedSettingsDTO->databaseWasReused($reused);
        }
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
        if (!$this->configDTO->connectionExists) {
            throw AdaptConfigException::invalidConnection($this->configDTO->connection);
        }

        if (($this->configDTO->isBrowserTest) && (!$this->dbAdapter()->build->isCompatibleWithBrowserTests())) {
            throw AdaptBuildException::databaseNotCompatibleWithBrowserTests((string) $this->configDTO->driver);
        }
    }

    /**
     * Build or reuse an existing database - locally.
     *
     * @param boolean $forceRebuild Should the database be rebuilt anyway (no need to double-check)?.
     * @return boolean Returns true if the database was reused.
     * @throws Throwable When the database couldn't be used.
     */
    private function buildOrReuseDBLocally(bool $forceRebuild): bool
    {
//        $this->di->log->debug("Preparing database \"{$this->configDTO->database}\"…");

        try {
            $logTimer = $this->di->log->newTimer();

            $database = (string) $this->configDTO->database;
            $canReuse = !$forceRebuild && $this->canReuseDB(
                $this->hasher->getBuildHash(),
                $this->hasher->currentScenarioHash(),
                $database
            );

            $canReuse
                ? $this->di->log->debug("Reusing the existing \"$database\" database 😎", $logTimer)
                : $this->di->log->debug("Database \"$database\" cannot be reused", $logTimer);

            if ($canReuse) {
                $this->updateMetaTableLastUsed();
            } else {
                $this->buildDBFresh();
                $this->createReuseMetaDataTable();
            }

            return $canReuse;

        } catch (Throwable $e) {
            throw $this->transformAnAccessDeniedException($e);
        }
    }



    /**
     * Check if the current database can be re-used.
     *
     * @param string $buildHash    The current build-hash.
     * @param string $scenarioHash The current scenario-hash.
     * @param string $database     The current database to check.
     * @return boolean
     */
    private function canReuseDB(
        string $buildHash,
        string $scenarioHash,
        string $database
    ): bool {

        $logTimer = $this->di->log->newTimer();

        if (!$this->configDTO->reusingDB()) {
            return false;
        }

        if ($this->configDTO->forceRebuild) {
            return false;
        }

        return $this->dbAdapter()->reuseMetaData->dbIsCleanForReuse(
            $buildHash,
            $scenarioHash,
            $this->configDTO->projectName,
            $database
        );
    }

    /**
     * Create the re-use meta-data table.
     *
     * @return void
     */
    private function createReuseMetaDataTable(): void
    {
        $logTimer = $this->di->log->newTimer();

        $this->dbAdapter()->reuseMetaData->createReuseMetaDataTable(
            $this->origDBName(),
            $this->hasher->getBuildHash(),
            $this->hasher->currentSnapshotHash(),
            $this->hasher->currentScenarioHash()
        );

        $this->di->log->debug("Set up re-use meta-data", $logTimer);
    }

    /**
     * Update the last-used field in the meta-table.
     *
     * @return void
     */
    private function updateMetaTableLastUsed(): void
    {
        $logTimer = $this->di->log->newTimer();

        $this->dbAdapter()->reuseMetaData->updateMetaTableLastUsed();

        $this->di->log->debug("Updated re-use meta-data", $logTimer);
    }

    /**
     * Build the database fresh.
     *
     * @return void
     */
    private function buildDBFresh(): void
    {
        $this->resetDBIfSnapshotsFilesAreNotSimplyCopied();

        $this->canUseSnapshots()
            ? $this->buildDBFromSnapshot()
            : $this->buildDBFromScratch();
    }

    /**
     * Check if snapshots can be used here.
     *
     * @return boolean
     * @throws AdaptConfigException
     */
    private function canUseSnapshots(): bool
    {
        return $this->configDTO->snapshotsAreEnabled() && $this->dbAdapter()->snapshot->isSnapshottable();
    }

    /**
     * Reset the database ready to build into - only when snapshot files are simply copied.
     *
     * @return void
     * @throws AdaptConfigException
     */
    private function resetDBIfSnapshotFilesAreSimplyCopied(): void
    {
        if ($this->dbAdapter()->snapshot->snapshotFilesAreSimplyCopied()) {
            $this->resetDB();
        }
    }

    /**
     * Reset the database ready to build into - only when snapshot files are NOT simply copied.
     *
     * @return void
     * @throws AdaptConfigException
     */
    private function resetDBIfSnapshotsFilesAreNotSimplyCopied(): void
    {
        if (!$this->dbAdapter()->snapshot->snapshotFilesAreSimplyCopied()) {
            $this->resetDB();
        }
    }

    /**
     * Reset the database ready to build into.
     *
     * @return void
     * @throws AdaptConfigException
     */
    private function resetDB(): void
    {
        $this->dbAdapter()->build->resetDB();
//        $this->createReuseMetaDataTable();
    }

    /**
     * Build the database fresh, loading from a snapshot if available.
     *
     * @return void
     */
    private function buildDBFromSnapshot(): void
    {
        $seeders = $this->configDTO->pickSeedersToInclude();
        $seedersLeftToRun = [];

        if ($this->trySnapshots($seeders, $seedersLeftToRun)) {
            if (count($seedersLeftToRun)) {
                $this->seed($seedersLeftToRun);
                $this->takeSnapshotAfterSeeders();
            }
            $this->setUpVerification();
            $this->setUpJournaling();
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
        $this->resetDBIfSnapshotFilesAreSimplyCopied();

        $this->importPreMigrationImports();
        $this->migrate();
        $this->takeSnapshotAfterMigrations();
        $this->seed();
        $this->takeSnapshotAfterSeeders();
        $this->setUpVerification();
        $this->setUpJournaling();
    }



    /**
     * Import the database dumps needed before the migrations run.
     *
     * @return void
     * @throws AdaptSnapshotException When snapshots aren't allowed for this type of database.
     */
    private function importPreMigrationImports(): void
    {
        $preMigrationImports = $this->configDTO->pickPreMigrationImports();
        if (!count($preMigrationImports)) {
            return;
        }

        if (!$this->dbAdapter()->snapshot->isSnapshottable()) {
            throw AdaptSnapshotException::importsNotAllowed(
                (string) $this->configDTO->driver,
                (string) $this->configDTO->database
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
        $migrationsPath = is_string($this->configDTO->migrations)
            ? $this->configDTO->migrations
            : (bool) $this->configDTO->migrations;

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
    }



    /**
     * Run the seeders.
     *
     * @param string[]|null $seeders The seeders to run - will run all if not passed.
     * @return void
     */
    private function seed(array $seeders = null): void
    {
        if (!$this->configDTO->seedingIsAllowed()) {
            return;
        }

        if (is_null($seeders)) {
            $seeders = $this->configDTO->pickSeedersToInclude();
        }

        if (!count($seeders)) {
            return;
        }

        $this->dbAdapter()->build->seed($seeders);
    }



    /**
     * Take the snapshot that would be taken after the migrations have been run.
     *
     * @return void
     */
    private function takeSnapshotAfterMigrations(): void
    {
        if (!$this->configDTO->shouldTakeSnapshotAfterMigrations()) {
            return;
        }

        $seedersRun = []; // i.e. no seeders
        $this->takeDBSnapshot($seedersRun);
    }

    /**
     * Take the snapshot that would be taken after the seeders have been run.
     *
     * @return void
     */
    private function takeSnapshotAfterSeeders(): void
    {
        if (!$this->configDTO->shouldTakeSnapshotAfterSeeders()) {
            return;
        }

        $seedersRun = $this->configDTO->pickSeedersToInclude(); // i.e. all seeders
        $this->takeDBSnapshot($seedersRun);
    }



    /**
     * Take a snapshot (dump) of the current database.
     *
     * @param string[] $seeders The seeders that are included in this database.
     * @return void
     */
    private function takeDBSnapshot(array $seeders): void
    {
        if (!$this->canUseSnapshots()) {
            return;
        }

        $logTimer = $this->di->log->newTimer();

//        $this->dbAdapter()->reuseMetaData->removeReuseMetaTable(); // remove the meta-table, ready for the snapshot

        $snapshotPath = $this->generateSnapshotPath($seeders);
        $this->dbAdapter()->snapshot->takeSnapshot($snapshotPath);

//        $this->createReuseMetaDataTable(); // put the meta-table back

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
     * @throws AdaptRemoteBuildException When the database type isn't allowed to be built remotely.
     */
    private function buildDBRemotely(bool $forceRebuild, int $logTimer): void
    {
        if (!$this->dbAdapter()->build->canBeBuiltRemotely()) {
            throw AdaptRemoteBuildException::databaseTypeCannotBeBuiltRemotely((string) $this->configDTO->driver);
        }

        if ($this->configDTO->shouldInitialise()) {
            $this->initialise();
        }



        $this->di->log->debug("Building the database remotely…");

        $this->resolvedSettingsDTO = $this->sendBuildRemoteRequest($forceRebuild);
        Settings::storeResolvedSettingsDTO($this->hasher->currentScenarioHash(), $this->resolvedSettingsDTO);

        $connection = $this->resolvedSettingsDTO->connection;
        $database = (string) $this->resolvedSettingsDTO->database;

        $message = $this->resolvedSettingsDTO->databaseWasReused
            ? "Database \"$database\" was reused. Remote preparation time"
            : "Database \"$database\" was built. Remote preparation time";
        $this->di->log->debug($message, $logTimer);

        if (!$this->configDTO->shouldInitialise()) {
//            $this->configDTO->database($database);
            $this->di->log->debug("Not using connection \"$connection\" locally");
            return;
        }

        $this->logTheUsedSettings();
        $this->useDatabase($database);

        if ($this->resolvedSettingsDTO && $this->resolvedSettingsDTO->databaseWasReused) {
            $this->di->log->debug("Reusing the existing \"$database\" database 😎");
        }
    }

    /**
     * Send the http request to build the database remotely.
     *
     * @param boolean $forceRebuild Should the database be rebuilt anyway (no need to double-check)?.
     * @return ResolvedSettingsDTO
     * @throws AdaptRemoteBuildException When the database couldn't be built.
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

            Hasher::rememberRemoteBuildHash($url, (string) $resolvedSettingsDTO->buildHash);

            return $resolvedSettingsDTO;

        } catch (GuzzleException $e) {
            throw $this->buildAdaptRemoteBuildException($url, $e);
        }
    }

    /**
     * Build a ConfigDTO ready to send in the remote-build request.
     *
     * @param string  $remoteBuildUrl The remote-build url.
     * @param boolean $forceRebuild   Force the database to be rebuilt.
     * @return ConfigDTO
     */
    private function prepareConfigForRemoteRequest(string $remoteBuildUrl, bool $forceRebuild): ConfigDTO
    {
        $configDTO = clone $this->configDTO;

        if ($forceRebuild) {
            $configDTO->forceRebuild = true;
        }

        // save time by telling the remote Adapt installation what the build-hash was from last time.
        $configDTO->preCalculatedBuildHash(Hasher::getRemoteBuildHash($remoteBuildUrl));
        return $configDTO;
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
            $this->configDTO->connection,
            $url,
            $response,
            $e
        );
    }

    /**
     * Build the url to use when building the database remotely.
     *
     * @return string
     * @throws AdaptRemoteBuildException When the url is invalid.
     */
    private function buildRemoteUrl(): string
    {
        $remoteUrl = $origUrl = (string) $this->configDTO->remoteBuildUrl;
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
        return $this->dbAdapter()->find->findDatabases(
            $this->origDBName(),
            $this->hasher->getBuildHash()
        );
    }



    /**
     * Build SnapshotMetaInfo objects for the snapshots in the storage directory.
     *
     * @return SnapshotMetaInfo[]
     * @throws AdaptSnapshotException When a snapshot file couldn't be used.
     */
    public function buildSnapshotMetaInfos(): array
    {
        if (!$this->di->filesystem->dirExists($this->configDTO->storageDir)) {
            return [];
        }

        try {
            $snapshotMetaInfos = [];
            $filePaths = $this->di->filesystem->filesInDir($this->configDTO->storageDir);
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
        $prefix = $this->configDTO->snapshotPrefix;

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
            $this->configDTO->staleGraceSeconds
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
        $configDTO = $this->configDTO;
        $canHash = $configDTO->usingScenarioTestDBs() && !$configDTO->shouldBuildRemotely();

        return (new ResolvedSettingsDTO())
            ->projectName($configDTO->projectName)
            ->testName($configDTO->testName)
            ->connection($configDTO->connection)
            ->driver((string) $configDTO->driver)
            ->host($this->di->db->getHost())
            ->database($database)
            ->storageDir($configDTO->storageDir)
            ->preMigrationImports($configDTO->pickPreMigrationImports())
            ->migrations($configDTO->migrations)
            ->seeders($configDTO->seedingIsAllowed(), $configDTO->seeders)
            ->builtRemotely(
                $configDTO->shouldBuildRemotely(),
                $configDTO->shouldBuildRemotely() ? $this->buildRemoteUrl() : null
            )
            ->snapshotType(
                $configDTO->snapshotType(),
                is_string($configDTO->useSnapshotsWhenReusingDB) ? $configDTO->useSnapshotsWhenReusingDB : null,
                is_string($configDTO->useSnapshotsWhenNotReusingDB) ? $configDTO->useSnapshotsWhenNotReusingDB : null,
            )
            ->isBrowserTest($configDTO->isBrowserTest)
            ->sessionDriver($configDTO->sessionDriver)
            ->transactionReusable($configDTO->shouldUseTransaction())
            ->journalReusable($configDTO->shouldUseJournal())
            ->verifyDatabase($configDTO->verifyDatabase)
            ->forceRebuild($configDTO->forceRebuild)
            ->scenarioTestDBs(
                $configDTO->usingScenarioTestDBs(),
                $canHash ? $this->hasher->getBuildHash() : null,
                $canHash ? $this->hasher->currentSnapshotHash() : null,
                $canHash ? $this->hasher->currentScenarioHash() : null
            )
            ->databaseWasReused(true);
    }

    /**
     * Get the ResolvedSettingsDTO representing the settings that were used.
     *
     * @return ResolvedSettingsDTO|null
     */
    public function getResolvedSettingsDTO(): ?ResolvedSettingsDTO
    {
        return $this->resolvedSettingsDTO;
    }
}
