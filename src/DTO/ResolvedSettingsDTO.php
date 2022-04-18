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



    /** @var string The name of the current project. */
    public $projectName;

    /** @var string The name of the current test. */
    public $testName;



    /** @var string The database connection used. */
    public $connection;

    /** @var string The database driver to use when building the database ("mysql", "sqlite" etc). */
    public $driver;

    /** @var string|null The database host (if relevant). */
    public $host;

    /** @var string|null The name of the database to use. */
    public $database;



    /** @var boolean Whether the database was built remotely or not. */
    public $builtRemotely;

    /** @var string|null The remote Adapt installation to send "build" requests to. */
    public $remoteBuildUrl;

    /** @var string|null The type of snapshots being used, depending on whether the database is reused or not. */
    public $resolvedSnapshotType;

    /** @var string|null The type of snapshots being used, when reusing the database. */
    public $reuseDBSnapshotType;

    /** @var string|null The type of snapshots being used, when NOT reusing the database. */
    public $notReuseDBSnapshotType;

    /** @var string The directory to store database snapshots in. */
    public $storageDir;

    /** @var string[] The files to import before the migrations are run. */
    public $preMigrationImports;

    /** @var boolean|string Should the migrations be run? / migrations location - if not, the db will be empty. */
    public $migrations;

    /** @var boolean Whether seeding is allowed or not. */
    public $isSeedingAllowed;

    /** @var string[] The seeders to run after migrating - will only be run if migrations were run. */
    public $seeders;

    /** @var boolean When turned on, databases are created for each scenario (based on migrations and seeders etc). */
    public $usingScenarios;

    /** @var string|null The calculated build-hash. */
    public $buildHash;

    /** @var string|null The calculated snapshot scenario-hash. */
    public $snapshotHash;

    /** @var string|null The calculated scenario-hash. */
    public $scenarioHash;

    /** @var boolean Is a browser test being run?. */
    public $isBrowserTest;

    /** @var string|null The session-driver being used. */
    public $sessionDriver;

    /** @var boolean When turned on, transactions will be used to allow the database to be reused. */
    public $transactionReusable;

    /** @var boolean When turned on, journaling will be used to allow the database to be reused. */
    public $journalReusable;

    /** @var boolean When turned on, the database structure and content will be checked after each test. */
    public $verifyDatabase;

    /** @var boolean When turned on, the database will be rebuilt instead of allowing it to be reused. */
    public $forceRebuild;



    /**
     * Set the project-name.
     *
     * @param string $projectName The name of this project.
     * @return static
     */
    public function projectName($projectName): self
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
    public function testName($testName): self
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
    public function connection($connection): self
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
    public function driver($driver): self
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
    public function host($host): self
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
    public function database($database): self
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
    public function builtRemotely($builtRemotely, $remoteBuildUrl = null): self
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
        $resolvedSnapshotType,
        $reuseDBSnapshotType,
        $notReuseDBSnapshotType
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
    public function storageDir($storageDir): self
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
    public function preMigrationImports($preMigrationImports): self
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
    public function seeders($isSeedingAllowed, $seeders): self
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
        $usingScenarios,
        $buildHash,
        $snapshotHash,
        $scenarioHash
    ): self {

        $this->usingScenarios = $usingScenarios;
        $this->buildHash = $this->usingScenarios ? $buildHash : null;
        $this->snapshotHash = $this->usingScenarios ? $snapshotHash : null;
        $this->scenarioHash = $this->usingScenarios ? $scenarioHash : null;
        return $this;
    }

    /**
     * Turn the is-browser-test setting on (or off).
     *
     * @param boolean $isBrowserTest Is this test a browser-test?.
     * @return static
     */
    public function isBrowserTest($isBrowserTest): self
    {
        $this->isBrowserTest = $isBrowserTest;
        return $this;
    }

    /**
     * Set the session-driver that's being used.
     *
     * @param string|null $sessionDriver The session-driver being used.
     * @return static
     */
    public function sessionDriver($sessionDriver): self
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
    public function transactionReusable($transactionReusable): self
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
    public function journalReusable($journalReusable): self
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
    public function verifyDatabase($verifyDatabase): self
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
    public function forceRebuild($forceRebuild): self
    {
        $this->forceRebuild = $forceRebuild;
        return $this;
    }





    /**
     * Build a new ResolvedSettingsDTO from the data given in a response from a remote Adapt installation.
     *
     * @param string $payload The raw ResolvedSettingsDTO data from the response.
     * @return self
     * @throws AdaptRemoteShareException When the version doesn't match.
     */
    public static function buildFromPayload($payload): self
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
    public function renderBuildSettings(): array
    {
        $remoteExtra = $this->builtRemotely ? ' (remote)' : '';

        $snapshotsEnabled =
            $this->renderBoolean((bool) $this->resolvedSnapshotType)
            . ($this->resolvedSnapshotType
                ? ' - ' . $this->escapeString($this->resolvedSnapshotType)
                : '');

        $storageDir = $this->resolvedSnapshotType
            ? $this->escapeString($this->storageDir) . $remoteExtra
            : null;

        $migrations = is_bool($this->migrations)
            ? ($this->migrations ? 'Yes' : 'No')
            : "\"" . $this->migrations . "\"";
        $migrations .= $remoteExtra;

        $seeders = $this->isSeedingAllowed
            ? $this->renderList($this->seeders, $remoteExtra)
            : 'n/a';

        $preMigrationImportsTitle = count($this->preMigrationImports) == 1
            ? 'Pre-migration import:'
            : 'Pre-migration imports:';

        $seedersTitle = $this->isSeedingAllowed && count($this->seeders) == 1 ? 'Seeder:' : 'Seeders:';

        $isBrowserTest = $this->renderBoolean($this->isBrowserTest, "Yes (session-driver: \"$this->sessionDriver\")");

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
            'Project name:' => $this->escapeString($this->projectName, 'n/a'),
            'Remote-build url:' => $this->escapeString($this->remoteBuildUrl),
            'Snapshots enabled?' => $snapshotsEnabled,
            '- When reusing db?' => $this->escapeString($this->reuseDBSnapshotType, 'No'),
            '- When not reusing db?' => $this->escapeString($this->notReuseDBSnapshotType, 'No'),
            'Snapshot storage dir:' => $storageDir,
            $preMigrationImportsTitle => $this->renderList($this->preMigrationImports, $remoteExtra),
            'Migrations:' => $migrations,
            $seedersTitle => $seeders,
            'Is a browser-test?' => $isBrowserTest,
//            'Is reusable?' => $this->renderBoolean($this->databaseIsReusable),
            'Is reusable?' => $isReusable,
//            '- Force-rebuild?' => $this->renderBoolean($this->forceRebuild),
            'Verify database?' => $this->renderBoolean($this->verifyDatabase),
            'Using scenarios?' => $this->renderBoolean($this->usingScenarios),
            '- Build-hash:' => $this->escapeString($this->buildHash, 'n/a'),
            '- Snapshot-hash:' => $this->escapeString($this->snapshotHash),
            '- Scenario-hash:' => $this->escapeString($this->scenarioHash),
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
    private function escapeString($value, $default = null)
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
