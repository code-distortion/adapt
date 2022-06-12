<?php

namespace CodeDistortion\Adapt\Tests\Integration\Laravel\DatabaseBuilder;

use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Tests\Database\Seeders\DatabaseSeeder;
use CodeDistortion\Adapt\Tests\Database\Seeders\InitialImportSeeder;
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
        $evInitialImportOne = new ExpectedValuesDTO('initial_import', ['name'], [['One']]);
        $evInitialImportOneThree = new ExpectedValuesDTO('initial_import', ['name'], [['One'], ['Three']]);
        $evUsers = new ExpectedValuesDTO('users', ['username'], [['user1']]);
        $evNoUsers = new ExpectedValuesDTO('users', ['username'], []);
        $evLogs = new ExpectedValuesDTO('logs', ['event'], [['event1']]);
        $evNoLogs = new ExpectedValuesDTO('logs', ['event'], []);
        $allTables = [
            'initial_import',
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
                    ->databaseName("$this->wsAdaptStorageDir/database.sqlite")
                    ->expectedTables(['____adapt____']),
            ],

            'sqlite - No initial-imports 1 - no migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([]),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.80cb3b-a7bb33d7d9b2.sqlite")
                    ->expectedTables(['____adapt____']),
            ],
            'sqlite - No initial-imports 2 - no migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->initialImports(['sqlite' => '']),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.80cb3b-e69755c9c9bd.sqlite")
                    ->expectedTables(['____adapt____']),
            ],
            'sqlite - No initial-imports 3 - no migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->initialImports(['sqlite' => []]),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.80cb3b-540f84254f46.sqlite")
                    ->expectedTables(['____adapt____']),
            ],

            'sqlite - initial-imports (string) - no migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->initialImports([
                        'sqlite' => "$this->wsInitialImportsDir/initial-import-1.sqlite",
                    ]),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.80cb3b-19c60372d635.sqlite")
                    ->expectedTables(['initial_import', '____adapt____'])
                    ->addExpectedValues($evInitialImportOne),
            ],
            'sqlite - initial-imports (array) - no migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->initialImports([
                        'sqlite' => ["$this->wsInitialImportsDir/initial-import-1.sqlite"],
                    ]),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.80cb3b-47d3551e6a4c.sqlite")
                    ->expectedTables(['initial_import', '____adapt____'])
                    ->addExpectedValues($evInitialImportOne),
            ],

            'sqlite - initial-imports - migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->initialImports([
                        'sqlite' => ["$this->wsInitialImportsDir/initial-import-1.sqlite"],
                    ])
                    ->migrations($this->wsMigrationsDir),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.80cb3b-0126ca588e17.sqlite")
                    ->expectedTables($allTables)
                    ->addExpectedValues($evInitialImportOne)
                    ->addExpectedValues($evNoUsers)
                    ->addExpectedValues($evNoLogs),
            ],
            'sqlite - initial-imports - no migrations - seeders (one)' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->initialImports([
                        'sqlite' => ["$this->wsInitialImportsDir/initial-import-1.sqlite"],
                    ])
                    ->seeders([InitialImportSeeder::class]),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.80cb3b-47d3551e6a4c.sqlite")
                    ->expectedTables(['initial_import', '____adapt____'])
                    ->addExpectedValues($evInitialImportOne)
            ],

            'sqlite - initial-imports - migrations - seeders (one)' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->initialImports([
                        'sqlite' => ["$this->wsInitialImportsDir/initial-import-1.sqlite"],
                    ])
                    ->migrations($this->wsMigrationsDir)
                    ->seeders([InitialImportSeeder::class]),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.80cb3b-423c485a7882.sqlite")
                    ->expectedTables($allTables)
                    ->addExpectedValues($evInitialImportOneThree)
                    ->addExpectedValues($evNoUsers)
                    ->addExpectedValues($evNoLogs),
            ],

            'sqlite - initial-imports - migrations - seeders (several)' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->initialImports([
                        'sqlite' => ["$this->wsInitialImportsDir/initial-import-1.sqlite"],
                    ])
                    ->migrations($this->wsMigrationsDir)
                    ->seeders([DatabaseSeeder::class, InitialImportSeeder::class]),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.80cb3b-6a48d8a4d86e.sqlite")
                    ->expectedTables($allTables)
                    ->addExpectedValues($evInitialImportOneThree)
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
     * @param ConfigDTO          $configDTO       The ConfigDTO to use which instructs what and how to build.
     * @param ExpectedOutcomeDTO $expectedOutcome The outcome to expect.
     * @return void
     */
    public function test_structure_and_data_setup(ConfigDTO $configDTO, ExpectedOutcomeDTO $expectedOutcome): void
    {
        $this->prepareWorkspace("$this->workspaceBaseDir/scenario1", $this->wsCurrentDir);

        // build the database
        $this->newDatabaseBuilder($configDTO)->execute();

        // check database name
        $this->assertSame(
            $expectedOutcome->databaseName,
            config("database.connections.$configDTO->connection.database")
        );

        // check which tables exist
        $this->assertTableList($configDTO->connection, $expectedOutcome->expectedTables);

        // check values in certain tables
        foreach ($expectedOutcome->expectedValues as $expectedValueSet) {
            $this->assertTableValues($configDTO->connection, $expectedValueSet);
        }
    }
}
