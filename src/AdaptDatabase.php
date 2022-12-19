<?php

namespace CodeDistortion\Adapt;

use CodeDistortion\Adapt\Initialise\InitialiseAdapt;

/**
 * Trait that allows your tests to use Adapt.
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
     * Turn database building on or off.
     *
     * @var boolean
     */
//    protected bool $buildDatabases = true;

    /**
     * Specify which connection "default" should point to when your test runs.
     *
     * @var string
     */
//    protected string $defaultConnection = 'mysql';

    /**
     * Specify custom sql-dump files to import before migrations run.
     *
     * NOTE: initial_imports aren't available for SQLite :memory: databases.
     *
     * @var array<string, string>|array<string, string[]>
     */
//    protected array $initialImports = [
//        'mysql' => ['database/dumps/mysql/db.sql', …],
//        'pgsql' => ['database/dumps/pgsql/db.sql', …],
//        'sqlite' => ['database/dumps/sqlite/db.sqlite'], // SQLite files are simply copied
//    ];

    /**
     * Runs your migrations. You can also specify a custom location.
     *
     * @var boolean|string
     */
//    protected bool|string $migrations = true;
//    or
//    protected bool|string $migrations = 'database/other_migrations';

    /**
     * Specify which seeders to run.
     *
     * NOTE: Seeders will only be run if initial-imports or migrations are run.
     *
     * @var string|string[]
     */
//    protected string|array $seeders = 'DatabaseSeeder'; // or ''
//    or
//    protected string|array $seeders = ['DatabaseSeeder', …]; // or []

    /**
     * When browser-tests are being performed, transaction-based database
     * re-use needs to be disabled.
     *
     * If you don't specify this value, Adapt will automatically
     * detect if a Dusk browser test is running.
     *
     * @var boolean
     */
//    protected bool $isBrowserTest = true;

    /**
     * Reuse databases using a transaction.
     *
     * @var boolean
     */
//    protected bool $transactions = true;

    /**
     * Reuse databases using journaling (MySQL only).
     *
     * @var boolean
     */
//    protected bool $journals = true;

    /**
     * Take snapshots of the database for quick importing later. Snapshots will
     * only be taken when transactions and journaling aren't used, unless
     * the "!" prefix is added.
     *
     * false
     * / 'afterMigrations' / 'afterSeeders' / 'both',
     * / '!afterMigrations' / '!afterSeeders' / '!both'.
     *
     * @var string|boolean
     */
//    protected string|bool $snapshots = 'afterSeeders';

    /**
     * Adapt can be configured to use another installation of Adapt to
     * build databases instead of doing it itself.
     *
     * NOTE: The other installation must be web-accessible to the this.
     *
     * e.g. 'https://other-site.local/'
     *
     * @var ?string
     */
//    protected ?string $remoteBuildUrl = null;

    /**
     * Overwrite the details of certain database connections with values from
     * others.
     *
     * @var string
     */
//    protected string $remapConnections = 'mysql < sqlite';

    /**
     * Set up the database/s programmatically.
     *
     * You may set up more test-databases by calling:
     * $this->prepareConnection(string $connection), and then altering its
     * settings.
     *
     * Each $database object starts with the combined settings from the config,
     * and properties from this test-class.
     *
     * @param DatabaseDefinition $database Used to create the "default"
     *                                     connection's database.
     * @return void
     */
//    protected function databaseInit(DatabaseDefinition $database): void
//    {
//        $initialImports =  [
//            'mysql' => ['database/dumps/mysql/db.sql'],
//            'pgsql' => ['database/dumps/pgsql/db.sql'],
//            'sqlite' => ['database/dumps/sqlite/db.sqlite'], // SQLite files are simply copied
//        ];
//
//        // the DatabaseDefinition $database is pre-configured to match your config settings
//        // for the "default" database connection
//        // you can override them with any of the following…
//        $database
//            ->connection('primary') // specify another connection to build a db for
//            ->initialImports($initialImports) // or ->noInitialImports()
//            ->migrations() // or ->migrations('database/other_migrations') or ->noMigrations()
//            ->seeders(['DatabaseSeeder']) // or ->noSeeders()
//            ->isABrowserTest() // or ->isNotABrowserTest()
//            ->transaction() // or ->noTransaction()
//            ->journal() // or ->noJournal()
//            ->snapshots('!afterSeeders') // or ->noSnapshots()
//            ->remoteBuildUrl('https://...') // or ->noRemoteBuildUrl()
//            ->forceRebuild() // or ->dontForceRebuild()
//            ->makeDefault(); // make the "default" Laravel connection point to this connection
//
//        // you can create a database for another connection
//        $connection = 'secondary';
//        $database2 = $this->prepareConnection($connection);
//        $database2
//            ->initialImports($initialImports); // or ->noInitialImports()
//            // …
//    }
}
