<?php

namespace CodeDistortion\Adapt\Tests\Unit\DTO;

use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Exceptions\AdaptRemoteShareException;
use CodeDistortion\Adapt\Tests\AssertExceptionTrait;
use CodeDistortion\Adapt\Tests\PHPUnitTestCase;

/**
 * Test the ConfigDTO class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class ConfigDTOTest extends PHPUnitTestCase
{
    use AssertExceptionTrait;

    /**
     * Provide data for the config_dto_can_set_and_get_values test.
     *
     * @return mixed[][]
     */
    public function configDtoDataProvider(): array
    {
        return [
            'dtoVersion' => [
                'method' => 'dtoVersion',
                'params' => ['dtoVersion' => 5],
            ],

            'projectName' => [
                'method' => 'projectName',
                'params' => ['projectName' => 'my-project'],
            ],

            'testName' => [
                'method' => 'testName',
                'params' => ['testName' => 'Some test'],
            ],

            'connection' => [
                'method' => 'connection',
                'params' => ['connection' => 'mysql'],
            ],

            'connectionExists' => [
                'method' => 'connectionExists',
                'params' => ['connectionExists' => true],
            ],

            'driver' => [
                'method' => 'driver',
                'params' => ['driver' => 'mysql'],
            ],

            'origDatabase' => [
                'method' => 'origDatabase',
                'params' => ['origDatabase' => 'orig_database'],
            ],

            'database' => [
                'method' => 'database',
                'params' => ['database' => 'new_database'],
            ],

            'databaseModifier' => [
                'method' => 'databaseModifier',
                'params' => ['databaseModifier' => '_1'],
            ],

            'storageDir' => [
                'method' => 'storageDir',
                'params' => ['storageDir' => '/somewhere/databases'],
            ],

            'snapshotPrefix' => [
                'method' => 'snapshotPrefix',
                'params' => ['snapshotPrefix' => 'snapshot.'],
            ],

            'databasePrefix' => [
                'method' => 'databasePrefix',
                'params' => ['databasePrefix' => 'test-'],
            ],

            'checkForSourceChanges' => [
                'method' => 'checkForSourceChanges',
                'params' => ['checkForSourceChanges' => true],
            ],

            'hashPaths' => [
                'method' => 'hashPaths',
                'params' => ['hashPaths' => ['/someplace1', '/someplace2']],
            ],

            'preCalculatedBuildHash' => [
                'method' => 'preCalculatedBuildHash',
                'params' => ['preCalculatedBuildHash' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
            ],

            'buildSettings 1' => [
                'method' => 'buildSettings',
                'params' => [
                    'preMigrationImports' => ['mysql' => 'someFile.sql'],
                    'migrations' => true,
                    'seeders' => ['DatabaseSeeder', 'TestSeeder'],
                    'remoteBuildUrl' => 'https://something',
                    'isBrowserTest' => true,
                    'isRemoteBuild' => false,
                    'sessionDriver' => 'database',
                    'remoteCallerSessionDriver' => null,
                ],
            ],
            'buildSettings 2' => [
                'method' => 'buildSettings',
                'params' => [
                    'preMigrationImports' => ['mysql' => 'someFile.sql'],
                    'migrations' => false,
                    'seeders' => ['DatabaseSeeder', 'TestSeeder'],
                    'remoteBuildUrl' => null,
                    'isBrowserTest' => true,
                    'isRemoteBuild' => false,
                    'sessionDriver' => 'file',
                    'remoteCallerSessionDriver' => 'database',
                ],
            ],
            'buildSettings 3' => [
                'method' => 'buildSettings',
                'params' => [
                    'preMigrationImports' => ['mysql' => 'someFile.sql'],
                    'migrations' => true,
                    'seeders' => ['DatabaseSeeder', 'TestSeeder'],
                    'remoteBuildUrl' => null,
                    'isBrowserTest' => false,
                    'isRemoteBuild' => true,
                    'sessionDriver' => 'database',
                    'remoteCallerSessionDriver' => null,
                ],
            ],
            'buildSettings 4' => [
                'method' => 'buildSettings',
                'params' => [
                    'preMigrationImports' => ['mysql' => 'someFile.sql'],
                    'migrations' => '/migrations-path',
                    'seeders' => ['DatabaseSeeder', 'TestSeeder'],
                    'remoteBuildUrl' => null,
                    'isBrowserTest' => true,
                    'isRemoteBuild' => false,
                    'sessionDriver' => 'database',
                    'remoteCallerSessionDriver' => null,
                ],
            ],

            'preMigrationImports' => [
                'method' => 'preMigrationImports',
                'params' => ['preMigrationImports' => ['mysql' => 'someFile.sql']],
            ],

            'migrations 1' => [
                'method' => 'migrations',
                'params' => ['migrations' => true],
            ],
            'migrations 2' => [
                'method' => 'migrations',
                'params' => ['migrations' => false],
            ],
            'migrations 3' => [
                'method' => 'migrations',
                'params' => ['migrations' => '/migrations-path'],
            ],

            'seeders' => [
                'method' => 'seeders',
                'params' => ['seeders' => ['DatabaseSeeder', 'TestSeeder']],
            ],

            'remoteBuildUrl 1' => [
                'method' => 'remoteBuildUrl',
                'params' => ['remoteBuildUrl' => 'https://something'],
            ],
            'remoteBuildUrl 2' => [
                'method' => 'remoteBuildUrl',
                'params' => ['remoteBuildUrl' => null],
            ],

            'isBrowserTest' => [
                'method' => 'isBrowserTest',
                'params' => ['isBrowserTest' => true],
            ],

            'isRemoteBuild' => [
                'method' => 'isRemoteBuild',
                'params' => ['isRemoteBuild' => true],
            ],

            'sessionDriver' => [
                'method' => 'sessionDriver',
                'params' => ['sessionDriver' => 'database'],
            ],

            'remoteCallerSessionDriver' => [
                'method' => 'remoteCallerSessionDriver',
                'params' => ['remoteCallerSessionDriver' => null],
            ],

            'remoteCallerSessionDriver 2' => [
                'method' => 'remoteCallerSessionDriver',
                'params' => ['remoteCallerSessionDriver' => 'database'],
            ],

            'cacheTools 1' => [
                'method' => 'cacheTools',
                'params' => [
                    'reuseTestDBs' => true,
                    'scenarioTestDBs' => true,
                ],
            ],
            'cacheTools 2' => [
                'method' => 'cacheTools',
                'params' => [
                    'reuseTestDBs' => false,
                    'scenarioTestDBs' => true,
                ],
            ],
            'cacheTools 3' => [
                'method' => 'cacheTools',
                'params' => [
                    'reuseTestDBs' => true,
                    'scenarioTestDBs' => false,
                ],
            ],
            'cacheTools 4' => [
                'method' => 'cacheTools',
                'params' => [
                    'reuseTestDBs' => true,
                    'scenarioTestDBs' => true,
                ],
            ],

            'reuseTestDBs' => [
                'method' => 'reuseTestDBs',
                'params' => [
                    'reuseTestDBs' => true,
                ],
            ],

            'scenarioTestDBs' => [
                'method' => 'scenarioTestDBs',
                'params' => [
                    'scenarioTestDBs' => true,
                ],
            ],

            'snapshots 1' => [
                'method' => 'snapshots',
                'params' => [
                    'useSnapshotsWhenReusingDB' => false,
                    'useSnapshotsWhenNotReusingDB' => false,
                ],
            ],
            'snapshots 2' => [
                'method' => 'snapshots',
                'params' => [
                    'useSnapshotsWhenReusingDB' => 'afterMigrations',
                    'useSnapshotsWhenNotReusingDB' => false,
                ],
            ],
            'snapshots 3' => [
                'method' => 'snapshots',
                'params' => [
                    'useSnapshotsWhenReusingDB' => 'afterSeeders',
                    'useSnapshotsWhenNotReusingDB' => 'afterMigrations',
                ],
            ],
            'snapshots 4' => [
                'method' => 'snapshots',
                'params' => [
                    'useSnapshotsWhenReusingDB' => 'both',
                    'useSnapshotsWhenNotReusingDB' => 'afterSeeders',
                ],
            ],
            'snapshots 5' => [
                'method' => 'snapshots',
                'params' => [
                    'useSnapshotsWhenReusingDB' => false,
                    'useSnapshotsWhenNotReusingDB' => 'both',
                ],
            ],

            'forceRebuild 1' => [
                'method' => 'forceRebuild',
                'params' => [
                    'forceRebuild' => false,
                ],
            ],
            'forceRebuild 2' => [
                'method' => 'forceRebuild',
                'params' => [
                    'forceRebuild' => true,
                ],
            ],

            'mysqlSettings' => [
                'method' => 'mysqlSettings',
                'params' => [
                    'mysqlExecutablePath' => 'somewhere/mysql',
                    'mysqldumpExecutablePath' => 'somewhere/mysqldump',
                ],
            ],

            'postgresSettings' => [
                'method' => 'postgresSettings',
                'params' => [
                    'psqlExecutablePath' => 'somewhere/psql',
                    'pgDumpExecutablePath' => 'somewhere/pg_dump',
                ],
            ],

            'staleGraceSeconds' => [
                'method' => 'staleGraceSeconds',
                'params' => ['staleGraceSeconds' => 100],
            ],
        ];
    }

    /**
     * Test that the ConfigDTO object can set and get values properly.
     *
     * @test
     * @dataProvider configDtoDataProvider
     * @param string       $method  The set method to call.
     * @param mixed[]      $params  The parameters to pass to this set method, and the values to check after.
     * @param mixed[]|null $outcome The outcome values to check for (uses $params if not given).
     * @return void
     */
    public function config_dto_can_set_and_get_values(string $method, array $params, array $outcome = null): void
    {
        $config = new ConfigDTO();

        $callable = [$config, $method];
        if (is_callable($callable)) {
            call_user_func_array($callable, $params);
        }

        $outcome ??= $params;
        foreach ($outcome as $name => $value) {
            $this->assertSame($value, $config->$name);
        }
    }



    /**
     * Test the ConfigDTO->pickSeedersToInclude() getter.
     *
     * @test
     * @return void
     */
    public function test_pick_seeders_to_include_getter(): void
    {
        $seeders = ['DatabaseSeeder', 'TestSeeder'];
        $config = (new ConfigDTO())->seeders($seeders);

        $config->migrations(true);
        $this->assertSame($seeders, $config->pickSeedersToInclude());

        $config->migrations(false);
        $this->assertSame([], $config->pickSeedersToInclude());
    }



    /**
     * Provide data for the test_pick_pre_migration_dumps_getter test.
     *
     * @return mixed[][]
     */
    public function pickPreMigrationDumpsDataProvider(): array
    {
        return [
            [
                'preMigrationImports' => [
                    'mysql' => ['database/dumps/mysql/my-database.sql'],
                    'sqlite' => ['database/dumps/sqlite/my-database.sqlite'],
                ],
                'driver' => 'mysql',
                'expected' => ['database/dumps/mysql/my-database.sql'],
            ],
            [
                'preMigrationImports' => [
                    'mysql' => ['database/dumps/mysql/my-database.sql'],
                    'sqlite' => ['database/dumps/sqlite/my-database.sqlite'],
                ],
                'driver' => 'sqlite',
                'expected' => ['database/dumps/sqlite/my-database.sqlite'],
            ],
            [
                'preMigrationImports' => [
                    'mysql' => ['database/dumps/mysql/my-database.sql'],
                    'sqlite' => ['database/dumps/sqlite/my-database.sqlite'],
                ],
                'driver' => 'blah',
                'expected' => [],
            ],
            [
                'preMigrationImports' => [
                    'mysql' => 'database/dumps/mysql/my-database.sql',
                    'sqlite' => 'database/dumps/sqlite/my-database.sqlite'
                ],
                'driver' => 'mysql',
                'expected' => ['database/dumps/mysql/my-database.sql'],
            ],
            [
                'preMigrationImports' => [
                    'mysql' => '',
                    'sqlite' => ''
                ],
                'driver' => 'mysql',
                'expected' => [],
            ],
        ];
    }

    /**
     * Test the ConfigDTO->pickPreMigrationDumps() getter.
     *
     * @test
     * @dataProvider pickPreMigrationDumpsDataProvider
     * @param array<int, string|string[]> $preMigrationImports The pre-migration-imports value (same as what could be put in the config).
     * @param string                      $driver              The driver to read from.
     * @param mixed                       $expected            The expected output.
     * @return void
     */
    public function test_pick_pre_migration_dumps_getter(array $preMigrationImports, string $driver, $expected): void
    {
        $this->assertSame(
            $expected,
            (new ConfigDTO())->preMigrationImports($preMigrationImports)->driver($driver)->pickPreMigrationImports()
        );
    }



    /**
     * Test ConfigDTO->shouldInitialise().
     *
     * @test
     * @return void
     */
    public function test_should_initialise(): void
    {
        $this->assertTrue((new ConfigDTO())->connectionExists(true)->shouldInitialise());
        $this->assertFalse((new ConfigDTO())->connectionExists(false)->shouldInitialise());
    }



    /**
     * DataProvider for the test_check_that_session_drivers_match test.
     *
     * @return array
     */
    public function sessionDriversDataProvider(): array
    {
        return [
            [
                'isRemoteBuild' => false,
                'isBrowserTest' => true,
                'sessionDriver' => 'database',
                'remoteCallerSessionDriver' => 'file',
                'expectException' => null,
            ],
            [
                'isRemoteBuild' => true,
                'isBrowserTest' => false,
                'sessionDriver' => 'database',
                'remoteCallerSessionDriver' => 'file',
                'expectException' => null,
            ],
            [
                'isRemoteBuild' => false,
                'isBrowserTest' => false,
                'sessionDriver' => 'database',
                'remoteCallerSessionDriver' => 'file',
                'expectException' => null,
            ],
            [
                'isRemoteBuild' => true,
                'isBrowserTest' => true,
                'sessionDriver' => 'database',
                'remoteCallerSessionDriver' => 'database',
                'expectException' => null,
            ],
            [
                'isRemoteBuild' => true,
                'isBrowserTest' => true,
                'sessionDriver' => 'database',
                'remoteCallerSessionDriver' => 'file',
                'expectException' => AdaptRemoteShareException::class,
            ],
        ];
    }

    /**
     * Test ConfigDTO->checkThatSessionDriversMatch().
     *
     * @test
     * @dataProvider sessionDriversDataProvider
     * @param boolean     $isRemoteBuild             The isRemoteBuild value.
     * @param boolean     $isBrowserTest             The isBrowserTest value.
     * @param string      $sessionDriver             The sessionDriver value.
     * @param string      $remoteCallerSessionDriver The remoteCallerSessionDriver value.
     * @param string|null $expectException           The expected exception
     * @return void
     */
    public function test_check_that_session_drivers_match(
        bool $isRemoteBuild,
        bool $isBrowserTest,
        string $sessionDriver,
        string $remoteCallerSessionDriver,
        ?string $expectException
    ): void {

        $callback = fn() => (new ConfigDTO())
            ->isRemoteBuild($isRemoteBuild)
            ->isBrowserTest($isBrowserTest)
            ->sessionDriver($sessionDriver)
            ->remoteCallerSessionDriver($remoteCallerSessionDriver)
            ->checkThatSessionDriversMatch();

        $this->assertException($expectException, $callback);
    }



    /**
     * Test ConfigDTO->usingReuseTestDBs().
     *
     * @test
     * @return void
     */
    public function test_using_reuse_test_dbs(): void
    {
        $this->assertTrue((new ConfigDTO())->reuseTestDBs(true)->isBrowserTest(false)->usingReuseTestDBs());
        $this->assertFalse((new ConfigDTO())->reuseTestDBs(true)->isBrowserTest(true)->usingReuseTestDBs());
        $this->assertFalse((new ConfigDTO())->reuseTestDBs(false)->isBrowserTest(false)->usingReuseTestDBs());
        $this->assertFalse((new ConfigDTO())->reuseTestDBs(false)->isBrowserTest(true)->usingReuseTestDBs());
    }



    /**
     * Test ConfigDTO->dbWillBeReusable().
     *
     * @test
     * @return void
     */
    public function test_db_will_be_reusable(): void
    {
        $this->assertTrue((new ConfigDTO())->reuseTestDBs(true)->isBrowserTest(false)->dbWillBeReusable());
        $this->assertFalse((new ConfigDTO())->reuseTestDBs(true)->isBrowserTest(true)->dbWillBeReusable());
        $this->assertFalse((new ConfigDTO())->reuseTestDBs(false)->isBrowserTest(false)->dbWillBeReusable());
        $this->assertFalse((new ConfigDTO())->reuseTestDBs(false)->isBrowserTest(true)->dbWillBeReusable());
    }



    /**
     * Test ConfigDTO->usingTransactions().
     *
     * @test
     * @return void
     */
    public function test_using_transactions(): void
    {
        $this->assertFalse(
            $this->newConfigReusableDB()
                ->isRemoteBuild(false)
                ->connectionExists(false)
                ->usingTransactions()
        );

        $this->assertTrue(
            $this->newConfigReusableDB()
                ->isRemoteBuild(false)
                ->connectionExists(true)
                ->usingTransactions()
        );

        $this->assertFalse(
            $this->newConfigReusableDB()
                ->isRemoteBuild(true)
                ->connectionExists(false)
                ->usingTransactions()
        );

        $this->assertFalse(
            $this->newConfigNotReusableDB()
                ->isRemoteBuild(true)
                ->connectionExists(true)
                ->usingTransactions()
        );
    }



    /**
     * Test ConfigDTO->usingScenarioTestDBs().
     *
     * @test
     * @return void
     */
    public function test_using_scenario_test_dbs(): void
    {
        $this->assertTrue((new ConfigDTO())->scenarioTestDBs(true)->usingScenarioTestDBs());
        $this->assertFalse((new ConfigDTO())->scenarioTestDBs(false)->usingScenarioTestDBs());
    }



    /**
     * Test ConfigDTO->shouldBuildRemotely().
     *
     * @test
     * @return void
     */
    public function test_should_build_remotely(): void
    {
        $this->assertTrue((new ConfigDTO())->remoteBuildUrl('https://some-host/')->shouldBuildRemotely());
        $this->assertFalse((new ConfigDTO())->remoteBuildUrl('')->shouldBuildRemotely());
        $this->assertFalse((new ConfigDTO())->remoteBuildUrl(null)->shouldBuildRemotely());
    }



    /**
     * Test ConfigDTO->seedingIsAllowed().
     *
     * @test
     * @return void
     */
    public function test_seeding_is_allowed(): void
    {
        $this->assertTrue((new ConfigDTO())->migrations(true)->seedingIsAllowed());
        $this->assertTrue((new ConfigDTO())->migrations('/some/path')->seedingIsAllowed());
        $this->assertFalse((new ConfigDTO())->migrations(false)->seedingIsAllowed());
    }



    /**
     * Test ConfigDTO->snapshotsAreEnabled().
     *
     * @test
     * @return void
     */
    public function test_snapshots_are_enabled(): void
    {
        $this->assertTrue(
            $this->newConfigReusableDB()
                ->snapshots('afterMigrations', false)
                ->snapshotsAreEnabled()
        );

        $this->assertTrue(
            $this->newConfigNotReusableDB()
                ->snapshots(false, 'afterMigrations')
                ->snapshotsAreEnabled()
        );

        $this->assertFalse(
            $this->newConfigReusableDB()
                ->snapshots(false, false)
                ->snapshotsAreEnabled()
        );

        $this->assertFalse(
            $this->newConfigNotReusableDB()
                ->snapshots(false, false)
                ->snapshotsAreEnabled()
        );
    }



    /**
     * Test ConfigDTO->snapshotType().
     *
     * @test
     * @return void
     */
    public function test_snapshot_type(): void
    {
        $this->assertSame(
            'afterMigrations',
            $this->newConfigReusableDB()
                ->snapshots('afterMigrations', false)
                ->snapshotType()
        );

        $this->assertSame(
            'afterMigrations',
            $this->newConfigNotReusableDB()
                ->snapshots(false, 'afterMigrations')
                ->snapshotType()
        );

        $this->assertNull(
            $this->newConfigReusableDB()
                ->snapshots(false, false)
                ->snapshotType()
        );

        $this->assertNull(
            $this->newConfigNotReusableDB()
                ->snapshots(false, false)
                ->snapshotType()
        );
    }



    /**
     * Provide data for the test_should_take_snapshot_after_migrations_and_seeders test.
     *
     * @return void
     */
    public function shouldTakeSnapshotsDataProvider(): array
    {
        $return = [
            // reusable database - but with no migrations and no seeders
            [
                'reusableDB' => true,
                'migrations' => false,
                'seeders' => [],
                'useSnapshotsWhenReusingDB' => false,
                'useSnapshotsWhenNotReusingDB' => false,
                'afterMigrationsExpected' => false,
                'afterSeedersExpected' => false,
            ],
            [
                'reusableDB' => true,
                'migrations' => false,
                'seeders' => [],
                'useSnapshotsWhenReusingDB' => 'afterMigrations',
                'useSnapshotsWhenNotReusingDB' => false,
                'afterMigrationsExpected' => false,
                'afterSeedersExpected' => false,
            ],
            [
                'reusableDB' => true,
                'migrations' => false,
                'seeders' => [],
                'useSnapshotsWhenReusingDB' => 'afterSeeders',
                'useSnapshotsWhenNotReusingDB' => false,
                'afterMigrationsExpected' => false,
                'afterSeedersExpected' => false,
            ],
            [
                'reusableDB' => true,
                'migrations' => false,
                'seeders' => [],
                'useSnapshotsWhenReusingDB' => 'both',
                'useSnapshotsWhenNotReusingDB' => false,
                'afterMigrationsExpected' => false,
                'afterSeedersExpected' => false,
            ],

            // reusable database - with migrations but no seeders
            [
                'reusableDB' => true,
                'migrations' => true,
                'seeders' => [],
                'useSnapshotsWhenReusingDB' => false,
                'useSnapshotsWhenNotReusingDB' => false,
                'afterMigrationsExpected' => false,
                'afterSeedersExpected' => false,
            ],
            [
                'reusableDB' => true,
                'migrations' => true,
                'seeders' => [],
                'useSnapshotsWhenReusingDB' => 'afterMigrations',
                'useSnapshotsWhenNotReusingDB' => false,
                'afterMigrationsExpected' => true,
                'afterSeedersExpected' => false,
            ],
            [
                'reusableDB' => true,
                'migrations' => true,
                'seeders' => [],
                'useSnapshotsWhenReusingDB' => 'afterSeeders',
                'useSnapshotsWhenNotReusingDB' => false,
                'afterMigrationsExpected' => true,
                'afterSeedersExpected' => false,
            ],
            [
                'reusableDB' => true,
                'migrations' => true,
                'seeders' => [],
                'useSnapshotsWhenReusingDB' => 'both',
                'useSnapshotsWhenNotReusingDB' => false,
                'afterMigrationsExpected' => true,
                'afterSeedersExpected' => false,
            ],

            // reusable database - with migrations and seeders
            [
                'reusableDB' => true,
                'migrations' => true,
                'seeders' => ['DatabaseSeeder'],
                'useSnapshotsWhenReusingDB' => false,
                'useSnapshotsWhenNotReusingDB' => false,
                'afterMigrationsExpected' => false,
                'afterSeedersExpected' => false,
            ],
            [
                'reusableDB' => true,
                'migrations' => true,
                'seeders' => ['DatabaseSeeder'],
                'useSnapshotsWhenReusingDB' => 'afterMigrations',
                'useSnapshotsWhenNotReusingDB' => false,
                'afterMigrationsExpected' => true,
                'afterSeedersExpected' => false,
            ],
            [
                'reusableDB' => true,
                'migrations' => true,
                'seeders' => ['DatabaseSeeder'],
                'useSnapshotsWhenReusingDB' => 'afterSeeders',
                'useSnapshotsWhenNotReusingDB' => false,
                'afterMigrationsExpected' => false,
                'afterSeedersExpected' => true,
            ],
            [
                'reusableDB' => true,
                'migrations' => true,
                'seeders' => ['DatabaseSeeder'],
                'useSnapshotsWhenReusingDB' => 'both',
                'useSnapshotsWhenNotReusingDB' => false,
                'afterMigrationsExpected' => true,
                'afterSeedersExpected' => true,
            ],
        ];

        $return2 = $return;
        foreach ($return as $set) {

            $set['reusableDB'] = false;
            // swap useSnapshotsWhenReusingDB and useSnapshotsWhenNotReusingDB
            $set['useSnapshotsWhenNotReusingDB'] = $set['useSnapshotsWhenReusingDB'];
            $set['useSnapshotsWhenReusingDB'] = false;
            $return2[] = $set;
        }

        return $return2;
    }

    /**
     * Test ConfigDTO->shouldTakeSnapshotAfterMigrations().
     *
     * @test
     * @dataProvider shouldTakeSnapshotsDataProvider
     * @param boolean        $reusableDB                   Can the database be reused?
     * @param boolean|string $migrations                   The migrations to run.
     * @param array          $seeders                      The seeders to run.
     * @param boolean|string $useSnapshotsWhenReusingDB    Use snapshots when reusing the database?.
     * @param boolean|string $useSnapshotsWhenNotReusingDB Use snapshots when not reusing the database?.
     * @param boolean        $afterMigrationsExpected      Whether to take a snapshot after migrations or not.
     * @param boolean        $afterSeedersExpected         Whether to take a snapshot after seeders or not.
     * @return void
     */
    public function test_should_take_snapshot_after_migrations_and_seeders(
        bool $reusableDB,
        $migrations,
        array $seeders,
        $useSnapshotsWhenReusingDB,
        $useSnapshotsWhenNotReusingDB,
        bool $afterMigrationsExpected,
        bool $afterSeedersExpected
    ): void {

        $configDTO = $reusableDB ? $this->newConfigReusableDB() : $this->newConfigNotReusableDB();

        $configDTO
            ->migrations($migrations)
            ->seeders($seeders)
            ->snapshots(
                $useSnapshotsWhenReusingDB,
                $useSnapshotsWhenNotReusingDB
            );

        $this->assertSame($afterMigrationsExpected, $configDTO->shouldTakeSnapshotAfterMigrations());
        $this->assertSame($afterSeedersExpected, $configDTO->shouldTakeSnapshotAfterSeeders());
    }





    /**
     * Create a new ConfigDTO where the database COULD be reused.
     *
     * @return ConfigDTO
     */
    private function newConfigReusableDB(): ConfigDTO
    {
        return (new ConfigDTO())
            ->reuseTestDBs(true)
            ->isBrowserTest(false);
    }

    /**
     * Create a new ConfigDTO where the database CANNOT be reused.
     *
     * @return ConfigDTO
     */
    private function newConfigNotReusableDB(): ConfigDTO
    {
        return (new ConfigDTO())
            ->reuseTestDBs(false)
            ->isBrowserTest(false);
    }
}
