<?php

namespace CodeDistortion\Adapt\DTO;

use CodeDistortion\Adapt\DTO\Traits\DTOBuildTrait;
use CodeDistortion\Adapt\Exceptions\AdaptRemoteShareException;

/**
 * A DTO to record the various inputs and settings used by Adapt when building a database.
 */
class ResolvedSettingsDTO
{
    use DTOBuildTrait;



    /** @var string|null The name of the current project. */
    public ?string $projectName;

    /** @var string The name of the current test. */
    public string $testName;



    /** @var string The database connection used. */
    public string $connection;

    /** @var string The database driver to use when building the database ("mysql", "sqlite" etc). */
    public string $driver;

    /** @var string|null The database host (if relevant). */
    public ?string $host;

    /** @var string|null The name of the database to use. */
    public ?string $database;



    /** @var boolean Whether the database was built remotely or not. */
    public bool $builtRemotely;

    /** @var string|null The remote Adapt installation to send "build" requests to. */
    public ?string $remoteBuildUrl;

    /** @var string|null The type of snapshots being used, depending on whether the database is reused or not. */
    public ?string $resolvedSnapshotType;

    /** @var string|null The type of snapshots being used, when reusing the database. */
    public ?string $reuseDBSnapshotType;

    /** @var string|null The type of snapshots being used, when NOT reusing the database. */
    public ?string $notReuseDBSnapshotType;

    /** @var string The directory to store database snapshots in. */
    public string $storageDir;

    /** @var string[] The files to import before the migrations are run. */
    public array $preMigrationImports;

    /** @var boolean|string Should the migrations be run? / migrations location - if not, the db will be empty. */
    public $migrations;

    /** @var boolean Whether seeding is allowed or not. */
    public bool $isSeedingAllowed;

    /** @var string[] The seeders to run after migrating - will only be run if migrations were run. */
    public array $seeders;

    /** @var boolean When turned on, databases are created for each scenario (based on migrations and seeders etc). */
    public bool $usingScenarios;

    /** @var string|null The calculated build-hash. */
    public ?string $buildHash;

    /** @var string|null The calculated snapshot scenario-hash. */
    public ?string $snapshotHash;

    /** @var string|null The calculated scenario-hash. */
    public ?string $scenarioHash;

    /** @var boolean Is a browser test being run?. */
    public bool $isBrowserTest;

    /** @var boolean Is parallel testing being run? Is just for informational purposes. */
    public bool $isParallelTest;

    /** @var string|null The session-driver being used. */
    public ?string $sessionDriver;

    /** @var boolean When turned on, transactions will be used to allow the database to be reused. */
    public bool $transactionReusable;

    /** @var boolean When turned on, journaling will be used to allow the database to be reused. */
    public bool $journalReusable;

    /** @var boolean When turned on, the database structure and content will be checked after each test. */
    public bool $verifyDatabase;

    /** @var boolean When turned on, the database will be rebuilt instead of allowing it to be reused. */
    public bool $forceRebuild;

    /** @var boolean Whether the database was reused or not (for logging). */
    public bool $databaseWasReused;



    /**
     * Set the project-name.
     *
     * @param string|null $projectName The name of this project.
     * @return static
     */
    public function projectName(?string $projectName): self
    {
        $this->projectName = $projectName;
        return $this;
    }

    /**
     * Set the current test-name.
     *
     * @param string $testName The name of the current test.
     * @return static
     */
    public function testName(string $testName): self
    {
        $this->testName = $testName;
        return $this;
    }

    /**
     * Set the connection used.
     *
     * @param string $connection The database connection to prepare.
     * @return static
     */
    public function connection(string $connection): self
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * Set the database driver to use when building the database ("mysql", "sqlite" etc).
     *
     * @param string $driver The database driver to use.
     * @return static
     */
    public function driver(string $driver): self
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Set the database host.
     *
     * @param string|null $host The database host (if relevant).
     * @return static
     */
    public function host(?string $host): self
    {
        $this->host = $host;
        return $this;
    }

    /**
     * Set the database to use.
     *
     * @param string|null $database The name of the database to use.
     * @return static
     */
    public function database(?string $database): self
    {
        $this->database = $database;
        return $this;
    }

    /**
     * Specify the url to send "remote-build" requests to.
     *
     * @param boolean     $builtRemotely  Whether the database was built remotely or not.
     * @param string|null $remoteBuildUrl The remote Adapt installation to send "build" requests to.
     * @return static
     */
    public function builtRemotely(bool $builtRemotely, ?string $remoteBuildUrl = null): self
    {
        $this->builtRemotely = $builtRemotely;
        $this->remoteBuildUrl = $builtRemotely ? $remoteBuildUrl : null;
        return $this;
    }

    /**
     * Set the type of snapshots being used.
     *
     * @param string|null $resolvedSnapshotType   The type of snapshots being used, depending on whether the database is
     *                                            reused or not.
     * @param string|null $reuseDBSnapshotType    The type of snapshots being used, when reusing the database.
     * @param string|null $notReuseDBSnapshotType The type of snapshots being used, when NOT reusing the database.
     * @return static
     */
    public function snapshotType(
        ?string $resolvedSnapshotType,
        ?string $reuseDBSnapshotType,
        ?string $notReuseDBSnapshotType
    ): self {
        $this->resolvedSnapshotType = $resolvedSnapshotType;
        $this->reuseDBSnapshotType = $reuseDBSnapshotType;
        $this->notReuseDBSnapshotType = $notReuseDBSnapshotType;
        return $this;
    }

