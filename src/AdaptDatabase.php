<?php

namespace CodeDistortion\Adapt;

use CodeDistortion\Adapt\Initialise\InitialiseAdapt;

/**
 * Allow your tests to use Adapt.
 *
 * Add this trait to the test-classes you'd like Adapt to apply to.
 *
 * The properties below can be set in your test-class to alter how the
 * test-database is set up. Or alternatively, more customisation is possible
 * by adding the databaseInit(…) method (shown at the bottom below).
 *
 * @see InitialiseAdapt
 */
trait AdaptDatabase
{
    use InitialiseAdapt;

    /**
     * Enable / disable database building. This is useful when you want to use
     * Adapt to handle your Browser (Dusk) tests but don't have a database.
     *
     * @var boolean
     */
//    protected $buildDatabases = true;

    /**
     * Let Adapt re-use databases using a transaction.
     *
     * @var boolean
     */
//    protected bool $reuseTransaction = true;

    /**
     * Let Adapt re-use databases using journaling (MySQL only).
     *
     * @var boolean
     */
//    protected bool $reuseJournal = true;

    /**
     * Let Adapt create databases dynamically (with distinct names) based on
     * the scenario.
     *
     * @var boolean
     */
//    protected bool $scenarioTestDBs = true;

    /**
     * Enable snapshots, and specify when to take them - when reusing the
     * database.
     *
     * false, 'afterMigrations', 'afterSeeders', 'both'.
     *
     * @var string|boolean
     */
//    protected $useSnapshotsWhenReusingDB = 'afterMigrations';

    /**
     * Enable snapshots, and specify when to take them - when NOT reusing the
     * database.
     *
     * false, 'afterMigrations', 'afterSeeders', 'both'.
     *
     * @var string|boolean
     */
//    protected $useSnapshotsWhenNotReusingDB = 'afterMigrations';

    /**
     * Specify database dump files to import before migrations run.
     *
     * NOTE: It's important that these dumps don't contain output from seeders
     * if those seeders are to also be run by Adapt afterwards.
     *
     * NOTE: initial_imports aren't available for SQLite :memory:
     * databases.
     *
     * @var array<string, string>|array<string, string[]>
     */
//    protected array $initialImports = [
//        'mysql' => ['database/dumps/mysql/my-database.sql'],
//        'pgsql' => ['database/dumps/pgsql/my-database.sql'],
//        'sqlite' => ['database/dumps/sqlite/my-database.sqlite'], // SQLite files are simply copied
//    ];

    /**
     * Specify whether to run migrations or not. You can also specify the
     * location of the migrations to run.
     *
     * @var boolean|string
     */
//    protected bool $migrations = true;
//    or
//    protected string $migrations = 'database/migrations';

    /**
     * Specify the seeders to run (they will only be run if migrations are
     * run).
     *
     * @var string|string[]
     */
//    protected string $seeders = 'DatabaseSeeder';
//    or
//    protected array $seeders = ['DatabaseSeeder'];

    /**
     * Overwrite the details of certain database connections with values from
     * others.
     *
     * e.g. overwrite the "mysql" connection with the "sqlite" connection's
     * details so SQLite is used instead.
     *
     * @var string
     */
//    protected string $remapConnections = 'mysql < sqlite';

    /**
     * Specify which connection "default" should point to.
     *
     * @var string
     */
//    protected string $defaultConnection = 'mysql';

    /**
     * When browser-tests are being performed, transaction-based database
     * re-use needs to be disabled.
     *
     * This is because the browser (which runs in a different process and
     * causes outside requests to your website) needs to access the same
     * database that your test built.
     *
     * If you don't specify this value, Adapt will automatically
     * detect if a browser test is running.
     *
     * @var boolean
     */
//    protected bool $isBrowserTest = true;

    /**
     * Adapt can be configured to use another installation of Adapt to
     * build databases instead of doing it itself. This may be
     * useful when sharing a database between projects.
     *
     * The other installation must be web-accessible to the first.
     *
     * e.g. 'https://other-site.local/'
     *
     * @var ?string
     */
//    protected ?string $remoteBuildUrl = null;

    /**
     * Set up the database/s programmatically.
     *
     * You may set up more test-databases by calling:
     * $this->newBuilder(string $connection), and then altering its settings.
     *
     * Each $builder object starts with the combined settings from the config
     * and properties from this test-class.
     *
     * @param DatabaseBuilder $builder Used to create the "default"
     *                                 connection's database.
     * @return void
     */
//    protected function databaseInit(DatabaseBuilder $builder): void
//    {
//        $initialImports =  [
//            'mysql' => ['database/dumps/mysql/my-database.sql'],
//            'pgsql' => ['database/dumps/pgsql/my-database.sql'],
//            'sqlite' => ['database/dumps/sqlite/my-database.sqlite'], // SQLite files are simply copied
//        ];
//
//        // the DatabaseBuilder $builder is pre-configured to match your config settings
//        // for the "default" database connection
//        // you can override them with any of the following…
//        $builder
//            ->connection('primary') // specify another connection to build a db for
//            ->cacheInvalidationMethod('content') // or ->cacheInvalidationMethodOff()
//            ->initialImports($initialImports) // or ->noInitialImports()
//            ->migrations() // or ->migrations('database/migrations') or ->noMigrations()
//            ->seeders(['DatabaseSeeder']) // or ->noSeeders()
//            ->remoteBuildUrl('https://...') // or ->noRemoteBuildUrl()
//            ->reuseTransaction() // or ->noReuseTransaction()
//            ->reuseJournal() // or ->noReuseJournal()
//            ->scenarioTestDBs() // or ->noScenarioTestDBs()
//            ->snapshots($useSnapshotsWhenReusingDB, $useSnapshotsWhenNotReusingDB) // or ->noSnapshots()
//            ->forceRebuild() // or ->dontForceRebuild()
//            ->isBrowserTest() // or isNotBrowserTest()
//            ->makeDefault(); // make the "default" Laravel connection point to this database
//
//        // you can create a database for another connection
//        $connection = 'secondary';
//        $builder2 = $this->newBuilder($connection);
//        $builder2
//            ->initialImports($initialImports) // or ->noInitialImports()
//            // …
//            ->makeDefault(); // make the "default" Laravel connection point to this database
//    }another
}
