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

            'dbAdapterSupport' => [
                'method' => 'dbAdapterSupport',
                'params' => [
                    'dbSupportsReUse' => true,
                    'dbSupportsSnapshots' => true,
                    'dbSupportsScenarios' => true,
                    'dbSupportsTransactions' => true,
                    'dbSupportsJournaling' => true,
                    'dbSupportsVerification' => true,
                 ],
            ],

            'dbSupportsReUse' => [
                'method' => 'dbSupportsReUse',
                'params' => ['dbSupportsReUse' => true],
            ],

            'dbSupportsSnapshots' => [
                'method' => 'dbSupportsSnapshots',
                'params' => ['dbSupportsSnapshots' => true],
            ],

            'dbSupportsScenarios' => [
                'method' => 'dbSupportsScenarios',
                'params' => ['dbSupportsScenarios' => true],
            ],

            'dbSupportsTransactions' => [
                'method' => 'dbSupportsTransactions',
                'params' => ['dbSupportsTransactions' => true],
            ],

            'dbSupportsJournaling' => [
                'method' => 'dbSupportsJournaling',
                'params' => ['dbSupportsJournaling' => true],
            ],

            'dbSupportsVerification' => [
                'method' => 'dbSupportsVerification',
                'params' => ['dbSupportsVerification' => true],
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
                    'reuseTransaction' => false,
                    'reuseJournal' => true,
                    'verifyDatabase' => false,
                    'scenarioTestDBs' => true,
                ],
            ],
            'cacheTools 2' => [
                'method' => 'cacheTools',
                'params' => [
                    'reuseTransaction' => false,
                    'reuseJournal' => false,
                    'verifyDatabase' => true,
                    'scenarioTestDBs' => true,
                ],
            ],
            'cacheTools 3' => [
                'method' => 'cacheTools',
                'params' => [
                    'reuseTransaction' => true,
                    'reuseJournal' => true,
                    'verifyDatabase' => false,
                    'scenarioTestDBs' => false,
                ],
            ],
            'cacheTools 4' => [
                'method' => 'cacheTools',
                'params' => [
                    'reuseTransaction' => true,
                    'reuseJournal' => false,
                    'verifyDatabase' => true,
                    'scenarioTestDBs' => false,
                ],
            ],

            'reuseTransaction' => [
                'method' => 'reuseTransaction',
                'params' => [
                    'reuseTransaction' => true,
                ],
            ],

            'reuseJournal' => [
                'method' => 'reuseJournal',
                'params' => [
                    'reuseJournal' => true,
                ],
            ],

            'verifyDatabase' => [
                'method' => 'verifyDatabase',
                'params' => [
                    'verifyDatabase' => true,
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
        $configDTO = new ConfigDTO();

        $callable = [$configDTO, $method];
        if (is_callable($callable)) {
            call_user_func_array($callable, $params);
        }

        $outcome ??= $params;
        foreach ($outcome as $name => $value) {
            $this->assertSame($value, $configDTO->$name);
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
        $configDTO = (new ConfigDTO())->seeders($seeders);

        $configDTO->migrations(true);
        $this->assertSame($seeders, $configDTO->pickSeedersToInclude());

        $configDTO->migrations(false);
        $this->assertSame([], $configDTO->pickSeedersToInclude());
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
     * @param array<int, string|string[]> $preMigrationImports The pre-migration-imports value (same as what could be
     *                                                         put in the config).
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
     * @return mixed[][]
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
     * @param string|null $expectException           The expected exception.
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
            ->ensureThatSessionDriversMatch();

        $this->assertException($expectException, $callback);
    }



    /**
     * Provide data for the test_can_use_transactions test.
     *
     * @return mixed[][]
     */
    public function databaseCanUseTransactionsDataProvider(): array
    {
        return [
            [
                'reuseTransaction' => true,
                'connectionExists' => true,
                'isRemoteBuild' => false,
                'isBrowserTest' => false,
                'dbSupportsTransactions' => true,
                'expectedCanUseTransactions' => true,
            ],
            [
                'reuseTransaction' => true,
                'connectionExists' => false, // off
                'isRemoteBuild' => false,
                'isBrowserTest' => false,
                'dbSupportsTransactions' => true,
                'expectedCanUseTransactions' => false,
            ],
            [
                'reuseTransaction' => true,
                'connectionExists' => true,
                'isRemoteBuild' => true, // true
                'isBrowserTest' => false,
                'dbSupportsTransactions' => true,
                'expectedCanUseTransactions' => true,
            ],
            [
                'reuseTransaction' => true,
                'connectionExists' => true,
                'isRemoteBuild' => false,
                'isBrowserTest' => true, // true
                'dbSupportsTransactions' => true,
                'expectedCanUseTransactions' => false,
            ],
            [
                'reuseTransaction' => true,
                'connectionExists' => true,
                'isRemoteBuild' => false,
                'isBrowserTest' => false,
                'dbSupportsTransactions' => false, // false
                'expectedCanUseTransactions' => false,
            ],

            [
                'reuseTransaction' => false,
                'connectionExists' => true,
                'isRemoteBuild' => false,
                'isBrowserTest' => false,
                'dbSupportsTransactions' => true,
                'expectedCanUseTransactions' => false,
            ],
            [
                'reuseTransaction' => false,
                'connectionExists' => false, // off
                'isRemoteBuild' => false,
                'isBrowserTest' => false,
                'dbSupportsTransactions' => true,
                'expectedCanUseTransactions' => false,
            ],
            [
                'reuseTransaction' => false,
                'connectionExists' => true,
                'isRemoteBuild' => true, // true
                'isBrowserTest' => false,
                'dbSupportsTransactions' => true,
                'expectedCanUseTransactions' => false,
            ],
            [
                'reuseTransaction' => false,
                'connectionExists' => true,
                'isRemoteBuild' => false,
                'isBrowserTest' => true, // true
                'dbSupportsTransactions' => true,
                'expectedCanUseTransactions' => false,
            ],
            [
                'reuseTransaction' => false,
                'connectionExists' => true,
                'isRemoteBuild' => false,
                'isBrowserTest' => false,
                'dbSupportsTransactions' => false, // false
                'expectedCanUseTransactions' => false,
            ],
        ];
    }

    /**
     * Test ConfigDTO->canUseTransactions().
     *
     * @test
     * @dataProvider databaseCanUseTransactionsDataProvider
     * @param boolean $reuseTransaction           The "reuse-transaction" setting.
     * @param boolean $connectionExists           Whether the connection exists or not.
     * @param boolean $isRemoteBuild              Is this process building a db for another Adapt installation?.
     * @param boolean $isBrowserTest              Is this test a browser-test?.
     * @param boolean $dbSupportsTransactions     Whether the database supports transactions or not.
     * @param boolean $expectedCanUseTransactions The expected canUseTransactions() result.
     * @return void
     */
    public function test_can_use_transactions(
        bool $reuseTransaction,
        bool $connectionExists,
        bool $isRemoteBuild,
        bool $isBrowserTest,
        bool $dbSupportsTransactions,
        bool $expectedCanUseTransactions
    ): void {

        $configDTO = (new ConfigDTO())
            ->reuseTransaction($reuseTransaction)
            ->reuseJournal(true)
            ->connectionExists($connectionExists)
            ->isRemoteBuild($isRemoteBuild)
            ->isBrowserTest($isBrowserTest)
            ->dbSupportsReUse($dbSupportsTransactions)
            ->dbSupportsTransactions($dbSupportsTransactions)
            ->dbSupportsJournaling(true);

        $this->assertSame($expectedCanUseTransactions, $configDTO->canUseTransactions());
    }



    /**
     * Provide data for the test_can_use_journaling test.
     *
     * @return mixed[][]
     */
    public function databaseCanUseJournalingDataProvider(): array
    {
        return [
            [
                'reuseJournal' => true,
                'connectionExists' => true,
                'isRemoteBuild' => false,
                'isBrowserTest' => false,
                'dbSupportsJournaling' => true,
                'expectedCanUseJournaling' => true,
            ],
            [
                'reuseJournal' => true,
                'connectionExists' => false, // off
                'isRemoteBuild' => false,
                'isBrowserTest' => false,
                'dbSupportsJournaling' => true,
                'expectedCanUseJournaling' => false,
            ],
            [
                'reuseJournal' => true,
                'connectionExists' => true,
                'isRemoteBuild' => true, // true
                'isBrowserTest' => false,
                'dbSupportsJournaling' => true,
                'expectedCanUseJournaling' => true,
            ],
            [
                'reuseJournal' => true,
                'connectionExists' => true,
                'isRemoteBuild' => false,
                'isBrowserTest' => true, // true
                'dbSupportsJournaling' => true,
                'expectedCanUseJournaling' => true, // journaling is allowed for browser tests
            ],
            [
                'reuseJournal' => true,
                'connectionExists' => true,
                'isRemoteBuild' => false,
                'isBrowserTest' => false,
                'dbSupportsJournaling' => false, // false
                'expectedCanUseJournaling' => false,
            ],

            [
                'reuseJournal' => false,
                'connectionExists' => true,
                'isRemoteBuild' => false,
                'isBrowserTest' => false,
                'dbSupportsJournaling' => true,
                'expectedCanUseJournaling' => false,
            ],
            [
                'reuseJournal' => false,
                'connectionExists' => false, // off
                'isRemoteBuild' => false,
                'isBrowserTest' => false,
                'dbSupportsJournaling' => true,
                'expectedCanUseJournaling' => false,
            ],
            [
                'reuseJournal' => false,
                'connectionExists' => true,
                'isRemoteBuild' => true, // true
                'isBrowserTest' => false,
                'dbSupportsJournaling' => true,
                'expectedCanUseJournaling' => false,
            ],
            [
                'reuseJournal' => false,
                'connectionExists' => true,
                'isRemoteBuild' => false,
                'isBrowserTest' => true, // true
                'dbSupportsJournaling' => true,
                'expectedCanUseJournaling' => false,
            ],
            [
                'reuseJournal' => false,
                'connectionExists' => true,
                'isRemoteBuild' => false,
                'isBrowserTest' => false,
                'dbSupportsJournaling' => false, // false
                'expectedCanUseJournaling' => false,
            ],
        ];
    }

    /**
     * Test ConfigDTO->canUseJournaling().
     *
     * @test
     * @dataProvider databaseCanUseJournalingDataProvider
     * @param boolean $reuseJournal             The "reuse-journal" setting.
     * @param boolean $connectionExists         Whether the connection exists or not.
     * @param boolean $isRemoteBuild            Is this process building a db for another Adapt installation?.
     * @param boolean $isBrowserTest            Is this test a browser-test?.
     * @param boolean $dbSupportsJournaling     Whether the database supports journaling or not.
     * @param boolean $expectedCanUseJournaling The expected canUseJournaling() result.
     * @return void
     */
    public function test_can_use_journaling(
        bool $reuseJournal,
        bool $connectionExists,
        bool $isRemoteBuild,
        bool $isBrowserTest,
        bool $dbSupportsJournaling,
        bool $expectedCanUseJournaling
    ): void {

        $configDTO = (new ConfigDTO())
            ->reuseTransaction(true)
            ->reuseJournal($reuseJournal)
            ->connectionExists($connectionExists)
            ->isRemoteBuild($isRemoteBuild)
            ->isBrowserTest($isBrowserTest)
            ->dbSupportsReUse(true)
            ->dbSupportsTransactions(true)
            ->dbSupportsJournaling($dbSupportsJournaling);

        $this->assertSame($expectedCanUseJournaling, $configDTO->canUseJournaling());
    }



    /**
     * Provide data for the test_should_use_transactions_or_journaling test.
     *
     * @return mixed[][]
     */
    public function databaseShouldUseTransactionsOrJournalingDataProvider(): array
    {
        return [
            [
                'reuseTransaction' => true,
                'reuseJournal' => true,
                'expectedShouldUseTransactions' => true,
                'expectedShouldUseJournaling' => false,
                'expectedReusingDB' => true,
            ],
            [
                'reuseTransaction' => true,
                'reuseJournal' => false,
                'expectedShouldUseTransactions' => true,
                'expectedShouldUseJournaling' => false,
                'expectedReusingDB' => true,
            ],
            [
                'reuseTransaction' => false,
                'reuseJournal' => true,
                'expectedShouldUseTransactions' => false,
                'expectedShouldUseJournaling' => true,
                'expectedReusingDB' => true,
            ],
            [
                'reuseTransaction' => false,
                'reuseJournal' => false,
                'expectedShouldUseTransactions' => false,
                'expectedShouldUseJournaling' => false,
                'expectedReusingDB' => false,
            ],
        ];
    }

    /**
     * Test ConfigDTO->shouldUseTransactions(), ConfigDTO->shouldUseJournaling() and ConfigDTO->reusingDB().
     *
     * @test
     * @dataProvider databaseShouldUseTransactionsOrJournalingDataProvider
     * @param boolean $reuseTransaction              The "reuse-transaction" setting.
     * @param boolean $reuseJournal                  The "reuse-journal" setting.
     * @param boolean $expectedShouldUseTransactions The expected canUseTransactions() result.
     * @param boolean $expectedShouldUseJournaling   The expected canUseJournaling() result.
     * @param boolean $expectedReusingDB             The expected reusingDB() result.
     * @return void
     */
    public function test_should_use_transactions_or_journaling(
        bool $reuseTransaction,
        bool $reuseJournal,
        bool $expectedShouldUseTransactions,
        bool $expectedShouldUseJournaling,
        bool $expectedReusingDB
    ): void {

        $configDTO = (new ConfigDTO())
            ->reuseTransaction($reuseTransaction)
            ->reuseJournal($reuseJournal)
            ->connectionExists(true)
            ->isRemoteBuild(false)
            ->isBrowserTest(false)
            ->dbSupportsReUse(true)
            ->dbSupportsTransactions(true)
            ->dbSupportsJournaling(true);

        $this->assertSame($expectedShouldUseTransactions, $configDTO->shouldUseTransaction());
        $this->assertSame($expectedShouldUseJournaling, $configDTO->shouldUseJournal());
        $this->assertSame($expectedReusingDB, $configDTO->reusingDB());
    }



    /**
     * Provide data for the test_should_verify_database test.
     *
     * @return mixed[][]
     */
    public function databaseShouldVerifyDatabaseDataProvider(): array
    {
        return [
            [
                'verifyDatabase' => true,
                'dbSupportsVerification' => true,
                'shouldVerifyStructure' => true,
                'shouldVerifyData' => true,
                'expectedShouldVerifyDatabase' => true,
            ],
            [
                'verifyDatabase' => true,
                'dbSupportsVerification' => false,
                'shouldVerifyStructure' => false,
                'shouldVerifyData' => false,
                'expectedShouldVerifyDatabase' => false,
            ],
            [
                'verifyDatabase' => false,
                'dbSupportsVerification' => true,
                'shouldVerifyStructure' => false,
                'shouldVerifyData' => false,
                'expectedShouldVerifyDatabase' => false,
            ],
            [
                'verifyDatabase' => false,
                'dbSupportsVerification' => false,
                'shouldVerifyStructure' => false,
                'shouldVerifyData' => false,
                'expectedShouldVerifyDatabase' => false,
            ],
        ];
    }

    /**
     * Test ConfigDTO->shouldVerifyStructure(), ConfigDTO->shouldVerifyData() and ConfigDTO->shouldVerifyDatabase().
     *
     * @test
     * @dataProvider databaseShouldVerifyDatabaseDataProvider
     * @param boolean $verifyDatabase               The "verify-database" setting.
     * @param boolean $dbSupportsVerification       Whether the database supports verification or not.
     * @param boolean $shouldVerifyStructure        The expected shouldVerifyStructure() result.
     * @param boolean $shouldVerifyData             The expected shouldVerifyData() result.
     * @param boolean $expectedShouldVerifyDatabase The expected shouldVerifyDatabase() result.
     * @return void
     */
    public function test_should_verify_database(
        bool $verifyDatabase,
        bool $dbSupportsVerification,
        bool $shouldVerifyStructure,
        bool $shouldVerifyData,
        bool $expectedShouldVerifyDatabase
    ): void {

        $configDTO = (new ConfigDTO())
            ->verifyDatabase($verifyDatabase)
            ->dbSupportsVerification($dbSupportsVerification);

        $this->assertSame($shouldVerifyStructure, $configDTO->shouldVerifyStructure());
        $this->assertSame($shouldVerifyData, $configDTO->shouldVerifyData());
        $this->assertSame($expectedShouldVerifyDatabase, $configDTO->shouldVerifyDatabase());
    }



    /**
     * Test ConfigDTO->usingScenarioTestDBs().
     *
     * @test
     * @return void
     */
    public function test_using_scenario_test_dbs(): void
    {
        $this->assertTrue(
            (new ConfigDTO())->scenarioTestDBs(true)->dbSupportsScenarios(true)->usingScenarioTestDBs()
        );
        $this->assertFalse(
            (new ConfigDTO())->scenarioTestDBs(true)->dbSupportsScenarios(false)->usingScenarioTestDBs()
        );
        $this->assertFalse(
            (new ConfigDTO())->scenarioTestDBs(false)->dbSupportsScenarios(true)->usingScenarioTestDBs()
        );
        $this->assertFalse(
            (new ConfigDTO())->scenarioTestDBs(false)->dbSupportsScenarios(false)->usingScenarioTestDBs()
        );
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
     * Provide data for the test_should_take_snapshot_after_migrations_and_seeders test.
     *
     * @return mixed[][]
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
                'snapshotTypeExpected' => null,
            ],
            [
                'reusableDB' => true,
                'migrations' => false,
                'seeders' => [],
                'useSnapshotsWhenReusingDB' => 'afterMigrations',
                'useSnapshotsWhenNotReusingDB' => false,
                'afterMigrationsExpected' => false,
                'afterSeedersExpected' => false,
                'snapshotTypeExpected' => 'afterMigrations',
            ],
            [
                'reusableDB' => true,
                'migrations' => false,
                'seeders' => [],
                'useSnapshotsWhenReusingDB' => 'afterSeeders',
                'useSnapshotsWhenNotReusingDB' => false,
                'afterMigrationsExpected' => false,
                'afterSeedersExpected' => false,
                'snapshotTypeExpected' => 'afterSeeders',
            ],
            [
                'reusableDB' => true,
                'migrations' => false,
                'seeders' => [],
                'useSnapshotsWhenReusingDB' => 'both',
                'useSnapshotsWhenNotReusingDB' => false,
                'afterMigrationsExpected' => false,
                'afterSeedersExpected' => false,
                'snapshotTypeExpected' => 'both',
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
                'snapshotTypeExpected' => null,
            ],
            [
                'reusableDB' => true,
                'migrations' => true,
                'seeders' => [],
                'useSnapshotsWhenReusingDB' => 'afterMigrations',
                'useSnapshotsWhenNotReusingDB' => false,
                'afterMigrationsExpected' => true,
                'afterSeedersExpected' => false,
                'snapshotTypeExpected' => 'afterMigrations',
            ],
            [
                'reusableDB' => true,
                'migrations' => true,
                'seeders' => [],
                'useSnapshotsWhenReusingDB' => 'afterSeeders',
                'useSnapshotsWhenNotReusingDB' => false,
                'afterMigrationsExpected' => true,
                'afterSeedersExpected' => false,
                'snapshotTypeExpected' => 'afterSeeders',
            ],
            [
                'reusableDB' => true,
                'migrations' => true,
                'seeders' => [],
                'useSnapshotsWhenReusingDB' => 'both',
                'useSnapshotsWhenNotReusingDB' => false,
                'afterMigrationsExpected' => true,
                'afterSeedersExpected' => false,
                'snapshotTypeExpected' => 'both',
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
                'snapshotTypeExpected' => null,
            ],
            [
                'reusableDB' => true,
                'migrations' => true,
                'seeders' => ['DatabaseSeeder'],
                'useSnapshotsWhenReusingDB' => 'afterMigrations',
                'useSnapshotsWhenNotReusingDB' => false,
                'afterMigrationsExpected' => true,
                'afterSeedersExpected' => false,
                'snapshotTypeExpected' => 'afterMigrations',
            ],
            [
                'reusableDB' => true,
                'migrations' => true,
                'seeders' => ['DatabaseSeeder'],
                'useSnapshotsWhenReusingDB' => 'afterSeeders',
                'useSnapshotsWhenNotReusingDB' => false,
                'afterMigrationsExpected' => false,
                'afterSeedersExpected' => true,
                'snapshotTypeExpected' => 'afterSeeders',
            ],
            [
                'reusableDB' => true,
                'migrations' => true,
                'seeders' => ['DatabaseSeeder'],
                'useSnapshotsWhenReusingDB' => 'both',
                'useSnapshotsWhenNotReusingDB' => false,
                'afterMigrationsExpected' => true,
                'afterSeedersExpected' => true,
                'snapshotTypeExpected' => 'both',
            ],
        ];

        $return2 = $return;
        foreach ($return as $set) {

            $set['reusableDB'] = false;
            // swap useSnapshotsWhenReusingDB and useSnapshotsWhenNotReusingDB
            $set['useSnapshotsWhenNotReusingDB'] = $set['useSnapshotsWhenReusingDB'];
            $set['useSnapshotsWhenReusingDB'] = false;
            $set['snapshotTypeExpected'] = $set['useSnapshotsWhenNotReusingDB'] ?: null;
            $return2[] = $set;
        }

        return $return2;
    }

    /**
     * Test ConfigDTO->shouldTakeSnapshotAfterMigrations().
     *
     * @test
     * @dataProvider shouldTakeSnapshotsDataProvider
     * @param boolean        $reusableDB                   Can the database be reused?.
     * @param boolean|string $migrations                   The migrations to run.
     * @param string[]       $seeders                      The seeders to run.
     * @param boolean|string $useSnapshotsWhenReusingDB    Use snapshots when reusing the database?.
     * @param boolean|string $useSnapshotsWhenNotReusingDB Use snapshots when not reusing the database?.
     * @param boolean        $afterMigrationsExpected      Whether to take a snapshot after migrations or not.
     * @param boolean        $afterSeedersExpected         Whether to take a snapshot after seeders or not.
     * @param string|null    $snapshotTypeExpected         The type of snapshots to take.
     * @return void
     */
    public function test_should_take_snapshot_after_migrations_and_seeders(
        bool $reusableDB,
        $migrations,
        array $seeders,
        $useSnapshotsWhenReusingDB,
        $useSnapshotsWhenNotReusingDB,
        bool $afterMigrationsExpected,
        bool $afterSeedersExpected,
        ?string $snapshotTypeExpected
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
        $this->assertSame($snapshotTypeExpected, $configDTO->snapshotType());
    }





    /**
     * Create a new ConfigDTO where the database COULD be reused.
     *
     * @return ConfigDTO
     */
    private function newConfigReusableDB(): ConfigDTO
    {
        return (new ConfigDTO())
            ->connectionExists(true)
            ->isRemoteBuild(false)
            ->isBrowserTest(false)
            ->dbSupportsReUse(true)
            ->dbSupportsTransactions(true)
            ->dbSupportsJournaling(true)
            ->reuseTransaction(true)
            ->reuseJournal(true);
    }

    /**
     * Create a new ConfigDTO where the database CANNOT be reused.
     *
     * @return ConfigDTO
     */
    private function newConfigNotReusableDB(): ConfigDTO
    {
        return (new ConfigDTO())
            ->connectionExists(true)
            ->isRemoteBuild(false)
            ->isBrowserTest(false)
            ->dbSupportsReUse(true)
            ->dbSupportsTransactions(true)
            ->dbSupportsJournaling(true)
            ->reuseTransaction(false)
            ->reuseJournal(false);
    }
}