    /**
     * Set the directory to store database snapshots in.
     *
     * @param string $storageDir The storage directory to use.
     * @return static
     */
    public function storageDir(string $storageDir): self
    {
        $this->storageDir = $storageDir;
        return $this;
    }

    /**
     * Specify the database dump files to import before migrations run.
     *
     * @param string[] $preMigrationImports The database dump files to import, one per database type.
     * @return static
     */
    public function preMigrationImports(array $preMigrationImports): self
    {
        $this->preMigrationImports = $preMigrationImports;
        return $this;
    }

    /**
     * Turn migrations on or off, or specify the location of the migrations to run.
     *
     * @param boolean|string $migrations Should the migrations be run? / the path of the migrations to run.
     * @return static
     */
    public function migrations($migrations): self
    {
        $this->migrations = $migrations;
        return $this;
    }

    /**
     * Specify the seeders to run.
     *
     * @param boolean  $isSeedingAllowed Whether seeding is allowed or not.
     * @param string[] $seeders          The seeders to run after migrating.
     * @return static
     */
    public function seeders(bool $isSeedingAllowed, array $seeders): self
    {
        $this->isSeedingAllowed = $isSeedingAllowed;
        $this->seeders = $isSeedingAllowed ? $seeders : [];
        return $this;
    }

    /**
     * Turn the scenario-test-dbs setting on (or off).
     *
     * @param boolean     $usingScenarios Create databases as needed for the database-scenario?.
     * @param string|null $buildHash      The calculated build-hash.
     * @param string|null $snapshotHash   The calculated snapshot-hash.
     * @param string|null $scenarioHash   The calculated scenario-hash.
     * @return static
     */
    public function scenarioTestDBs(
        bool $usingScenarios,
        ?string $buildHash,
        ?string $snapshotHash,
        ?string $scenarioHash
    ): self {

        $this->usingScenarios = $usingScenarios;
        $this->buildHash = $buildHash;
        $this->snapshotHash = $snapshotHash;
        $this->scenarioHash = $scenarioHash;
        return $this;
    }

    /**
     * Turn the is-browser-test setting on (or off).
     *
     * @param boolean $isBrowserTest Is this test a browser-test?.
     * @return static
     */
    public function isBrowserTest(bool $isBrowserTest): self
    {
        $this->isBrowserTest = $isBrowserTest;
        return $this;
    }

    /**
     * Turn the is-parallel-test setting on or off (is just for informational purposes).
     *
     * @param boolean $isParallelTest Is parallel testing being run?.
     * @return static
     */
    public function isParallelTest(bool $isParallelTest): self
    {
        $this->isParallelTest = $isParallelTest;
        return $this;
    }

    /**
     * Set the session-driver that's being used.
     *
     * @param string|null $sessionDriver The session-driver being used.
     * @return static
     */
    public function sessionDriver(?string $sessionDriver): self
    {
        $this->sessionDriver = $sessionDriver;
        return $this;
    }

    /**
     * Turn the transaction-reusable setting on (or off).
     *
     * @param boolean $transactionReusable Are transactions going to be used to allow reuse?.
     * @return static
     */
    public function transactionReusable(bool $transactionReusable): self
    {
        $this->transactionReusable = $transactionReusable;
        return $this;
    }

    /**
     * Turn the journal-reusable setting on (or off).
     *
     * @param boolean $journalReusable Are transactions going to be used to allow reuse?.
     * @return static
     */
    public function journalReusable(bool $journalReusable): self
    {
        $this->journalReusable = $journalReusable;
        return $this;
    }

    /**
     * Turn the database verification setting on (or off).
     *
     * @param boolean $verifyDatabase Perform a check of the db structure and content after each test?.
     * @return static
     */
    public function verifyDatabase(bool $verifyDatabase): self
    {
        $this->verifyDatabase = $verifyDatabase;
        return $this;
    }

    /**
     * Turn the reusable-database setting on (or off).
     *
     * @param boolean $forceRebuild Was the database forced to be rebuilt?.
     * @return static
     */
    public function forceRebuild(bool $forceRebuild): self
    {
        $this->forceRebuild = $forceRebuild;
        return $this;
    }

    /**
     * Record whether the database was reused or not.
     *
     * @param boolean $databaseWasReused Whether the database was reused or not.
     * @return static
     */
    public function databaseWasReused(bool $databaseWasReused): self
    {
        $this->databaseWasReused = $databaseWasReused;
        return $this;
    }





