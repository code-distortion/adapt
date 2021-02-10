<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Project Name
     |--------------------------------------------------------------------------
     |
     | You might share your database server between different projects. If so,
     | specify a unique project name here to ensure that Adapt doesn't
     | interfere with Adapt test-databases from other projects.
     |
     */

    'project_name' => env('ADAPT_PROJECT_NAME', ''),

    /*
     |--------------------------------------------------------------------------
     | Build Databases
     |--------------------------------------------------------------------------
     |
     | Turn database building on or off. This config setting can be overridden
     | by adding the $buildDatabases property to your test-class.
     |
     */

    'build_databases' => true,

    /*
    |--------------------------------------------------------------------------
    | Reuse Test-Databases
    |--------------------------------------------------------------------------
    |
    | Tests are wrapped in a transaction to keep the database in a clean state.
    | Databases are re-used between tests, and also between test-runs.
    | This is best used with the scenario_test_dbs setting below.
    |
    | This config setting can be overridden by adding the
    | $reuseTestDBs property to your test-class.
    |
    */

    'reuse_test_dbs' => env('ADAPT_REUSE_TEST_DBS', true),

    /*
    |--------------------------------------------------------------------------
    | "Scenario" Test-Databases
    |--------------------------------------------------------------------------
    |
    | A new database (based on the original database name) will be created
    | for each "scenario" the tests need. This is best used with the
    | reuse_test_dbs setting above.
    |
    | An scenario database will be called something like:
    | "test_your_database_name_17bd3c_d266ab43ac75"
    |
    | This config setting can be overridden by adding the $scenarioTestDBs
    | property to your test-class.
    |
    | This is turned off automatically when browser testing (eg. Dusk).
    |
    */

    'scenario_test_dbs' => env('ADAPT_SCENARIO_TEST_DBS', true),

    /*
    |--------------------------------------------------------------------------
    | Database Snapshots
    |--------------------------------------------------------------------------
    |
    | Database dumps/copies can be taken and imported automatically when
    | needed, saving migration + seeding time.
    |
    | These config settings can be overridden by adding the $snapshotsEnabled,
    | $takeSnapshotAfterMigrations and $takeSnapshotAfterSeeders properties
    | to your test-class.
    |
    */

    'snapshots' => [
        'enabled' => env('ADAPT_USE_SNAPSHOTS', false),
        'take_after_migrations' => env('ADAPT_TAKE_SNAPSHOTS_AFTER_MIGRATIONS', false),
        'take_after_seeders' => env('ADAPT_TAKE_SNAPSHOTS_AFTER_SEEDERS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Imports To Apply Before Migrations & Seeders
    |--------------------------------------------------------------------------
    |
    | If you have your own database-dump/s that you'd like to be applied BEFORE
    | migrations run, list them here. This config setting can be overridden
    | by adding the $preMigrationImports property to your test-class.
    |
    | eg.
    | protected array $preMigrationImports = [
    |   'mysql' => [database_path('dumps/mysql/my-database.sql')],
    |   'sqlite' => [database_path('dumps/sqlite/my-database.sqlite')], // SQLite files are simply copied
    |   'pgsql' => [database_path('dumps/postgres/my-database.sql')],
    | ];
    |
    | NOTE: It's important that these dumps don't contain output from seeders
    | if those seeders are also run by Adapt afterwards.
    |
    */

    'pre_migration_imports' => [
        'mysql' => [],
        'sqlite' => [],
        'pgsql' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Run Migrations
    |--------------------------------------------------------------------------
    |
    | Your test-databases can be migrated before use. This can be true/false,
    | or the LOCATION of the migration files. This config setting can be
    | overridden by adding the $migrations property to your test-class.
    |
    */

    'migrations' => true, // eg. true, false, 'database/migrations',

    /*
    |--------------------------------------------------------------------------
    | Seeders To Run
    |--------------------------------------------------------------------------
    |
    | These seeders will be run for you automatically when the database is
    | prepared. This config setting can be overridden by adding the
    | $seeders property to your test-class.
    |
    | NOTE: Seeders are only run when migrations (above) are turned on.
    |
    */

    'seeders' => [], // eg. ['DatabaseSeeder'],

    /*
    |--------------------------------------------------------------------------
    | Storage Location
    |--------------------------------------------------------------------------
    |
    | Database-snapshots (for quicker loading) and disk-based databases will
    | be stored in this directory. It will be created automatically.
    |
    */

    'storage_dir' => realpath(base_path('../../../../tests/workspaces/current/database')) . '/adapt-test-storage',

    /*
    |--------------------------------------------------------------------------
    | Files That Alter Test-Databases Building
    |--------------------------------------------------------------------------
    |
    | Changes to files in these directories will invalidate existing
    | test-databases and snapshots (they'll be re-built).
    |
    */

    'look_for_changes_in' => [
        realpath(base_path('../../../../tests/workspaces/current/database')) . '/factories',
        realpath(base_path('../../../../tests/workspaces/current/database')) . '/migrations',
//        realpath(base_path('../../../../tests/workspaces/current/database')) . '/seeders',     // Laravel 8 and after
        realpath(base_path('../../../../tests/workspaces/current/database')) . '/seeds',       // before Laravel 8
    ],

    /*
    |--------------------------------------------------------------------------
    | Remap Database Connections
    |--------------------------------------------------------------------------
    |
    | This lets you overload database connections with the details from
    | others. This config setting can be overridden by adding the
    | $remapConnections property to your test-class.
    |
    | eg.
    | // reassign the "mysql" and "mysql2" connections to use the "sqlite"
    | // and "sqlite2" details respectively.
    |
    | protected string $remapConnections = 'mysql < sqlite, mysql2 < sqlite2';
    |
    | You can make the settings here more important than your test-class
    | settings by adding "!".
    |
    | eg.
    | '!mysql < sqlite'
    */

    'remap_connections' => env('ADAPT_REMAP_CONNECTIONS', ''),

    /*
     |--------------------------------------------------------------------------
     | Logging
     |--------------------------------------------------------------------------
     |
     | Where to log debugging output:
     | - stdout - to the screen.
     | - laravel - to Laravel's default logging mechanism.
     |
     */

    'log' => [
        'stdout' => env('ADAPT_LOG_STDOUT', false),
        'laravel' => env('ADAPT_LOG_LARAVEL', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Settings And Executables
    |--------------------------------------------------------------------------
    |
    | Settings specific to each type of database, including the location
    | of their executable files in case they aren't in your system-
    | path.
    |
    */

    'database' => [
        'mysql' => [
            'executables' => [
                'mysql' => env('ADAPT_MYSQL_EXECUTABLE', 'mysql'),
                'mysqldump' => env('ADAPT_MYSQLDUMP_EXECUTABLE', 'mysqldump'),
            ],
        ],
        'pgsql' => [
            'executables' => [
                'psql' => env('ADAPT_PSQL_EXECUTABLE', 'psql'),
                'pg_dump' => env('ADAPT_PG_DUMP_EXECUTABLE', 'pg_dump'),
            ],
        ],
    ],

];
