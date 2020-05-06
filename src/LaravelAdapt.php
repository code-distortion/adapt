<?php

namespace CodeDistortion\Adapt;

use CodeDistortion\Adapt\SetUp\LaravelAdaptTestSetUpTrait;

/**
 * Allow Laravel tests to use Adapt.
 *
 * Add this trait to the test-classes you'd like Adapt to apply to.
 *
 * The properties below can be set in your test-class to alter how the
 * test-database is set up. Or alternatively, more customisation is possible
 * by adding the databaseInit(DatabaseBuilder $builder) method.
 */
trait LaravelAdapt
{
    use LaravelAdaptTestSetUpTrait;

    /**
     * Specify database dump files to import before migrations run.
     *
     * NOTE: It's important that these dumps don't contain output from seeders
     * if those seeders are to be run by Adapt as needed afterwards.
     *
     * @var string[]|string[][]
     */
//    protected array $preMigrationImports = [
//        'mysql' => ['database/dumps/mysql/my-database.sql'],
//        'sqlite' => ['database/dumps/sqlite/my-database.sqlite'], // SQLite files are simply copied
//    ];

    /**
     * Specify whether to run migrations or not. You can also specify the
     * location of the migrations to run.
     *
     * @var boolean|string
     */
//    protected $migrations = true;
//    or
//    protected $migrations = 'database/migrations';

    /**
     * Specify the seeders to run (they will only be run if migrations are
     * run).
     *
     * @var string[]
     */
//    protected array $seeders = ['DatabaseSeeder'];

    /**
     * Let Adapt re-use databases.
     *
     * NOTE: this requires the transactions setting to be on.
     *
     * @var boolean
     */
//    protected bool $reuseTestDBs = true;

    /**
     * Let Adapt create databases dynamically based on the scenario.
     *
     * @var boolean
     */
//    protected bool $dynamicTestDBs = true;

    /**
     * Encapsulate each test inside a transaction - it's rolled back afterwards
     * to leave the database in it's initial state.
     *
     * @var boolean
     */
//    protected bool $transactions = true;

    /**
     * Enable / disable the use of snapshot files.
     *
     * @var boolean
     */
//    protected bool $snapshotsEnabled = true;

    /**
     * Adapt can take a snapshot after migrations have run (but before
     * seeders).
     *
     * @var boolean
     */
//    protected bool $takeSnapshotAfterMigrations = true;

    /**
     * Adapt can take a snapshot after migrations and seeders have run.
     *
     * @var boolean
     */
//    protected bool $takeSnapshotAfterSeeders = true;

    /**
     * When performing browser tests "reuse-test-dbs", "dynamic-test-dbs"
     * and "transactions" need to be turned off.
     *
     * This is because the browser (which runs in a different process and
     * causes outside requests to your website) needs to access the same
     * database that your tests build.
     *
     * When this value isn't present Adapt will attempt to detect if a browser
     * test is running.
     *
     * @var boolean
     */
//    protected bool $isBrowserTest = true;

    /**
     * Overwrite the details of certain database connections with values from
     * others.
     *
     * eg. overwrite the "mysql" connection with the "sqlite" connection's
     * details so sqlite is used instead.
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
     * Customise the database/s that are set up.
     *
     * You may set up more test-databases by calling:
     * $this->newBuilder(string $connection), and then altering its settings.
     *
     * @param DatabaseBuilder $builder The initial $databaseBuilder - with the
     *                                 default settings.
     * @return void
     */
//    protected function databaseInit(DatabaseBuilder $builder): void
//    {
//        $preMigrationImports =  [
//            'mysql' => ['database/dumps/mysql/my-database.sql'],
//            'sqlite' => ['database/dumps/sqlite/my-database.sqlite'], // SQLite files are simply copied
//        ];
//
//        // the DatabaseBuilder $builder will contain settings based on the config and properties above
//        // you can override them like so:
//        $builder
//            ->preMigrationImports($preMigrationImports) // or ->noPreMigrationImports()
//            ->migrations() // or ->migrations('database/migrations') or ->noMigrations()
//            ->seeders(['DatabaseSeeder']) // or ->noSeeders()
//            ->reuseTestDBs() // or ->noReuseTestDBs()
//            ->dynamicTestDBs() // or ->noDynamicTestDBs()
//            ->transactions() // or ->noTransactions()
//            ->snapshots() // or ->noSnapshots()
//            ->isBrowserTest() // or isNotBrowserTest()
//            ->makeDefault(); // make the "default" connection point to this database
//
//        // define a second database that will be created
//        // the DatabaseBuilder $builder2 will contain settings based on the config and properties above
//        $connection = 'mysql2';
//        $builder2 = $this->newBuilder($connection); /** @var DatabaseBuilder $builder2 **/
//
//        // you can override them like so:
//        $builder2
//            ->preMigrationImports($preMigrationImports) // or ->noPreMigrationImports()
//            // ...
//            ->makeDefault(); // make the "default" connection point to this database
//    }
}