    /**
     * Build a new ResolvedSettingsDTO from the data given in a response from a remote Adapt installation.
     *
     * @param string $payload The raw ResolvedSettingsDTO data from the response.
     * @return self
     * @throws AdaptRemoteShareException When the payload couldn't be interpreted.
     */
    public static function buildFromPayload(string $payload): self
    {
        if (!mb_strlen($payload)) {
            throw AdaptRemoteShareException::couldNotReadResolvedSettingsDTO();
        }

        $values = json_decode($payload, true);
        if (!is_array($values)) {
            throw AdaptRemoteShareException::couldNotReadResolvedSettingsDTO();
        }

        return static::buildFromArray($values);
    }

    /**
     * Build the value to send in responses.
     *
     * @return string
     */
    public function buildPayload(): string
    {
        return (string) json_encode(get_object_vars($this));
    }



    /**
     * Render the "build settings" ready to be logged.
     *
     * @return array<string, string>
     */
    public function renderBuildSources(): array
    {
        $remoteExtra = $this->builtRemotely ? ' (remote)' : '';

        $snapshotsEnabled = $this->resolvedSnapshotType
            ? "Yes - \"$this->resolvedSnapshotType\"" . $remoteExtra
            : 'No';

        $storageDir = $this->resolvedSnapshotType
            ? $this->escapeString($this->storageDir) . $remoteExtra
            : null;

        $preMigrationImportsTitle = count($this->preMigrationImports) == 1
            ? 'Pre-migration import:'
            : 'Pre-migration imports:';

        $migrations = is_bool($this->migrations)
            ? ($this->migrations ? 'Yes' : 'No')
            : "\"$this->migrations\"";
        $migrations .= $remoteExtra;

        $seedersTitle = $this->isSeedingAllowed && count($this->seeders) == 1 ? 'Seeder:' : 'Seeders:';
        $seeders = $this->isSeedingAllowed
            ? $this->renderList($this->seeders, $remoteExtra)
            : 'n/a';

        return array_filter([
            'Remote-build url:' => $this->escapeString($this->remoteBuildUrl),
            $preMigrationImportsTitle => $this->renderList($this->preMigrationImports, $remoteExtra),
            'Migrations:' => $migrations,
            $seedersTitle => $seeders,
            'Snapshots enabled?' => $snapshotsEnabled,
            'Storage dir:' => $storageDir,
        ]);
    }



    /**
     * Render the "build-environment" settings ready to be logged.
     *
     * @return array<string, string>
     */
    public function renderBuildEnvironmentSettings(): array
    {
        $isBrowserTest = $this->renderBoolean(
            $this->isBrowserTest,
            "Yes - NOTE: the session-driver is \"$this->sessionDriver\""
        );

        $reuseTypes = array_filter([
            $this->transactionReusable ? 'transaction' : '',
            $this->journalReusable ? 'journal' : '',
        ]);
        $isReusable = $this->renderBoolean(
            (bool) count($reuseTypes),
            'Yes - ' . implode(', ', $reuseTypes),
            'No, it will be rebuilt for each test'
        );

        return array_filter([
            'Project name:' => $this->escapeString($this->projectName),
            'Using scenarios?' => $this->renderBoolean($this->usingScenarios),
            'Database is reusable?' => $isReusable,
//            '- Force-rebuild?' => $this->renderBoolean($this->forceRebuild),
            'Verify database after?' => $this->renderBoolean($this->verifyDatabase),
            'For a browser-test?' => $isBrowserTest,
            'Parallel testing?' => $this->renderBoolean($this->isParallelTest),
            'Build-hash:' => $this->escapeString($this->buildHash, 'n/a'),
            'Snapshot-hash:' => $this->escapeString($this->snapshotHash, 'n/a'),
            'Scenario-hash:' => $this->escapeString($this->scenarioHash, 'n/a'),
        ]);
    }

    /**
     * Render the "resolved database" details ready to be logged.
     *
     * @return array<string, string>
     */
    public function renderResolvedDatabaseSettings(): array
    {
        return array_filter([
            'Connection:' => $this->escapeString($this->connection),
            'Driver:' => $this->escapeString($this->driver),
            'Host:' => $this->escapeString($this->host),
            'Database:' => $this->escapeString($this->database),
        ]);
    }

    /**
     * Escape a string.
     *
     * @param string|null $value   The value to escape.
     * @param string|null $default A default to use if it's empty.
     * @return string|null
     */
    private function escapeString(?string $value, ?string $default = null): ?string
    {
        return mb_strlen((string) $value)
            ? "\"$value\""
            : $default;
    }

    /**
     * Escape a string.
     *
     * @param boolean $value      The boolean value to render.
     * @param string  $trueValue  The string to use when true.
     * @param string  $falseValue The string to use when false.
     * @return string
     */
    private function renderBoolean(bool $value, string $trueValue = 'Yes', string $falseValue = 'No'): string
    {
        return $value ? $trueValue : $falseValue;
    }

    /**
     * Render a list of things, or "None" when empty.
     *
     * @param string[] $things      The things to render.
     * @param string   $remoteExtra The text to add when being handled remotely.
     * @return string
     */
    private function renderList(array $things, string $remoteExtra): string
    {
        $newThings = [];
        foreach ($things as $thing) {
            $newThings[] = "\"$thing\"" . $remoteExtra;
        }

        return count($newThings)
            ? implode(PHP_EOL, $newThings)
            : 'None';
    }
}
