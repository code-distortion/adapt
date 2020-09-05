<?php

namespace CodeDistortion\Adapt\Tests\Integration\Laravel\DatabaseBuilder;

use CodeDistortion\Adapt\Exceptions\AdaptBuildException;
use CodeDistortion\Adapt\Support\Settings;
use CodeDistortion\Adapt\Tests\Integration\Support\AssignClassAlias;
use CodeDistortion\Adapt\Tests\Integration\Support\DatabaseBuilderTestTrait;
use CodeDistortion\Adapt\Tests\LaravelTestCase;
use DB;
use Throwable;

AssignClassAlias::databaseBuilderSetUpTrait(__NAMESPACE__);

/**
 * Test the DatabaseBuilder class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class GeneralTest extends LaravelTestCase
{
    use DatabaseBuilderSetUpTrait;
    use DatabaseBuilderTestTrait;


    /**
     * Test that the DatabaseBuilder only executes once.
     *
     * @test
     * @return void
     * @throws Throwable Any exception that's not an AdaptBuildException.
     */
    public function test_database_builder_only_runs_once()
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
    public function test_database_builder_creates_adapt_test_storage_dir()
    {
        $this->prepareWorkspace("$this->workspaceBaseDir/scenario1", $this->wsCurrentDir);

        $this->assertFalse(file_exists($this->wsAdaptStorageDir));
        $this->newDatabaseBuilder()->execute();
        $this->assertFileExists($this->wsAdaptStorageDir);
    }

    /**
     * Test that the DatabaseBuilder creates the sqlite database.
     *
     * @test
     * @return void
     */
    public function test_database_builder_creates_sqlite_database()
    {
        $this->prepareWorkspace("$this->workspaceBaseDir/scenario1", $this->wsCurrentDir);

        $this->newDatabaseBuilder()->execute();

        $dbPath = config('database.connections.sqlite.database');
        $this->assertFileExists($dbPath);
        $this->assertSame(
            "$this->wsAdaptStorageDir/test-database.3dd190-3e4b86d50da4.sqlite",
            $dbPath
        );
    }

    /**
     * Test that the DatabaseBuilder sets the default connection.
     *
     * @test
     * @return void
     */
    public function test_the_default_database_is_set()
    {
        $this->prepareWorkspace("$this->workspaceBaseDir/scenario1", $this->wsCurrentDir);

        // no change
        $this->assertSame('mysql', config('database.default'));
        $this->newDatabaseBuilder()->execute();
        $this->assertSame('mysql', config('database.default'));

        $this->loadConfigs($this->wsConfigDir);

        // changed
        $this->assertSame('mysql', config('database.default'));
        $this->newDatabaseBuilder()->execute()->makeDefault();
        $this->assertSame('sqlite', config('database.default'));
    }
}
