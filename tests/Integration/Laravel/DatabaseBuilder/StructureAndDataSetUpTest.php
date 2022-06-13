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
use Illuminate\Support\Facades\DB;

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
     * The initial-import-1.sqlite file contains one table - `initial_import` with one row.
     *
     * This method rebuilds it, and stores it in the correct place ready to commit
     *
     * @test
     * @return void
     * @throws \Throwable
     */
//    public function create_sqlite_import_file(): void
//    {
//        $configDTO = $this->newConfigDTO('sqlite')->migrations(false)->seeders([]);
//        $connection = $configDTO->connection;
//
//        $filename = 'initial-import-1.sqlite';
//
//        $database = "$this->wsInitialImportsDir/$filename";
//        $database = str_replace('/current/', '/scenario1/', $database);
//
//        if (file_exists($database)) {
//            unlink($database);
//        }
//        touch($database);
//
//        config(["database.connections.$connection.database" => $database]);
//
//        // insert some data into the database
//        $db = DB::connection($connection);
//        $db->statement("CREATE TABLE `initial_import` (`name` VARCHAR(64) PRIMARY KEY NOT NULL)");
//        $db->insert("INSERT INTO `initial_import` (`name`) VALUES ('One')");
//
//        dump("New $filename database written to $database");
//    }



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
                    ->databaseName("$this->wsAdaptStorageDir/test-database.9b943b-deff164aca4d.sqlite")
                    ->expectedTables(['____adapt____']),
            ],
            'sqlite - No initial-imports 2 - no migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->initialImports(['sqlite' => '']),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.9b943b-63cd4b8cddf6.sqlite")
                    ->expectedTables(['____adapt____']),
            ],
            'sqlite - No initial-imports 3 - no migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->initialImports(['sqlite' => []]),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.9b943b-16831144fbd1.sqlite")
                    ->expectedTables(['____adapt____']),
            ],

            'sqlite - initial-imports (string) - no migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->initialImports([
                        'sqlite' => "$this->wsInitialImportsDir/initial-import-1.sqlite",
                    ]),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.9b943b-6f53bc40d6ed.sqlite")
                    ->expectedTables(['initial_import', '____adapt____'])
                    ->addExpectedValues($evInitialImportOne),
            ],
            'sqlite - initial-imports (array) - no migrations - no seeders' => [
                'config' => $this->newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->initialImports([
                        'sqlite' => ["$this->wsInitialImportsDir/initial-import-1.sqlite"],
                    ]),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName("$this->wsAdaptStorageDir/test-database.9b943b-2bed70196779.sqlite")
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
                    ->databaseName("$this->wsAdaptStorageDir/test-database.9b943b-3a88252c3e8c.sqlite")
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
                    ->databaseName("$this->wsAdaptStorageDir/test-database.9b943b-2bed70196779.sqlite")
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
                    ->databaseName("$this->wsAdaptStorageDir/test-database.9b943b-50aa324c1e47.sqlite")
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
                    ->databaseName("$this->wsAdaptStorageDir/test-database.9b943b-5232b96a63cc.sqlite")
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
