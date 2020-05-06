<?php

namespace CodeDistortion\Adapt\Tests\Integration;

use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Exceptions\AdaptBuildException;
use CodeDistortion\Adapt\Tests\Database\Seeders\DatabaseSeeder;
use CodeDistortion\Adapt\Tests\Database\Seeders\PreMigrationImportSeeder;
use CodeDistortion\Adapt\Tests\Integration\Support\DatabaseBuilderSetUpNoVoidTrait;
use CodeDistortion\Adapt\Tests\Integration\Support\DatabaseBuilderSetUpVoidTrait;
use CodeDistortion\Adapt\Tests\Integration\Support\DatabaseBuilderTestTrait;
use CodeDistortion\Adapt\Tests\Integration\Support\ExpectedOutcomeDTO;
use CodeDistortion\Adapt\Tests\Integration\Support\ExpectedValuesDTO;
use CodeDistortion\Adapt\Tests\LaravelTestCase;
use DB;
use Orchestra\Testbench\TestCase;
use Throwable;

$setupMethod = new \ReflectionMethod(TestCase::class, 'setUp');
if ((!$setupMethod->getReturnType()) || ($setupMethod->getReturnType()->getName() != 'void')) {
    class_alias(DatabaseBuilderSetUpNoVoidTrait::class, DatabaseBuilderSetUpTrait::class);
} else {
    class_alias(DatabaseBuilderSetUpVoidTrait::class, DatabaseBuilderSetUpTrait::class);
}

