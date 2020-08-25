<?php

namespace CodeDistortion\Adapt\Tests\Integration\Laravel\DatabaseBuilder;

use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Exceptions\AdaptBuildException;
use CodeDistortion\Adapt\Support\Settings;
use CodeDistortion\Adapt\Tests\Integration\Support\AssignClassAlias;
use CodeDistortion\Adapt\Tests\Integration\Support\DatabaseBuilderTestTrait;
use CodeDistortion\Adapt\Tests\LaravelTestCase;
use DB;
use Throwable;

AssignClassAlias::databaseBuilderSetUpTrait(__NAMESPACE__);

/**
 * Test that the DatabaseBuilder acts correctly in different circumstances in relation to reusing-databases and
 * dynamic-database-names.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class ReuseDBTest extends LaravelTestCase
{
    use DatabaseBuilderSetUpTrait;
    use DatabaseBuilderTestTrait;


    /**
     * Provide data for the test_how_databases_are_reused test.
     *
     * @return mixed[][]
     */
    public function databaseReuseDataProvider(): array
    {
        return [
            'reuseTestDBs false, dynamicTestDBs false, transactions false, isBrowserTest false' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTestDBs(false)
                    ->dynamicTestDBs(false)
                    ->transactions(false)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' => "UPDATE `".Settings::REUSE_TABLE."` SET `inside_transaction` = 0",
                'expectedDBName' => $this->wsDatabaseDir.'/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTestDBs true, dynamicTestDBs false, transactions false, isBrowserTest false' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTestDBs(true)
                    ->dynamicTestDBs(false)
                    ->transactions(false)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' => "UPDATE `".Settings::REUSE_TABLE."` SET `inside_transaction` = 0",
                'expectedDBName' => $this->wsDatabaseDir.'/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTestDBs false, dynamicTestDBs true, transactions false, isBrowserTest false' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTestDBs(false)
                    ->dynamicTestDBs(true)
                    ->transactions(false)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' => "UPDATE `".Settings::REUSE_TABLE."` SET `inside_transaction` = 0",
                'expectedDBName' => $this->wsAdaptStorageDir.'/test-database.3dd190cf729cf1fc-e1ad45abaf58c9fb.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTestDBs true, dynamicTestDBs true, transactions false, isBrowserTest false' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTestDBs(true)
                    ->dynamicTestDBs(true)
                    ->transactions(false)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' => "UPDATE `".Settings::REUSE_TABLE."` SET `inside_transaction` = 0",
                'expectedDBName' => $this->wsAdaptStorageDir.'/test-database.3dd190cf729cf1fc-e1ad45abaf58c9fb.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTestDBs false, dynamicTestDBs false, transactions true, isBrowserTest false' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTestDBs(false)
                    ->dynamicTestDBs(false)
                    ->transactions(true)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' => "UPDATE `".Settings::REUSE_TABLE."` SET `inside_transaction` = 0",
                'expectedDBName' => $this->wsDatabaseDir.'/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTestDBs true, dynamicTestDBs false, transactions true, isBrowserTest false' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTestDBs(true)
                    ->dynamicTestDBs(false)
                    ->transactions(true)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' => "UPDATE `".Settings::REUSE_TABLE."` SET `inside_transaction` = 0",
                'expectedDBName' => $this->wsDatabaseDir.'/database.sqlite',
                'expectedUserCount' => 1,
                'expectedException' => null,
            ],
            'reuseTestDBs false, dynamicTestDBs true, transactions true, isBrowserTest false' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTestDBs(false)
                    ->dynamicTestDBs(true)
                    ->transactions(true)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' => "UPDATE `".Settings::REUSE_TABLE."` SET `inside_transaction` = 0",
                'expectedDBName' => $this->wsAdaptStorageDir.'/test-database.3dd190cf729cf1fc-071e7bc77f7ae33a.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTestDBs true, dynamicTestDBs true, transactions true, isBrowserTest false' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTestDBs(true)
                    ->dynamicTestDBs(true)
                    ->transactions(true)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' => "UPDATE `".Settings::REUSE_TABLE."` SET `inside_transaction` = 0",
                'expectedDBName' => $this->wsAdaptStorageDir.'/test-database.3dd190cf729cf1fc-071e7bc77f7ae33a.sqlite',
                'expectedUserCount' => 1,
                'expectedException' => null,
            ],
            'reuseTestDBs false, dynamicTestDBs false, transactions false, isBrowserTest true' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTestDBs(false)
                    ->dynamicTestDBs(false)
                    ->transactions(false)
                    ->isBrowserTest(true),
                'updateReuseTableQuery' => "UPDATE `".Settings::REUSE_TABLE."` SET `inside_transaction` = 0",
                'expectedDBName' => $this->wsDatabaseDir.'/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTestDBs true, dynamicTestDBs false, transactions false, isBrowserTest true' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTestDBs(true)
                    ->dynamicTestDBs(false)
                    ->transactions(false)
                    ->isBrowserTest(true),
                'updateReuseTableQuery' => "UPDATE `".Settings::REUSE_TABLE."` SET `inside_transaction` = 0",
                'expectedDBName' => $this->wsDatabaseDir.'/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTestDBs false, dynamicTestDBs true, transactions false, isBrowserTest true' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTestDBs(false)
                    ->dynamicTestDBs(true)
                    ->transactions(false)
                    ->isBrowserTest(true),
                'updateReuseTableQuery' => "UPDATE `".Settings::REUSE_TABLE."` SET `inside_transaction` = 0",
                'expectedDBName' => $this->wsDatabaseDir.'/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTestDBs true, dynamicTestDBs true, transactions false, isBrowserTest true' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTestDBs(true)
                    ->dynamicTestDBs(true)
                    ->transactions(false)
                    ->isBrowserTest(true),
                'updateReuseTableQuery' => "UPDATE `".Settings::REUSE_TABLE."` SET `inside_transaction` = 0",
                'expectedDBName' => $this->wsDatabaseDir.'/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTestDBs false, dynamicTestDBs false, transactions true, isBrowserTest true' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTestDBs(false)
                    ->dynamicTestDBs(false)
                    ->transactions(true)
                    ->isBrowserTest(true),
                'updateReuseTableQuery' => "UPDATE `".Settings::REUSE_TABLE."` SET `inside_transaction` = 0",
                'expectedDBName' => $this->wsDatabaseDir.'/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTestDBs true, dynamicTestDBs false, transactions true, isBrowserTest true' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTestDBs(true)
                    ->dynamicTestDBs(false)
                    ->transactions(true)
                    ->isBrowserTest(true),
                'updateReuseTableQuery' => "UPDATE `".Settings::REUSE_TABLE."` SET `inside_transaction` = 0",
                'expectedDBName' => $this->wsDatabaseDir.'/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTestDBs false, dynamicTestDBs true, transactions true, isBrowserTest true' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTestDBs(false)
                    ->dynamicTestDBs(true)
                    ->transactions(true)
                    ->isBrowserTest(true),
                'updateReuseTableQuery' => "UPDATE `".Settings::REUSE_TABLE."` SET `inside_transaction` = 0",
                'expectedDBName' => $this->wsDatabaseDir.'/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTestDBs true, dynamicTestDBs true, transactions true, isBrowserTest true' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTestDBs(true)
                    ->dynamicTestDBs(true)
                    ->transactions(true)
                    ->isBrowserTest(true),
                'updateReuseTableQuery' => "UPDATE `".Settings::REUSE_TABLE."` SET `inside_transaction` = 0",
                'expectedDBName' => $this->wsDatabaseDir.'/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],

            'reuseTestDBs true, transactions true, different reuse_table_version' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTestDBs(true)
                    ->dynamicTestDBs(false)
                    ->transactions(true)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' => "UPDATE `".Settings::REUSE_TABLE."` SET `inside_transaction` = 0, `reuse_table_version` = 'blahblah'",
                'expectedDBName' => $this->wsDatabaseDir.'/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTestDBs true, transactions true, different project_name' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTestDBs(true)
                    ->dynamicTestDBs(false)
                    ->transactions(true)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' => "UPDATE `".Settings::REUSE_TABLE."` SET `inside_transaction` = 0, `project_name` = 'blahblah'",
                'expectedDBName' => $this->wsDatabaseDir.'/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => AdaptBuildException::class,
            ],
            'reuseTestDBs true, transactions true, still in transaction' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTestDBs(true)
                    ->dynamicTestDBs(false)
                    ->transactions(true)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' => "UPDATE `".Settings::REUSE_TABLE."` SET `inside_transaction` = 1",
                'expectedDBName' => $this->wsDatabaseDir.'/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => AdaptBuildException::class,
            ],
            'reuseTestDBs true, transactions true, empty ____adapt____ table' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTestDBs(true)
                    ->dynamicTestDBs(false)
                    ->transactions(true)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' => "DELETE FROM `".Settings::REUSE_TABLE."`",
                'expectedDBName' => $this->wsDatabaseDir.'/database.sqlite',
                'expectedUserCount' => 0,
                'expectedException' => null,
            ],
            'reuseTestDBs true, transactions true, no ____adapt____ table' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->seeders([])
                    ->reuseTestDBs(true)
                    ->dynamicTestDBs(false)
                    ->transactions(true)
                    ->isBrowserTest(false),
                'updateReuseTableQuery' => "DROP TABLE `".Settings::REUSE_TABLE."`",
                'expectedDBName' => $this->wsDatabaseDir.'/database.sqlite',
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
     * @param ConfigDTO   $config                The ConfigDTO to use which instructs what and how to build.
     * @param string      $updateReuseTableQuery The query used to update the ____adapt____ table between database builds.
     * @param string      $expectedDBName        The expected name of the database used.
     * @param int         $expectedUserCount     The expected number of users in the database after the second build.
     * @param string|null $expectedException     The expected exception.
     * @return void
     * @throws Throwable Any exception that's not expected.
     */
    public function test_how_databases_are_reused(
        ConfigDTO $config,
        string $updateReuseTableQuery,
        string $expectedDBName,
        int $expectedUserCount,
        $expectedException
    ) {

        $this->prepareWorkspace("$this->workspaceBaseDir/scenario1", $this->wsCurrentDir);

        $config2 = clone($config);

        // set up the database the first time, pretend that the transaction has completed and add a user
        $this->newDatabaseBuilder($config, $this->newDIContainer($config->connection))->execute();

        DB::connection($config->connection)->update($updateReuseTableQuery);
        DB::connection($config->connection)->insert("INSERT INTO `users` (`username`) VALUES ('abc')");

        $this->assertSame($expectedDBName, $config->database);

        // set up the database the second time and see if the user is still there
        $this->loadConfigs($this->wsConfigDir);

        // if an exception is expected
        if ($expectedException) {
            try {
                $this->newDatabaseBuilder($config2, $this->newDIContainer($config2->connection))->execute();
            } catch (Throwable $e) {
                if (!$e instanceof $expectedException) {
                    throw $e;
                }
                $this->assertTrue(true);
            }
        // or no exception
        } else {
            $this->newDatabaseBuilder($config2, $this->newDIContainer($config2->connection))->execute();
            $this->assertSame(
                (string) $expectedUserCount,
                DB::connection($config2->connection)->select("SELECT COUNT(*) as total FROM `users`")[0]->total
            );
        }
    }
}
