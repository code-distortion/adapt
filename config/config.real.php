<?php

use CodeDistortion\Adapt\Support\Settings;

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
     | string / null
     |
     */

    'project_name' => env('ADAPT_PROJECT_NAME'),

    /*
     |--------------------------------------------------------------------------
     | Build Databases
     |--------------------------------------------------------------------------
     |
     | Database building functionality can be turned on or off altogether. This
     | config setting can be overridden by adding the $buildDatabases
     | property to your test-class.
     |
     | true / false
     |
     */

    'build_databases' => true,

    /*
     |--------------------------------------------------------------------------
     | Default Database Connection
     |--------------------------------------------------------------------------
     |
     | Set the "default" database connection to use when your tests run. This
     | config setting can be overridden by adding the $defaultConnection
     | property to your test-class.
     |
     | string / null
     |
     */

    'default_connection' => env('ADAPT_DEFAULT_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | Database Build-Sources
    |--------------------------------------------------------------------------
    |
    | These are the things that the database is built from.
    |
    | INITIAL-IMPORTS: Custom sql-dumps, imported before migrations & seeders.
    | e.g. [
    |     'mysql' => [database_path('initial-imports/mysql/db.sql')],
    |     'sqlite' => [database_path('initial-imports/sqlite/db.sqlite')],
    |     'pgsql' => [database_path('initial-imports/postgres/db.sql')],
    | ];
    |
    | > NOTE: initial_imports aren't available for SQLite :memory: databases.
    |
    | MIGRATIONS: Runs your migrations. You can specify a custom location.
    | e.g. 'database/other-migrations'
    |
    | SEEDERS: Runs particular seeders.
    | e.g. ['DatabaseSeeder', 'AnotherSeeder']
    |
    | These can be overridden by adding the $initialImports, $migrations
    | and $seeders properties to your test-classes.
    |
    | build_sources.initial_imports.mysql:  string / string[]
    | build_sources.initial_imports.pgsql:  string / string[]
    | build_sources.initial_imports.sqlite: string / string[]
    | build_sources.migrations:             true / false / string
    | build_sources.seeders:                string / string[]
    |
    */

    'build_sources' => [

        'initial_imports' => [
            'mysql' => [],
            'pgsql' => [],
            'sqlite' => [], // NOTE: SQLite files are simply copied
        ],

        'migrations' => true,

        'seeders' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Re-use
    |--------------------------------------------------------------------------
    |
    | Databases can be re-used to save time, provided their contents are
    | returned to a known state after each test. These are the methods
    | that can be used to return databases to their original state.s
    |
    | TRANSACTIONS: Wraps each test inside a transaction that's rolled back
    | afterward.
    |
    | JOURNALING: Tracks changes, and un-does them afterward.
    |
    | > WARNING: Journal based re-use is EXPERIMENTAL, and is currently only
    | > available for MySQL databases.
    |
    | SNAPSHOTS: SQL-dumps can be taken at certain points to skip the building
    | process next time. Snapshots will only be taken when transactions and
    | journaling aren't used, unless the "!" prefix is added.
    |
    | These can be overridden by adding the $transactions, $journals and
    | $snapshots properties to your test-classes.
    |
    | reuse_methods.transactions: true / false
    | reuse_methods.journals:     true / false
    | reuse_methods.snapshots:    false
    |                             / "afterMigrations" / "!afterMigrations"
    |                             / "afterSeeders" / "!afterSeeders"
    |                             / "both" / "!both"
    |
    */

    'reuse_methods' => [

        'transactions' => env('ADAPT_REUSE_TRANSACTIONS', true),

        'journals' => env('ADAPT_REUSE_JOURNALS', false),

        'snapshots' => env('ADAPT_REUSE_SNAPSHOTS', 'afterSeeders'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scenarios
    |--------------------------------------------------------------------------
    |
    | A new database will be created for each "scenario" needed.
    |
    | A scenario database will be called something like:
    | "your_test_database_name_17bd3c_d266ab43ac75"
    |
    | true / false
    |
    */

    'scenarios' => env('ADAPT_SCENARIOS', true),

    /*
    |--------------------------------------------------------------------------
    | Cache Invalidation - Detecting Changes to Source-Files
    |--------------------------------------------------------------------------
    |
    | New databases will be built when changes to source-files are detected.
    | Old databases and snapshot files are considered to be "stale", and
    | will be removed automatically after a grace-period.
    |
    | ENABLED: Turns cache invalidation on or off.
    |
    | LOCATIONS: Changes to files in these locations will be looked for.
    |
    | CHECKSUM_METHOD: The method used to detect changes. Either based on file
    | modified timestamps, or by looking at their content (which is slower).
    |
    | PURGE_STALE: Stale databases and snapshots will be looked for and
    | removed when enabled.
    |
    | cache_invalidation.enabled:         true / false
    | cache_invalidation.locations:       string[]
    | cache_invalidation.checksum_method: "modified" / "content"
    | cache_invalidation.purge_stale:     true / false
    |
    */

    'cache_invalidation' => [

        'enabled' => env('ADAPT_CACHE_INVALIDATION_ENABLED', true),

        'locations' => [
            database_path('migrations'),
            database_path('seeders'), // Laravel 8 and after
//            database_path('seeds'), // before Laravel 8
            database_path('factories'),
        ],

        'checksum_method' => env('ADAPT_CACHE_INVALIDATION_CHECKSUM_METHOD', 'modified'),

        'purge_stale' => env('ADAPT_CACHE_INVALIDATION_PURGE_STALE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Verification
    |--------------------------------------------------------------------------
    |
    | The database structure and content can be verified after each test, to
    | ensure they haven't changed. This was added as a safety-check when
    | using the experimental journal-based re-use option above. It's
    | disabled by default, and isn't otherwise necessary.
    |
    | > WARNING: Database verification EXPERIMENTAL, and is currently only
    | > available for MySQL databases.
    |
    | true / false
    |
    */

    'verify_databases' => env('ADAPT_VERIFY_DATABASES', false),

    /*
    |--------------------------------------------------------------------------
    | Remote Database Building
    |--------------------------------------------------------------------------
    |
    | Adapt can be configured to use another installation of Adapt to build
    | databases, instead of doing it itself. The other installation must
    | be web-accessible to this instance.
    |
    | This config setting can be overridden by adding the $remoteBuildUrl
    | property to your test-class.
    |
    | string / null
    |
    | e.g. 'https://other-site.local/'
    |
    */

    'remote_build_url' => env('ADAPT_REMOTE_BUILD_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Remap Database Connections
    |--------------------------------------------------------------------------
    |
    | This lets you overload database connections with the details from
    | others. This config setting can be overridden by adding the
    | $remapConnections property to your test-class.
    |
    | e.g. You can reassign the "mysql" and "mysql2" connections to use the
    | "sqlite" and "sqlite2" details respectively.
    |
    | "mysql < sqlite, mysql2 < sqlite2";
    |
    | You can make the settings here more important than your test-class
    | settings by adding "!".
    |
    | "!mysql < sqlite"
    |
    | string
    |
    */

    'remap_connections' => env('ADAPT_REMAP_CONNECTIONS', ''),

    /*
    |--------------------------------------------------------------------------
    | Storage Location
    |--------------------------------------------------------------------------
    |
    | Adapt will store files in this directory. It is created automatically.
    |
    | string
    |
    | e.g. database_path('adapt-test-storage')
    |
    */

    'storage_dir' => env('ADAPT_STORAGE_DIR', database_path('adapt-test-storage')),

    /*
     |--------------------------------------------------------------------------
     | Logging
     |--------------------------------------------------------------------------
     |
     | - stdout - Add logs to stdout.
     | - laravel - Add logs to Laravel's default logging mechanism.
     | - verbosity - The verbosity level to use (0, 1 or 2).
     |
     | log.stdout:    true / false
     | log.laravel:   true / false
     | log.verbosity: integer 0 - 2
     |
     */

    'log' => [
        'stdout' => env('ADAPT_LOG_STDOUT', false),
        'laravel' => env('ADAPT_LOG_LARAVEL', false),
        'verbosity' => env('ADAPT_LOG_VERBOSITY', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Settings And Executables
    |--------------------------------------------------------------------------
    |
    | Settings specific to each type of database, including the location of
    | their executable files (in case they aren't in your system-path).
    |
    | database.mysql.executables.mysql:     string
    | database.mysql.executables.mysqldump: string
    | database.pgsql.executables.psql:      string
    | database.pgsql.executables.pg_dump:   string
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

    /*
    |--------------------------------------------------------------------------
    | Other settings
    |--------------------------------------------------------------------------
    */

    'stale_grace_seconds' => env('ADAPT_STALE_GRACE_SECONDS', Settings::DEFAULT_STALE_GRACE_SECONDS),

];
