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
            "$this->wsAdaptStorageDir/test-database.338349-e2dee1963369.sqlite",
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
