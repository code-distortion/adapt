<?php

namespace CodeDistortion\Adapt\DTO;

/**
 * Resolves default setting values when needed.
 */
class ConfigDTO
{
    /**
     * The name of the current project.
     *
     * @var string
     */
    public $projectName;


    /**
     * The database connection to prepare.
     *
     * @var string
     */
    public $connection;

    /**
     * The database driver to use when building the database ("mysql", "sqlite" etc).
     *
     * @var string|null
     */
    public $driver = null;

    /**
     * The name of the database to use.
     *
     * @var string|null
     */
    public $database = null;

    /**
     * A modifier on the database name (eg. Paratest adds a TEST_TOKEN env setting to make the database unique).
     *
     * @var string
     */
    public $databaseModifier = '';


    /**
     * The directory to store database snapshots in.
     *
     * @var string
     */
    public $storageDir;

    /**
     * The prefix to add to snapshot filenames.
     *
     * @var string
     */
    public $snapshotPrefix;

    /**
     * The prefix to add to database names.
     *
     * @var string
     */
    public $databasePrefix;

    /**
     * The files and directories to look through. Changes to files will invalidate the snapshots.
     *
     * @var string[]
     */
    public $hashPaths;


    /**
     * The files to import before the migrations are run.
     *
     * @var string[]|string[][]
     */
    public $preMigrationImports;

    /**
     * Should the migrations be run? / the location of the migrations to run - if not then the database will be empty.
     *
     * @var boolean|string
     */
    public $migrations;

    /**
     * The seeders to run after migrating - will only be run if migrations were run.
     *
     * @var string[]
     */
    public $seeders;

    /**
     * Is a browser test being run?.
     *
     * When true, this will turn off $reuseTestDBs, $scenarioTestDBs and $transactionRollback.
     *
     * @var boolean
     */
    public $isBrowserTest;


    /**
     * When turned on, databases will be reused when possible instead of rebuilding them.
     *
     * @var boolean
     */
    public $reuseTestDBs;

    /**
     * When turned on, databases will be created for each scenario (based on migrations and seeders etc).
     *
     * @var boolean
     */
    public $scenarioTestDBs;

    /**
     * Should tests be encapsulated within transactions?.
     *
     * @var boolean
     */
    public $transactionRollback;

    /**
     * When turned on, snapshot files will created and imported when available.
     *
     * @var boolean
     */
    public $snapshotsEnabled;

    /**
     * When turned on, a snapshot (dump/copy) of the database will be taken after migrations have been run.
     *
     * @var boolean
     */
    public $takeSnapshotAfterMigrations;

    /**
     * When turned on, a snapshot (dump/copy) of the database will be taken after seeders have been run.
     *
     * @var boolean
     */
    public $takeSnapshotAfterSeeders;


    /**
     * The path to the "mysql" executable.
     *
     * @var string
     */
    public $mysqlExecutablePath;

    /**
     * The path to the "mysqldump" executable.
     *
     * @var string
     */
    public $mysqldumpExecutablePath;

    /**
     * The path to the "psql" executable.
     *
     * @var string
     */
    public $psqlExecutablePath;

    /**
     * The path to the "pg_dump" executable.
     *
     * @var string
     */
    public $pgDumpExecutablePath;


    /**
     * Set the the project-name.
     *
     * @param string $projectName The name of this project.
     * @return static
     */
    public function projectName(string $projectName): self
    {
        $this->projectName = $projectName;
        return $this;
    }

    /**
     * Set the connection to prepare.
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
     * Set the database to use.
     *
     * @param string $database The name of the database to use.
     * @return static
     */
    public function database(string $database): self
    {
        $this->database = $database;
        return $this;
    }

    /**
     * Set the database-modifier to use.
     *
     * @param string $databaseModifier The modifier to use.
     * @return static
     */
    public function databaseModifier(string $databaseModifier): self
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
    public function storageDir(string $storageDir): self
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
    public function snapshotPrefix(string $snapshotPrefix): self
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
    public function databasePrefix(string $databasePrefix): self
    {
        $this->databasePrefix = $databasePrefix;
        return $this;
    }

