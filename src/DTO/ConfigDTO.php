<?php

namespace CodeDistortion\Adapt\DTO;

use CodeDistortion\Adapt\DTO\Traits\DTOBuildTrait;
use CodeDistortion\Adapt\Exceptions\AdaptRemoteShareException;
use CodeDistortion\Adapt\Support\Settings;

/**
 * Resolves default setting values when needed.
 */
class ConfigDTO
{
    use DTOBuildTrait;

    /**
     * The ConfigDTO version. An exception will be thrown when there's a mis-match between installations of Adapt.
     *
     * @var integer
     */
    public $dtoVersion;

    /** @var string The name of the current project. */
    public $projectName;

    /** @var string The name of the current test. */
    public $testName;


    /** @var string The database connection to prepare. */
    public $connection;

    /** @var boolean Whether the connection exists or not (it's ok to not exist locally when the building remotely). */
    public $connectionExists;

    /** @var string|null The database driver to use when building the database ("mysql", "sqlite" etc). */
    public $driver;

    /** @var string|null The name of the database to use. */
    public $database;

    /** @var string A database name modifier (e.g. Paratest adds a TEST_TOKEN env value to make the db unique). */
    public $databaseModifier = '';


    /** @var string The directory to store database snapshots in. */
    public $storageDir;

    /** @var string The prefix to add to snapshot filenames. */
    public $snapshotPrefix;

    /** @var string The prefix to add to database names. */
    public $databasePrefix;

    /** @var string[] The files and directories to look through. Changes to files will invalidate the snapshots. */
    public $hashPaths;


    /** @var string[]|string[][] The files to import before the migrations are run. */
    public $preMigrationImports;

    /** @var boolean|string Should the migrations be run? / migrations location - if not, the db will be empty. */
    public $migrations;

    /** @var string[] The seeders to run after migrating - will only be run if migrations were run. */
    public $seeders;

    /** @var string|null The remote Adapt installation to send "build" requests to. */
    public $remoteBuildUrl;

    /** @var boolean Is a browser test being run?. When true, this will turn off $reuseTestDBs and $scenarioTestDBs. */
    public $isBrowserTest;

    /** @var boolean Is this process building a db locally for another remote Adapt installation?. */
    public $isRemoteBuild;

    /**
     * The session driver being used - will throw and exception when the remote version is different to
     * $remoteCallerSessionDriver.
     *
     * @var string
     */
    public $sessionDriver;

    /** @var string|null The session driver being used in the caller Adapt installation. */
    public $remoteCallerSessionDriver;


    /** @var boolean When turned on, databases will be reused when possible instead of rebuilding them. */
    public $reuseTestDBs;

    /** @var boolean When turned on, dbs will be created for each scenario (based on migrations and seeders etc). */
    public $scenarioTestDBs;

    /** @var string|boolean Enable snapshots, and specify when to take them - when reusing the database. */
    public $useSnapshotsWhenReusingDB;

    /** @var string|boolean Enable snapshots, and specify when to take them - when NOT reusing the database. */
    public $useSnapshotsWhenNotReusingDB;


    /** @var string The path to the "mysql" executable. */
    public $mysqlExecutablePath;

    /** @var string The path to the "mysqldump" executable. */
    public $mysqldumpExecutablePath;

    /** @var string The path to the "psql" executable. */
    public $psqlExecutablePath;

    /** @var string The path to the "pg_dump" executable. */
    public $pgDumpExecutablePath;