/**
 * Test the DatabaseBuilder class
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class DatabaseBuilderLaravelTest extends LaravelTestCase
{
    use DatabaseBuilderSetUpTrait;
    use DatabaseBuilderTestTrait;


    /**
     * Test that the DatabaseBuilder only executes once.
     *
     * @test
     * @return void
     */
    public function test_database_builder_only_runs_once(): void
    {
        $this->prepareWorkspace("$this->workspaceBaseDir/scenario1", $this->wsCurrentDir);

        try {
            $this->newDatabaseBuilder()->execute()->execute();
        } catch (Throwable $e) {
            if ($e instanceof AdaptBuildException) {
                $this->assertTrue(true);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Test that the DatabaseBuilder creates the database/adapt_test_storage directory.
     *
     * @test
     * @return void
     */
    public function test_database_builder_creates_adapt_test_storage_dir(): void
    {
        $this->prepareWorkspace("$this->workspaceBaseDir/scenario1", $this->wsCurrentDir);

        $this->assertFalse(file_exists("$this->wsCurrentDir/database/adapt-test-storage"));
        $this->newDatabaseBuilder()->execute();
        $this->assertFileExists("$this->wsCurrentDir/database/adapt-test-storage");
    }

    /**
     * Test that the DatabaseBuilder creates the sqlite database.
     *
     * @test
     * @return void
     */
    public function test_database_builder_creates_sqlite_database(): void
    {
        $this->prepareWorkspace("$this->workspaceBaseDir/scenario1", $this->wsCurrentDir);

        $this->newDatabaseBuilder()->execute();

        $dbPath = config('database.connections.sqlite.database');
        $this->assertFileExists($dbPath);
        $this->assertSame(
            "$this->wsCurrentDir/database/adapt-test-storage/test-database.c7669142c893e33c-feec43fe6c003072.sqlite",
            $dbPath
        );
    }

    /**
     * Provide data for the test_database_builder_takes_snapshots test.
     *
     * @return mixed[][]
     */
    public function snapshotDataProvider(): array
    {
        return [
            'Snapshots disabled' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(false, true, true)
                    ->seeders([DatabaseSeeder::class]),
                'expectedFiles' => [],
            ],

            'Takes snapshot after migrations' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(true, true, false)
                    ->seeders([DatabaseSeeder::class]),
                'expectedFiles' => [
                    'snapshot.database.c7669142c893e33c-a34cd538e35f9b7d.sqlite',
                ],
            ],
            'Takes snapshot after seeders' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(true, false, true)
                    ->seeders([DatabaseSeeder::class]),
                'expectedFiles' => [
                    'snapshot.database.c7669142c893e33c-8bd51f9f0b21313b.sqlite',
                ],
            ],
            'Takes snapshot after migrations and seeders' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(true, true, true)
                    ->seeders([DatabaseSeeder::class]),
                'expectedFiles' => [
                    'snapshot.database.c7669142c893e33c-8bd51f9f0b21313b.sqlite',
                    'snapshot.database.c7669142c893e33c-a34cd538e35f9b7d.sqlite',
                ],
            ],

            'Takes snapshot after migrations (no seeders)' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(true, true, false)
                    ->seeders([]),
                'expectedFiles' => [
                    'snapshot.database.c7669142c893e33c-a34cd538e35f9b7d.sqlite',
                ],
            ],
            'Takes snapshot after seeders (no seeders)' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(true, false, false)
                    ->seeders([]),
                'expectedFiles' => [],
            ],
            'Takes snapshot after migrations and seeders (no seeders)' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(true, true, true)
                    ->seeders([]),
                'expectedFiles' => [
                    'snapshot.database.c7669142c893e33c-a34cd538e35f9b7d.sqlite',
                ],
            ],
        ];
    }

    /**
     * Test that the DatabaseBuilder takes a snapshot after migrations.
     *
     * @test
     * @dataProvider snapshotDataProvider
     * @param ConfigDTO $config        The ConfigDTO to use which instructs what and how to build.
     * @param array     $expectedFiles The files expected to exist.
     * @return void
     */
    public function test_database_builder_takes_snapshots(ConfigDTO $config, array $expectedFiles): void
    {
        $this->prepareWorkspace("$this->workspaceBaseDir/scenario1", $this->wsCurrentDir);

        // build the database
        $this->newDatabaseBuilder($config)->execute();

        // get db name
        $dbPath = config('database.connections.sqlite.database');
        $temp = explode('/', $dbPath);
        $dbFile = array_pop($temp);

        // check other files
        $files = array_values(array_diff(
            scandir($this->wsAdaptStorageDir),
            ['.', '..', '.gitignore', $dbFile]
        ));

        $this->assertSame($expectedFiles, $files);
    }

    /**
     * Provide data for the test_different_scenarios test.
     *
     * @return mixed[][]
     */
    public function differentScenariosDataProvider(): array
    {
        $evPreMigrationImportOne = new ExpectedValuesDTO('pre_migration_import', ['name'], [['One']]);
        $evPreMigrationImportOneThree = new ExpectedValuesDTO('pre_migration_import', ['name'], [['One'], ['Three']]);
        $evUsers = new ExpectedValuesDTO('users', ['username'], [['user1']]);
        $evNoUsers = new ExpectedValuesDTO('users', ['username'], []);
        $evLogs = new ExpectedValuesDTO('logs', ['event'], [['event1']]);
        $evNoLogs = new ExpectedValuesDTO('logs', ['event'], []);
        $allTables = [
            'pre_migration_import',
            'migrations',
            'sqlite_sequence',
            'users',
            'logs',
            '____adapt____',
        ];

        return [
            'sqlite - dynamic-test-dbs off' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->dynamicTestDBs(false),
                'expectedOutcome' => (new ExpectedOutcomeDTO)
                    ->databaseName("$this->wsDatabaseDir/database.sqlite")
                    ->expectedTables(['____adapt____']),
            ],

            'sqlite - No pre-migration imports 1 - no migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([]),
                'expectedOutcome' => (new ExpectedOutcomeDTO)
                    ->databaseName("$this->wsAdaptStorageDir/test-database.c7669142c893e33c-0962187c9a7696b4.sqlite")
                    ->expectedTables(['____adapt____']),
            ],
            'sqlite - No pre-migration imports 2 - no migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->preMigrationImports(['sqlite' => '']),
                'expectedOutcome' => (new ExpectedOutcomeDTO)
                    ->databaseName("$this->wsAdaptStorageDir/test-database.c7669142c893e33c-c92f9f7f37966cc1.sqlite")
                    ->expectedTables(['____adapt____']),
            ],
            'sqlite - No pre-migration imports 3 - no migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->preMigrationImports(['sqlite' => []]),
                'expectedOutcome' => (new ExpectedOutcomeDTO)
                    ->databaseName("$this->wsAdaptStorageDir/test-database.c7669142c893e33c-f181ebbc9c06fbae.sqlite")
                    ->expectedTables(['____adapt____']),
            ],

            'sqlite - pre-migration imports (string) - no migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->preMigrationImports([
                        'sqlite' => "$this->wsPreMigrationsDir/pre-migration-import-1.sqlite",
                    ]),
                'expectedOutcome' => (new ExpectedOutcomeDTO)
                    ->databaseName("$this->wsAdaptStorageDir/test-database.c7669142c893e33c-7934da3193073646.sqlite")
                    ->expectedTables(['pre_migration_import', '____adapt____'])
                    ->addExpectedValues($evPreMigrationImportOne),
            ],
            'sqlite - pre-migration imports (array) - no migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->preMigrationImports([
                        'sqlite' => ["$this->wsPreMigrationsDir/pre-migration-import-1.sqlite"],
                    ]),
                'expectedOutcome' => (new ExpectedOutcomeDTO)
                    ->databaseName("$this->wsAdaptStorageDir/test-database.c7669142c893e33c-1ebc735c552b0f0a.sqlite")
                    ->expectedTables(['pre_migration_import', '____adapt____'])
                    ->addExpectedValues($evPreMigrationImportOne),
            ],

            'sqlite - pre-migration imports - migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->preMigrationImports([
                        'sqlite' => ["$this->wsPreMigrationsDir/pre-migration-import-1.sqlite"],
                    ])
                    ->migrations($this->wsMigrationsDir),
                'expectedOutcome' => (new ExpectedOutcomeDTO)
                    ->databaseName("$this->wsAdaptStorageDir/test-database.c7669142c893e33c-af4d8f5897f7ab5a.sqlite")
                    ->expectedTables($allTables)
                    ->addExpectedValues($evPreMigrationImportOne)
                    ->addExpectedValues($evNoUsers)
                    ->addExpectedValues($evNoLogs),
            ],
            'sqlite - pre-migration imports - no migrations - seeders (one)' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->preMigrationImports([
                        'sqlite' => ["$this->wsPreMigrationsDir/pre-migration-import-1.sqlite"],
                    ])
                    ->seeders([PreMigrationImportSeeder::class]),
                'expectedOutcome' => (new ExpectedOutcomeDTO)
                    ->databaseName("$this->wsAdaptStorageDir/test-database.c7669142c893e33c-1ebc735c552b0f0a.sqlite")
                    ->expectedTables(['pre_migration_import','____adapt____'])
                    ->addExpectedValues($evPreMigrationImportOne)
            ],

            'sqlite - pre-migration imports - migrations - seeders (one)' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->preMigrationImports([
                        'sqlite' => ["$this->wsPreMigrationsDir/pre-migration-import-1.sqlite"],
                    ])
                    ->migrations($this->wsMigrationsDir)
                    ->seeders([PreMigrationImportSeeder::class]),
                'expectedOutcome' => (new ExpectedOutcomeDTO)
                    ->databaseName("$this->wsAdaptStorageDir/test-database.c7669142c893e33c-446a5601559e305b.sqlite")
                    ->expectedTables($allTables)
                    ->addExpectedValues($evPreMigrationImportOneThree)
                    ->addExpectedValues($evNoUsers)
                    ->addExpectedValues($evNoLogs),
            ],

            'sqlite - pre-migration imports - migrations - seeders (several)' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->preMigrationImports([
                        'sqlite' => ["$this->wsPreMigrationsDir/pre-migration-import-1.sqlite"],
                    ])
                    ->migrations($this->wsMigrationsDir)
                    ->seeders([DatabaseSeeder::class, PreMigrationImportSeeder::class]),
                'expectedOutcome' => (new ExpectedOutcomeDTO)
                    ->databaseName("$this->wsAdaptStorageDir/test-database.c7669142c893e33c-cd7525eabceac076.sqlite")
                    ->expectedTables($allTables)
                    ->addExpectedValues($evPreMigrationImportOneThree)
                    ->addExpectedValues($evUsers)
                    ->addExpectedValues($evLogs),
            ],
        ];
    }

    /**
     * Test that the DatabaseBuilder builds different scenarios properly.
     *
     * @test
     * @dataProvider differentScenariosDataProvider
     * @param ConfigDTO          $config          The ConfigDTO to use which instructs what and how to build.
     * @param ExpectedOutcomeDTO $expectedOutcome The outcome to expect.
     * @return void
     */
    public function test_different_scenarios(
        ConfigDTO $config,
        ExpectedOutcomeDTO $expectedOutcome
    ): void {

        $this->prepareWorkspace("$this->workspaceBaseDir/scenario1", $this->wsCurrentDir);

        // build the database
        $this->newDatabaseBuilder($config)->execute();

        // check database name
        $this->assertSame(
            $expectedOutcome->databaseName,
            config("database.connections.$config->connection.database")
        );

        // check which tables exist
        $this->assertTableList($config->connection, $expectedOutcome->expectedTables);

        // check values in certain tables
        foreach ($expectedOutcome->expectedValues as $expectedValueSet) {
            $this->assertTableValues($config->connection, $expectedValueSet);
        }
    }
}