    /**
     * Set the the list of directories that can invalidate test-databases and snapshots.
     *
     * @param string[] $hashPaths The files and directories to look through.
     * @return static
     */
    public function hashPaths(array $hashPaths): self
    {
        $this->hashPaths = $hashPaths;
        return $this;
    }

    /**
     * Set the details that affect what is being built (ie. the database-scenario).
     *
     * @param string[]|string[][] $preMigrationImports The files to import before the migrations are run.
     * @param boolean|string      $migrations          Should the migrations be run? / the path of the migrations to
     *                                                 run.
     * @param string[]            $seeders             The seeders to run after migrating.
     * @param boolean             $isBrowserTest       Is a browser test running?.
     * @return static
     */
    public function buildSettings(
        array $preMigrationImports,
        $migrations,
        array $seeders,
        bool $isBrowserTest
    ): self {
        $this->preMigrationImports = $preMigrationImports;
        $this->migrations = $migrations;
        $this->seeders = $seeders;
        $this->isBrowserTest = $isBrowserTest;
        return $this;
    }

    /**
     * Specify the database dump files to import before migrations run.
     *
     * @param string[]|string[][] $preMigrationImports The database dump files to import, one per database type.
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
    public function seeders(array $seeders): self
    {
        $this->seeders = $seeders;
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
     * Set the types of cache to use.
     *
     * @param boolean $reuseTestDBs        Reuse databases when possible (instead of rebuilding them)?.
     * @param boolean $scenarioTestDBs     Create databases as needed for the database-scenario?.
     * @param boolean $transactionRollback Should tests be encapsulated within transactions?.
     * @return static
     */
    public function cacheTools(
        bool $reuseTestDBs,
        bool $scenarioTestDBs,
        bool $transactionRollback
    ): self {
        $this->reuseTestDBs = $reuseTestDBs;
        $this->scenarioTestDBs = $scenarioTestDBs;
        $this->transactionRollback = $transactionRollback;
        return $this;
    }

    /**
     * Turn the reuse-test-dbs setting on (or off).
     *
     * @param boolean $reuseTestDBs Reuse existing databases?.
     * @return static
     */
    public function reuseTestDBs(bool $reuseTestDBs): self
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
    public function scenarioTestDBs(bool $scenarioTestDBs): self
    {
        $this->scenarioTestDBs = $scenarioTestDBs;
        return $this;
    }

    /**
     * Turn transactions on or off.
     *
     * @param boolean $transactionRollback Should tests be encapsulated within transactions?.
     * @return static
     */
    public function transactionRollback(bool $transactionRollback): self
    {
        $this->transactionRollback = $transactionRollback;
        return $this;
    }

    /**
     * Set the the snapshot settings.
     *
     * @param boolean $snapshotsEnabled            Is the snapshot feature enabled?.
     * @param boolean $takeSnapshotAfterMigrations Take a snapshot of the database after migrations have been run?.
     * @param boolean $takeSnapshotAfterSeeders    Take a snapshot of the database after seeders have been run?.
     * @return static
     */
    public function snapshots(
        bool $snapshotsEnabled,
        bool $takeSnapshotAfterMigrations,
        bool $takeSnapshotAfterSeeders
    ): self {
        $this->snapshotsEnabled = $snapshotsEnabled;
        $this->takeSnapshotAfterMigrations = $takeSnapshotAfterMigrations;
        $this->takeSnapshotAfterSeeders = $takeSnapshotAfterSeeders;
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
        string $mysqlExecutablePath,
        string $mysqldumpExecutablePath
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
        string $psqlExecutablePath,
        string $pgDumpExecutablePath
    ): self {
        $this->psqlExecutablePath = $psqlExecutablePath;
        $this->pgDumpExecutablePath = $pgDumpExecutablePath;
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
    public function pickPreMigrationDumps(): array
    {
        $preMigrationImports = $this->preMigrationImports;
        $driver = $this->driver;

        $usePaths = [];
        if (isset($preMigrationImports[$driver])) {

            $paths = $preMigrationImports[$driver];
            $paths = (is_string($paths) ? [$paths] : $paths);

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
}