    /** @var integer The number of seconds grace-period before stale databases and snapshots are to be deleted. */
    public $staleGraceSeconds = 0;



    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->version(Settings::CONFIG_DTO_VERSION);
    }



    /**
     * Set the ConfigDTO version.
     *
     * @param integer $version The ConfigDTO version.
     * @return static
     */
    public function version($version): self
    {
        $this->dtoVersion = $version;
        return $this;
    }

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
     * Set the connection to prepare.
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
     * Set the connectionExists value.
     *
     * @param boolean $connectionExists Whether the connection exists or not (it's ok to not exist locally when the
     *                                  building remotely).
     * @return static
     */
    public function connectionExists($connectionExists): self
    {
        $this->connectionExists = $connectionExists;
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
     * Set the database-modifier to use (e.g. Paratest adds a TEST_TOKEN env value to make the db unique).
     *
     * @param string $databaseModifier The modifier to use.
     * @return static
     */
    public function databaseModifier($databaseModifier): self
    {
        $this->databaseModifier = $databaseModifier;
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
     * Set the prefix to add to snapshot filenames.
     *
     * @param string $snapshotPrefix The prefix to use.
     * @return static
     */
    public function snapshotPrefix($snapshotPrefix): self
    {
        $this->snapshotPrefix = $snapshotPrefix;
        return $this;
    }

    /**
     * Set the prefix to add to database names.
     *
     * @param string $databasePrefix The prefix to use.
     * @return static
     */
    public function databasePrefix($databasePrefix): self
    {
        $this->databasePrefix = $databasePrefix;
        return $this;
    }

    /**
     * Set the list of directories that can invalidate test-databases and snapshots.
     *
     * @param string[] $hashPaths The files and directories to look through.
     * @return static
     */
    public function hashPaths($hashPaths): self
    {
        $this->hashPaths = $hashPaths;
        return $this;
    }

    /**
     * Set the details that affect what is being built (i.e. the database-scenario).
     *
     * @param string[]|string[][] $preMigrationImports       The files to import before the migrations are run.
     * @param boolean|string      $migrations                Should the migrations be run? / the path of the migrations
     *                                                       to run.
     * @param string[]            $seeders                   The seeders to run after migrating.
     * @param string|null         $remoteBuildUrl            The remote Adapt installation to send "build" requests to.
     * @param boolean             $isBrowserTest             Is a browser test running?.
     * @param boolean             $isRemoteBuild             Is this process building a db for another Adapt
     *                                                       installation?.
     * @param string              $sessionDriver             The session driver being used.
     * @param string|null         $remoteCallerSessionDriver The session driver being used in the caller Adapt
     *                                                       installation.
     * @return static
     */
    public function buildSettings(
        $preMigrationImports,
        $migrations,
        $seeders,
        $remoteBuildUrl,
        $isBrowserTest,
        $isRemoteBuild,
        $sessionDriver,
        $remoteCallerSessionDriver
    ): self {

        $this->preMigrationImports = $preMigrationImports;
        $this->migrations = $migrations;
        $this->seeders = $seeders;
        $this->remoteBuildUrl = $remoteBuildUrl;
        $this->isBrowserTest = $isBrowserTest;
        $this->isRemoteBuild = $isRemoteBuild;
        $this->sessionDriver = $sessionDriver;
        $this->remoteCallerSessionDriver = $remoteCallerSessionDriver;
        return $this;
    }

    /**
     * Specify the database dump files to import before migrations run.
     *
     * @param string[]|string[][] $preMigrationImports The database dump files to import, one per database type.
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
        $this->migrations = false;
        if ((is_string($migrations) && (mb_strlen($migrations))) || (is_bool($migrations))) {
            $this->migrations = $migrations;
        }
        return $this;
    }

    /**
     * Specify the seeders to run.
     *
     * @param string[] $seeders The seeders to run after migrating.
     * @return static
     */
    public function seeders($seeders): self
    {
        $this->seeders = $seeders;
        return $this;
    }

    /**
     * Specify the url to send "build" requests to.
     *
     * @param string|null $remoteBuildUrl The remote Adapt installation to send "build" requests to.
     * @return static
     */
    public function remoteBuildUrl($remoteBuildUrl): self
    {
        $this->remoteBuildUrl = $remoteBuildUrl;
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
     * Turn the is-remote-build setting on (or off).
     *
     * @param boolean $isRemoteBuild Is this process building a db for another Adapt installation?.
     * @return static
     */
    public function isRemoteBuild($isRemoteBuild): self
    {
        $this->isRemoteBuild = $isRemoteBuild;
        return $this;
    }

    /**
     * Set the session-driver.
     *
     * @param string $sessionDriver The session driver being used.
     * @return static
     */
    public function sessionDriver($sessionDriver): self
    {
        $this->sessionDriver = $sessionDriver;
        return $this;
    }

    /**
     * Set the caller Adapt session-driver.
     *
     * @param string|null $remoteCallerSessionDriver The session driver being used.
     * @return static
     */
    public function remoteCallerSessionDriver($remoteCallerSessionDriver): self
    {
        $this->remoteCallerSessionDriver = $remoteCallerSessionDriver;
        return $this;
    }

    /**
     * Set the types of cache to use.
     *
     * @param boolean $reuseTestDBs    Reuse databases when possible (instead of rebuilding them)?.
     * @param boolean $scenarioTestDBs Create databases as needed for the database-scenario?.
     * @return static
     */
    public function cacheTools(
        $reuseTestDBs,
        $scenarioTestDBs
    ): self {
        $this->reuseTestDBs = $reuseTestDBs;
        $this->scenarioTestDBs = $scenarioTestDBs;
        return $this;
    }

    /**
     * Turn the reuse-test-dbs setting on (or off).
     *
     * @param boolean $reuseTestDBs Reuse existing databases?.
     * @return static
     */
    public function reuseTestDBs($reuseTestDBs): self
    {
        $this->reuseTestDBs = $reuseTestDBs;
        return $this;
    }

    /**
     * Turn the scenario-test-dbs setting on (or off).
     *
     * @param boolean $scenarioTestDBs Create databases as needed for the database-scenario?.
     * @return static
     */
    public function scenarioTestDBs($scenarioTestDBs): self
    {
        $this->scenarioTestDBs = $scenarioTestDBs;
        return $this;
    }

    /**
     * Set the snapshot settings.
     *
     * @param string|boolean $useSnapshotsWhenReusingDB    Take and import snapshots when reusing databases?
     *                                                     false, 'afterMigrations', 'afterSeeders', 'both'.
     * @param string|boolean $useSnapshotsWhenNotReusingDB Take and import snapshots when NOT reusing databases?
     *                                                     false, 'afterMigrations', 'afterSeeders', 'both'.
     * @return static
     */
    public function snapshots(
        $useSnapshotsWhenReusingDB,
        $useSnapshotsWhenNotReusingDB
    ): self {
        $this->useSnapshotsWhenReusingDB = $useSnapshotsWhenReusingDB;
        $this->useSnapshotsWhenNotReusingDB = $useSnapshotsWhenNotReusingDB;
        return $this;
    }

    /**
     * Set the mysql specific details.
     *
     * @param string $mysqlExecutablePath     The path to the "mysql" executable.
     * @param string $mysqldumpExecutablePath The path to the "mysqldump" executable.
     * @return static
     */
    public function mysqlSettings(
        $mysqlExecutablePath,
        $mysqldumpExecutablePath
    ): self {
        $this->mysqlExecutablePath = $mysqlExecutablePath;
        $this->mysqldumpExecutablePath = $mysqldumpExecutablePath;
        return $this;
    }

    /**
     * Set the postgres specific details.
     *
     * @param string $psqlExecutablePath   The path to the "psql" executable.
     * @param string $pgDumpExecutablePath The path to the "pg_dump" executable.
     * @return static
     */
    public function postgresSettings(
        $psqlExecutablePath,
        $pgDumpExecutablePath
    ): self {
        $this->psqlExecutablePath = $psqlExecutablePath;
        $this->pgDumpExecutablePath = $pgDumpExecutablePath;
        return $this;
    }



    /**
     * Set the number of seconds grace-period before stale databases and snapshots are to be deleted.
     *
     * @param integer $staleGraceSeconds The number of seconds.
     * @return static
     */
    public function staleGraceSeconds($staleGraceSeconds): self
    {
        $this->staleGraceSeconds = $staleGraceSeconds;
        return $this;
    }





    /**
     * Determine the seeders that need to be used.
     *
     * @return string[]
     */
    public function pickSeedersToInclude(): array
    {
        return $this->migrations ? $this->seeders : [];
    }

    /**
     * Pick the database dumps to import before the migrations run.
     *
     * @return string[]
     */
    public function pickPreMigrationImports(): array
    {
        $preMigrationImports = $this->preMigrationImports;
        $driver = $this->driver;

        $usePaths = [];
        if (isset($preMigrationImports[$driver])) {

            $paths = $preMigrationImports[$driver];
            $paths = is_string($paths) ? [$paths] : $paths;

            if (is_array($paths)) {
                foreach ($paths as $path) {
                    if (mb_strlen($path)) {
                        $usePaths[] = $path;
                    }
                }
            }
        }
        return $usePaths;
    }





    /**
     * Build a new ConfigDTO from the data given in a request to build the database remotely.
     *
     * @param string $payload The raw ConfigDTO data from the request.
     * @return $this|null
     * @throws AdaptRemoteShareException When the version doesn't match.
     */
    public static function buildFromPayload($payload)
    {
        if (!mb_strlen($payload)) {
            return null;
        }

        $values = json_decode($payload, true);
        if (!is_array($values)) {
            throw AdaptRemoteShareException::couldNotReadConfigDTO();
        }

        $configDTO = static::buildFromArray($values);

        if ($configDTO->dtoVersion != Settings::CONFIG_DTO_VERSION) {
            throw AdaptRemoteShareException::versionMismatch();
        }

        return $configDTO;
    }

    /**
     * Build the value to send in requests.
     *
     * @return string
     */
    public function buildPayload(): string
    {
        return json_encode(get_object_vars($this));
    }
}
