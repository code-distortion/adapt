<?php

namespace CodeDistortion\Adapt\Tests\Integration\Laravel\DatabaseBuilder;

use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Exceptions\AdaptBuildException;
use CodeDistortion\Adapt\Support\Settings;
use CodeDistortion\Adapt\Tests\Integration\Support\AssignClassAlias;
use CodeDistortion\Adapt\Tests\Integration\Support\DatabaseBuilderTestTrait;
use CodeDistortion\Adapt\Tests\LaravelTestCase;
use Illuminate\Support\Facades\DB;
use Throwable;

AssignClassAlias::databaseBuilderSetUpTrait(__NAMESPACE__);

/**
 * Test that the DatabaseBuilder acts correctly in different circumstances in relation to reusing-databases and
 * scenario-database-names.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class ReuseDBTest extends LaravelTestCase
{
    use DatabaseBuilderSetUpTrait; // this is chosen above by AssignClassAlias depending on the version of Laravel used
    use DatabaseBuilderTestTrait;


    /**
     * Provide data for the test_how_databases_are_reused test.
     *
     * @return mixed[][]
     */
    public static function databaseReuseDataProvider(): array
    {
        return [
            'reuseTransaction false, scenarios false, isBrowserTest false' => [
                'configDTO' => self::newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTransaction(false)
                    ->scenarios(false)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' => "UPDATE `" . Settings::REUSE_TABLE . "` SET `inside_transaction` = 0",
                'expectedDBName' => self::wsDatabaseDir() . '/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTransaction true, scenarios false, isBrowserTest false' => [
                'configDTO' => self::newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTransaction(true)
                    ->scenarios(false)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' => "UPDATE `" . Settings::REUSE_TABLE . "` SET `inside_transaction` = 0",
                'expectedDBName' => self::wsDatabaseDir() . '/database.sqlite',
                'expectedUserCount' => 1,
                'expectedException' => null,
            ],
            'reuseTransaction false, scenarios true, isBrowserTest false' => [
                'configDTO' => self::newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTransaction(false)
                    ->scenarios(true)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' => "UPDATE `" . Settings::REUSE_TABLE . "` SET `inside_transaction` = 0",
                'expectedDBName' => self::wsAdaptStorageDir() . '/test-database.80cb3b-4d0a942a4f44.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTransaction true, scenarios true, isBrowserTest false' => [
                'configDTO' => self::newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTransaction(true)
                    ->scenarios(true)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' => "UPDATE `" . Settings::REUSE_TABLE . "` SET `inside_transaction` = 0",
                'expectedDBName' => self::wsAdaptStorageDir() . '/test-database.80cb3b-3c25506a6206.sqlite',
                'expectedUserCount' => 1,
                'expectedException' => null,
            ],
            'reuseTransaction false, scenarios false, isBrowserTest true' => [
                'configDTO' => self::newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTransaction(false)
                    ->scenarios(false)
                    ->isBrowserTest(true),
                'updateReuseTableQuery' => "UPDATE `" . Settings::REUSE_TABLE . "` SET `inside_transaction` = 0",
                'expectedDBName' => self::wsDatabaseDir() . '/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTransaction true, scenarios false, isBrowserTest true' => [
                'configDTO' => self::newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTransaction(true)
                    ->scenarios(false)
                    ->isBrowserTest(true),
                'updateReuseTableQuery' => "UPDATE `" . Settings::REUSE_TABLE . "` SET `inside_transaction` = 0",
                'expectedDBName' => self::wsDatabaseDir() . '/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTransaction false, scenarios true, isBrowserTest true' => [
                'configDTO' => self::newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTransaction(false)
                    ->scenarios(true)
                    ->isBrowserTest(true),
                'updateReuseTableQuery' => "UPDATE `" . Settings::REUSE_TABLE . "` SET `inside_transaction` = 0",
                'expectedDBName' => self::wsAdaptStorageDir() . '/test-database.80cb3b-64d73e5bd418.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTransaction true, scenarios true, isBrowserTest true' => [
                'configDTO' => self::newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTransaction(true)
                    ->scenarios(true)
                    ->isBrowserTest(true),
                'updateReuseTableQuery' => "UPDATE `" . Settings::REUSE_TABLE . "` SET `inside_transaction` = 0",
                'expectedDBName' => self::wsAdaptStorageDir() . '/test-database.80cb3b-11d5e7e68fce.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],

            'reuseTransaction true, different reuse_table_version' => [
                'configDTO' => self::newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTransaction(true)
                    ->scenarios(false)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' =>
                    "UPDATE `" . Settings::REUSE_TABLE . "` "
                    . "SET `inside_transaction` = 0, `reuse_table_version` = 'blahblah'",
                'expectedDBName' => self::wsDatabaseDir() . '/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTransaction true, different project_name' => [
                'configDTO' => self::newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTransaction(true)
                    ->scenarios(false)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' =>
                    "UPDATE `" . Settings::REUSE_TABLE . "` "
                    . "SET `inside_transaction` = 0, `project_name` = 'blahblah'",
                'expectedDBName' => self::wsDatabaseDir() . '/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => AdaptBuildException::class,
            ],
            'reuseTransaction true, still in transaction' => [
                'configDTO' => self::newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTransaction(true)
                    ->scenarios(false)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' => "UPDATE `" . Settings::REUSE_TABLE . "` SET `inside_transaction` = 1",
                'expectedDBName' => self::wsDatabaseDir() . '/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => AdaptBuildException::class,
            ],
            'reuseTransaction true, empty ____adapt____ table' => [
                'configDTO' => self::newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTransaction(true)
                    ->scenarios(false)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' => "DELETE FROM `" . Settings::REUSE_TABLE . "`",
                'expectedDBName' => self::wsDatabaseDir() . '/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTransaction true, no ____adapt____ table' => [
                'configDTO' => self::newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTransaction(true)
                    ->scenarios(false)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' => "DROP TABLE `" . Settings::REUSE_TABLE . "`",
                'expectedDBName' => self::wsDatabaseDir() . '/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
        ];
    }

    /**
     * Test that the DatabaseBuilder's reuse-test-dbs setting works properly.
     *
     * @test
     * @dataProvider databaseReuseDataProvider
     * @param ConfigDTO   $configDTO             The ConfigDTO to use which instructs what and how to build.
     * @param string      $updateReuseTableQuery The query used to update the ____adapt____ table between database
     *                                           builds.
     * @param string      $expectedDBName        The expected name of the database used.
     * @param integer     $expectedUserCount     The expected number of users in the database after the second build.
     * @param string|null $expectedException     The expected exception.
     * @return void
     * @throws Throwable Any exception that's not expected.
     */
    public static function test_how_databases_are_reused(
        ConfigDTO $configDTO,
        string $updateReuseTableQuery,
        string $expectedDBName,
        int $expectedUserCount,
        ?string $expectedException
    ): void {

        self::markTestIncomplete('need to add reuseJournal into the settings');

        self::prepareWorkspace(self::$workspaceBaseDir . "/scenario1");
        self::updateConfigDTODirs($configDTO);

        $configDTO2 = clone($configDTO);

        // set up the database the first time, pretend that the transaction has completed and add a user
        self::newDatabaseBuilder($configDTO, self::newDIContainer($configDTO->connection))->execute();

        DB::connection($configDTO->connection)->update($updateReuseTableQuery);
        DB::connection($configDTO->connection)->insert("INSERT INTO `users` (`username`) VALUES ('abc')");

        self::assertSame($expectedDBName, $configDTO->database);

        // disconnect from the database
        DB::purge($configDTO->connection);

        // set up the database the second time and see if the user is still there
        self::loadConfigs(self::$wsConfigDir);

        // if an exception is expected
        if ($expectedException) {
            try {
                self::newDatabaseBuilder($configDTO2, self::newDIContainer($configDTO2->connection))->execute();
            } catch (Throwable $e) {
                if (!$e instanceof $expectedException) {
                    throw $e;
                }
                self::assertTrue(true);
            }
        // or no exception
        } else {
            self::newDatabaseBuilder($configDTO2, self::newDIContainer($configDTO2->connection))->execute();
            self::assertSame(
                $expectedUserCount,
                (int) DB::connection($configDTO2->connection)->select("SELECT COUNT(*) as total FROM `users`")[0]->total
            );
        }
    }
}
