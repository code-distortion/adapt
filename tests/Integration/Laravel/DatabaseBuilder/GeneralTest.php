<?php

namespace CodeDistortion\Adapt\Tests\Integration\Laravel\DatabaseBuilder;

use CodeDistortion\Adapt\Exceptions\AdaptBuildException;
use CodeDistortion\Adapt\Tests\Integration\Support\AssignClassAlias;
use CodeDistortion\Adapt\Tests\Integration\Support\DatabaseBuilderTestTrait;
use CodeDistortion\Adapt\Tests\LaravelTestCase;
use Throwable;

AssignClassAlias::databaseBuilderSetUpTrait(__NAMESPACE__);

/**
 * Test the DatabaseBuilder class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class GeneralTest extends LaravelTestCase
{
    use DatabaseBuilderSetUpTrait; // this is chosen above by AssignClassAlias depending on the version of Laravel used
    use DatabaseBuilderTestTrait;


    /**
     * Test that the DatabaseBuilder only executes once.
     *
     * @test
     * @return void
     * @throws Throwable Any exception that's not an AdaptBuildException.
     */
    public static function test_database_builder_only_runs_once()
    {
        self::prepareWorkspace(self::$workspaceBaseDir . "/scenario1");

        try {
            self::newDatabaseBuilder()->execute()->execute();
        } catch (Throwable $e) {
            if ($e instanceof AdaptBuildException) {
                self::assertTrue(true);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Test that the DatabaseBuilder creates the sqlite database.
     *
     * @test
     * @return void
     */
    public static function test_database_builder_creates_sqlite_database()
    {
        self::prepareWorkspace(self::$workspaceBaseDir . "/scenario1");

        self::newDatabaseBuilder()->execute();

        $dbPath = config('database.connections.sqlite.database');
        self::assertFileExists($dbPath);
        self::assertSame(
            self::wsAdaptStorageDir() . "/databases/test-database.2881d7-0161442c4a3a.sqlite",
            $dbPath
        );
    }

    /**
     * Test that the DatabaseBuilder sets the default connection.
     *
     * @test
     * @return void
     */
    public static function test_the_default_database_is_set()
    {
        self::prepareWorkspace(self::$workspaceBaseDir . "/scenario1");

        $expected = config('database.default') == 'testing'
            ? 'testing' # this was changed to 'testing' in Laravel 11
            : 'mysql';

        // no change
        self::assertSame($expected, config('database.default'));
        self::newDatabaseBuilder()->execute();
        self::assertSame($expected, config('database.default'));

        self::loadConfigs(self::$wsConfigDir);

        // changed
        self::assertSame($expected, config('database.default'));
        self::newDatabaseBuilder()->execute()->makeDefault();
        self::assertSame('sqlite', config('database.default'));
    }
}
