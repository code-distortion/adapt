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
     * This method rebuilds it, and stores it in the correct place ready to commit.
     *
     * @test
     * @return void
     * @throws \Throwable
     */
//    public static function create_sqlite_import_file(): void
//    {
//        $configDTO = self::newConfigDTO('sqlite')->migrations(false)->seeders([]);
//        $connection = $configDTO->connection;
//
//        $filename = 'initial-import-1.sqlite';
//
//        $database = self::wsInitialImportsDir() . "/$filename";
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
    public static function structureAndDataSetupDataProvider(): array
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
                'configDTO' => self::newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->scenarios(false),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName(self::wsAdaptStorageDir() . "/databases/database.sqlite")
                    ->expectedTables(['____adapt____']),
            ],

            'sqlite - No initial-imports 1 - no migrations - no seeders' => [
                'configDTO' => self::newConfigDTO('sqlite')->migrations(false)->seeders([]),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName(self::wsAdaptStorageDir() . "/databases/test-database.2881d7-deff164aca4d.sqlite")
                    ->expectedTables(['____adapt____']),
            ],
            'sqlite - No initial-imports 2 - no migrations - no seeders' => [
                'configDTO' => self::newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->initialImports(['sqlite' => '']),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName(self::wsAdaptStorageDir() . "/databases/test-database.2881d7-63cd4b8cddf6.sqlite")
                    ->expectedTables(['____adapt____']),
            ],
            'sqlite - No initial-imports 3 - no migrations - no seeders' => [
                'configDTO' => self::newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->initialImports(['sqlite' => []]),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName(self::wsAdaptStorageDir() . "/databases/test-database.2881d7-16831144fbd1.sqlite")
                    ->expectedTables(['____adapt____']),
            ],

            'sqlite - initial-imports (string) - no migrations - no seeders' => [
                'configDTO' => self::newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->initialImports([
                        'sqlite' => self::wsInitialImportsDir() . "/initial-import-1.sqlite",
                    ]),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName(self::wsAdaptStorageDir() . "/databases/test-database.2881d7-6f53bc40d6ed.sqlite")
                    ->expectedTables(['initial_import', '____adapt____'])
                    ->addExpectedValues($evInitialImportOne),
            ],
            'sqlite - initial-imports (array) - no migrations - no seeders' => [
                'configDTO' => self::newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->initialImports([
                        'sqlite' => [self::wsInitialImportsDir() . "/initial-import-1.sqlite"],
                    ]),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName(self::wsAdaptStorageDir() . "/databases/test-database.2881d7-2bed70196779.sqlite")
                    ->expectedTables(['initial_import', '____adapt____'])
                    ->addExpectedValues($evInitialImportOne),
            ],

            'sqlite - initial-imports - migrations - no seeders' => [
                'configDTO' => self::newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->initialImports([
                        'sqlite' => [self::wsInitialImportsDir() . "/initial-import-1.sqlite"],
                    ])
                    ->migrations(self::wsMigrationsDir()),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName(self::wsAdaptStorageDir() . "/databases/test-database.2881d7-3a88252c3e8c.sqlite")
                    ->expectedTables($allTables)
                    ->addExpectedValues($evInitialImportOne)
                    ->addExpectedValues($evNoUsers)
                    ->addExpectedValues($evNoLogs),
            ],
            'sqlite - initial-imports - no migrations - seeders (one)' => [
                'configDTO' => self::newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->initialImports([
                        'sqlite' => [self::wsInitialImportsDir() . "/initial-import-1.sqlite"],
                    ])
                    ->seeders([InitialImportSeeder::class]),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName(self::wsAdaptStorageDir() . "/databases/test-database.2881d7-3f664106200a.sqlite")
                    ->expectedTables(['initial_import', '____adapt____'])
                    ->addExpectedValues($evInitialImportOneThree)
            ],

            'sqlite - initial-imports - migrations - seeders (one)' => [
                'configDTO' => self::newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->initialImports([
                        'sqlite' => [self::wsInitialImportsDir() . "/initial-import-1.sqlite"],
                    ])
                    ->migrations(self::wsMigrationsDir())
                    ->seeders([InitialImportSeeder::class]),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName(self::wsAdaptStorageDir() . "/databases/test-database.2881d7-50aa324c1e47.sqlite")
                    ->expectedTables($allTables)
                    ->addExpectedValues($evInitialImportOneThree)
                    ->addExpectedValues($evNoUsers)
                    ->addExpectedValues($evNoLogs),
            ],

            'sqlite - initial-imports - migrations - seeders (several)' => [
                'configDTO' => self::newConfigDTO('sqlite')->migrations(false)->seeders([])
                    ->initialImports([
                        'sqlite' => [self::wsInitialImportsDir() . "/initial-import-1.sqlite"],
                    ])
                    ->migrations(self::wsMigrationsDir())
                    ->seeders([DatabaseSeeder::class, InitialImportSeeder::class]),
                'expectedOutcome' => (new ExpectedOutcomeDTO())
                    ->databaseName(self::wsAdaptStorageDir() . "/databases/test-database.2881d7-5232b96a63cc.sqlite")
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
    public static function test_structure_and_data_setup(ConfigDTO $configDTO, ExpectedOutcomeDTO $expectedOutcome)
    {
        self::prepareWorkspace(self::$workspaceBaseDir . "/scenario1");
        self::updateConfigDTODirs($configDTO);

        // build the database
        self::newDatabaseBuilder($configDTO)->execute();

        // check database name
        self::assertSame(
            $expectedOutcome->databaseName,
            config("database.connections.$configDTO->connection.database")
        );

        // check which tables exist
        self::assertTableList($configDTO->connection, $expectedOutcome->expectedTables);

        // check values in certain tables
        foreach ($expectedOutcome->expectedValues as $expectedValueSet) {
            self::assertTableValues($configDTO->connection, $expectedValueSet);
        }
    }
}
