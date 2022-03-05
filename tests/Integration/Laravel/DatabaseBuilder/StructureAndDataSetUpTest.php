<?php

namespace CodeDistortion\Adapt\Tests\Integration\Laravel\DatabaseBuilder;

use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Tests\Database\Seeders\DatabaseSeeder;
use CodeDistortion\Adapt\Tests\Database\Seeders\PreMigrationImportSeeder;
use CodeDistortion\Adapt\Tests\Integration\Support\AssignClassAlias;
use CodeDistortion\Adapt\Tests\Integration\Support\DatabaseBuilderTestTrait;
use CodeDistortion\Adapt\Tests\Integration\Support\ExpectedOutcomeDTO;
use CodeDistortion\Adapt\Tests\Integration\Support\ExpectedValuesDTO;
use CodeDistortion\Adapt\Tests\LaravelTestCase;

AssignClassAlias::databaseBuilderSetUpTrait(__NAMESPACE__);

/**
 * Test that the DatabaseBuilder class acts correctly in different scenarios.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class StructureAndDataSetUpTest extends LaravelTestCase
{
    use DatabaseBuilderSetUpTrait; // this is chosen above by AssignClassAlias depending on the version of Laravel used
    use DatabaseBuilderTestTrait;


    /**
     * Provide data for the test_structure_and_data_setup test.
     *
     * @return mixed[][]
     */
    public function structureAndDataSetupDataProvider(): array
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
            'sqlite - scenario-test-dbs off' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->scenarioTestDBs(false),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsDatabaseDir/database.sqlite")
                    ->expectedTables(['____adapt____']),
            ],

            'sqlite - No pre-migration imports 1 - no migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([]),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.80cb3b-185ad9d21f69.sqlite")
                    ->expectedTables(['____adapt____']),
            ],
            'sqlite - No pre-migration imports 2 - no migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->preMigrationImports(['sqlite' => '']),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.80cb3b-4f237fb468e6.sqlite")
                    ->expectedTables(['____adapt____']),
            ],
            'sqlite - No pre-migration imports 3 - no migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->preMigrationImports(['sqlite' => []]),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.80cb3b-a2bf309fce79.sqlite")
                    ->expectedTables(['____adapt____']),
            ],

            'sqlite - pre-migration imports (string) - no migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->preMigrationImports([
                        'sqlite' => "$this->wsPreMigrationsDir/pre-migration-import-1.sqlite",
                    ]),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.80cb3b-228ce65a61fb.sqlite")
                    ->expectedTables(['pre_migration_import', '____adapt____'])
                    ->addExpectedValues($evPreMigrationImportOne),
            ],
            'sqlite - pre-migration imports (array) - no migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->preMigrationImports([
                        'sqlite' => ["$this->wsPreMigrationsDir/pre-migration-import-1.sqlite"],
                    ]),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.80cb3b-d7a126c9b0b4.sqlite")
                    ->expectedTables(['pre_migration_import', '____adapt____'])
                    ->addExpectedValues($evPreMigrationImportOne),
            ],

            'sqlite - pre-migration imports - migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->preMigrationImports([
                        'sqlite' => ["$this->wsPreMigrationsDir/pre-migration-import-1.sqlite"],
                    ])
                    ->migrations($this->wsMigrationsDir),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.80cb3b-d62643c0d720.sqlite")
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
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.80cb3b-d7a126c9b0b4.sqlite")
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
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.80cb3b-318cc1456462.sqlite")
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
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.80cb3b-4d541b806801.sqlite")
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
     * @dataProvider structureAndDataSetupDataProvider
     * @param ConfigDTO          $config          The ConfigDTO to use which instructs what and how to build.
     * @param ExpectedOutcomeDTO $expectedOutcome The outcome to expect.
     * @return void
     */
    public function test_structure_and_data_setup(ConfigDTO $config, ExpectedOutcomeDTO $expectedOutcome): void
    {

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
