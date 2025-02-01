<?php

namespace CodeDistortion\Adapt\Tests\Unit\DTO;

use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Exceptions\AdaptRemoteShareException;
use CodeDistortion\Adapt\Tests\AssertExceptionTrait;
use CodeDistortion\Adapt\Tests\PHPUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

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
    public static function configDtoDataProvider(): array
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

            'isDefaultConnection' => [
                'method' => 'isDefaultConnection',
                'params' => ['isDefaultConnection' => true],
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

            'cacheInvalidationMethod 1' => [
                'method' => 'cacheInvalidationMethod',
                'params' => ['cacheInvalidationMethod' => 'content'],
            ],
            'cacheInvalidationMethod 2' => [
                'method' => 'cacheInvalidationMethod',
                'params' => ['cacheInvalidationMethod' => 'modified'],
            ],

            'checksumPaths' => [
                'method' => 'checksumPaths',
                'params' => ['checksumPaths' => ['/someplace1', '/someplace2']],
            ],

            'preCalculatedBuildChecksum' => [
                'method' => 'preCalculatedBuildChecksum',
                'params' => ['preCalculatedBuildChecksum' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
            ],

            'buildSettings 1' => [
                'method' => 'buildSettings',
                'params' => [
                    'initialImports' => ['mysql' => 'someFile.sql'],
                    'migrations' => true,
                    'seeders' => ['DatabaseSeeder', 'TestSeeder'],
                    'remoteBuildUrl' => 'https://something',
                    'isBrowserTest' => true,
                    'isParallelTest' => true,
                    'usingPest' => true,
                    'isRemoteBuild' => false,
                    'sessionDriver' => 'database',
                    'remoteCallerSessionDriver' => null,
                ],
            ],
            'buildSettings 2' => [
                'method' => 'buildSettings',
                'params' => [
                    'initialImports' => ['mysql' => 'someFile.sql'],
                    'migrations' => false,
                    'seeders' => ['DatabaseSeeder', 'TestSeeder'],
                    'remoteBuildUrl' => null,
                    'isBrowserTest' => true,
                    'isParallelTest' => true,
                    'usingPest' => false,
                    'isRemoteBuild' => false,
                    'sessionDriver' => 'file',
                    'remoteCallerSessionDriver' => 'database',
                ],
            ],
            'buildSettings 3' => [
                'method' => 'buildSettings',
                'params' => [
                    'initialImports' => ['mysql' => 'someFile.sql'],
                    'migrations' => true,
                    'seeders' => ['DatabaseSeeder', 'TestSeeder'],
                    'remoteBuildUrl' => null,
                    'isBrowserTest' => false,
                    'isParallelTest' => false,
                    'usingPest' => true,
                    'isRemoteBuild' => true,
                    'sessionDriver' => 'database',
                    'remoteCallerSessionDriver' => null,
                ],
            ],
            'buildSettings 4' => [
                'method' => 'buildSettings',
                'params' => [
                    'initialImports' => ['mysql' => 'someFile.sql'],
                    'migrations' => '/migrations-path',
                    'seeders' => ['DatabaseSeeder', 'TestSeeder'],
                    'remoteBuildUrl' => null,
                    'isBrowserTest' => true,
                    'isParallelTest' => false,
                    'usingPest' => false,
                    'isRemoteBuild' => false,
                    'sessionDriver' => 'database',
                    'remoteCallerSessionDriver' => null,
                ],
            ],

            'initialImports' => [
                'method' => 'initialImports',
                'params' => ['initialImports' => ['mysql' => 'someFile.sql']],
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
                    'scenarios' => true,
                ],
            ],
            'cacheTools 2' => [
                'method' => 'cacheTools',
                'params' => [
                    'reuseTransaction' => false,
                    'reuseJournal' => false,
                    'verifyDatabase' => true,
                    'scenarios' => true,
                ],
            ],
            'cacheTools 3' => [
                'method' => 'cacheTools',
                'params' => [
                    'reuseTransaction' => true,
                    'reuseJournal' => true,
                    'verifyDatabase' => false,
                    'scenarios' => false,
                ],
            ],
            'cacheTools 4' => [
                'method' => 'cacheTools',
                'params' => [
                    'reuseTransaction' => true,
                    'reuseJournal' => false,
                    'verifyDatabase' => true,
                    'scenarios' => false,
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

            'scenarios' => [
                'method' => 'scenarios',
                'params' => [
                    'scenarios' => true,
                ],
            ],

            'snapshots 1' => [
                'method' => 'snapshots',
                'params' => [
                    'snapshots' => null,
                ],
                'outcome' => [
                    'snapshots' => null,
                    'useSnapshotsWhenReusingDB' => null,
                    'useSnapshotsWhenNotReusingDB' => null,
                ],
            ],
            'snapshots 2' => [
                'method' => 'snapshots',
                'params' => [
                    'snapshots' => false,
                ],
                'outcome' => [
                    'snapshots' => null,
                    'useSnapshotsWhenReusingDB' => null,
                    'useSnapshotsWhenNotReusingDB' => null,
                ],
            ],
            'snapshots 3' => [
                'method' => 'snapshots',
                'params' => [
                    'snapshots' => 'afterMigrations',
                ],
                'outcome' => [
                    'snapshots' => 'afterMigrations',
                    'useSnapshotsWhenReusingDB' => null,
                    'useSnapshotsWhenNotReusingDB' => 'afterMigrations',
                ],
            ],
            'snapshots 4' => [
                'method' => 'snapshots',
                'params' => [
                    'snapshots' => 'afterSeeders',
                ],
                'outcome' => [
                    'snapshots' => 'afterSeeders',
                    'useSnapshotsWhenReusingDB' => null,
                    'useSnapshotsWhenNotReusingDB' => 'afterSeeders',
                ],
            ],
            'snapshots 5' => [
                'method' => 'snapshots',
                'params' => [
                    'snapshots' => 'both',
                ],
                'outcome' => [
                    'snapshots' => 'both',
                    'useSnapshotsWhenReusingDB' => null,
                    'useSnapshotsWhenNotReusingDB' => 'both',
                ],
            ],
            'snapshots 6' => [
                'method' => 'snapshots',
                'params' => [
                    'snapshots' => '!afterMigrations',
                ],
                'outcome' => [
                    'snapshots' => '!afterMigrations',
                    'useSnapshotsWhenReusingDB' => 'afterMigrations',
                    'useSnapshotsWhenNotReusingDB' => 'afterMigrations',
                ],
            ],
            'snapshots 7' => [
                'method' => 'snapshots',
                'params' => [
                    'snapshots' => '!afterSeeders',
                ],
                'outcome' => [
                    'snapshots' => '!afterSeeders',
                    'useSnapshotsWhenReusingDB' => 'afterSeeders',
                    'useSnapshotsWhenNotReusingDB' => 'afterSeeders',
                ],
            ],
            'snapshots 8' => [
                'method' => 'snapshots',
                'params' => [
                    'snapshots' => '!both',
                ],
                'outcome' => [
                    'snapshots' => '!both',
                    'useSnapshotsWhenReusingDB' => 'both',
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
     *
     * @dataProvider configDtoDataProvider
     *
     * @param string       $method  The set method to call.
     * @param mixed[]      $params  The parameters to pass to this set method, and the values to check after.
     * @param mixed[]|null $outcome The outcome values to check for (uses $params if not given).
     * @return void
     */
    #[Test]
    #[DataProvider('configDtoDataProvider')]
    public static function config_dto_can_set_and_get_values(string $method, array $params, $outcome = null)
    {
        $configDTO = new ConfigDTO();

        $callable = [$configDTO, $method];
        if (is_callable($callable)) {
            call_user_func_array($callable, $params);
        }

        $outcome = $outcome ?? $params;
        foreach ($outcome as $name => $value) {
            self::assertSame($value, $configDTO->$name);
        }
    }



    /**
     * Test the ConfigDTO->pickSeedersToInclude() getter.
     *
     * @test
     *
     * @return void
     */
    public static function test_pick_seeders_to_include_getter()
    {
        $seeders = ['DatabaseSeeder', 'TestSeeder'];
        $configDTO = (new ConfigDTO())->seeders($seeders);
        $configDTO->driver = 'sqlite';

        $configDTO->initialImports(['sqlite' => ['some-file.sql']])->migrations(true);
        self::assertSame($seeders, $configDTO->pickSeedersToInclude());

        $configDTO->initialImports(['sqlite' => ['some-file.sql']])->migrations(false);
        self::assertSame($seeders, $configDTO->pickSeedersToInclude());

        $configDTO->initialImports([])->migrations(true);
        self::assertSame($seeders, $configDTO->pickSeedersToInclude());

        $configDTO->initialImports([])->migrations(false);
        self::assertSame([], $configDTO->pickSeedersToInclude());
    }



    /**
     * Provide data for the test_pick_initial_imports_getter test.
     *
     * @return mixed[][]
     */
    public static function pickInitialImportsDataProvider(): array
    {
        return [
            [
                'initialImports' => [
                    'mysql' => ['database/dumps/mysql/my-database.sql'],
                    'sqlite' => ['database/dumps/sqlite/my-database.sqlite'],
                ],
                'driver' => 'mysql',
                'expected' => ['database/dumps/mysql/my-database.sql'],
            ],
            [
                'initialImports' => [
                    'mysql' => ['database/dumps/mysql/my-database.sql'],
                    'sqlite' => ['database/dumps/sqlite/my-database.sqlite'],
                ],
                'driver' => 'sqlite',
                'expected' => ['database/dumps/sqlite/my-database.sqlite'],
            ],
            [
                'initialImports' => [
                    'mysql' => ['database/dumps/mysql/my-database.sql'],
                    'sqlite' => ['database/dumps/sqlite/my-database.sqlite'],
                ],
                'driver' => 'blah',
                'expected' => [],
            ],
            [
                'initialImports' => [
                    'mysql' => 'database/dumps/mysql/my-database.sql',
                    'sqlite' => 'database/dumps/sqlite/my-database.sqlite'
                ],
                'driver' => 'mysql',
                'expected' => ['database/dumps/mysql/my-database.sql'],
            ],
            [
                'initialImports' => [
                    'mysql' => '',
                    'sqlite' => ''
                ],
                'driver' => 'mysql',
                'expected' => [],
            ],
        ];
    }

    /**
     * Test the ConfigDTO->pickInitialImports() getter.
     *
     * @test
     * @dataProvider pickInitialImportsDataProvider
     *
     * @param array<int, string|string[]> $initialImports The initial-imports value (same as what could be put in the
     *                                                    config).
     * @param string                      $driver         The driver to read from.
     * @param mixed                       $expected       The expected output.
     * @return void
     */
    #[Test]
    #[DataProvider('pickInitialImportsDataProvider')]
    public static function test_pick_initial_imports_getter(array $initialImports, string $driver, $expected)
    {
        self::assertSame(
            $expected,
            (new ConfigDTO())->initialImports($initialImports)->driver($driver)->pickInitialImports()
        );
    }



    /**
     * Test ConfigDTO->shouldInitialise().
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_should_initialise()
    {
        self::assertTrue((new ConfigDTO())->connectionExists(true)->shouldInitialise());
        self::assertFalse((new ConfigDTO())->connectionExists(false)->shouldInitialise());
    }



    /**
     * DataProvider for the test_check_that_session_drivers_match test.
     *
     * @return mixed[][]
     */
    public static function sessionDriversDataProvider(): array
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
     *
     * @param boolean     $isRemoteBuild             The isRemoteBuild value.
     * @param boolean     $isBrowserTest             The isBrowserTest value.
     * @param string      $sessionDriver             The sessionDriver value.
     * @param string      $remoteCallerSessionDriver The remoteCallerSessionDriver value.
     * @param string|null $expectException           The expected exception.
     * @return void
     */
    public static function test_check_that_session_drivers_match(
        bool $isRemoteBuild,
        bool $isBrowserTest,
        string $sessionDriver,
        string $remoteCallerSessionDriver,
        $expectException = null
    ) {

        $callback = function() use ($isRemoteBuild, $isBrowserTest, $sessionDriver, $remoteCallerSessionDriver) {
            (new ConfigDTO())
                ->isRemoteBuild($isRemoteBuild)
                ->isBrowserTest($isBrowserTest)
                ->sessionDriver($sessionDriver)
                ->remoteCallerSessionDriver($remoteCallerSessionDriver)
                ->ensureThatSessionDriversMatch();
        };

        self::assertException($callback, $expectException);
    }



    /**
     * Provide data for the test_can_use_transactions test.
     *
     * @return mixed[][]
     */
    public static function databaseCanUseTransactionsDataProvider(): array
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
     *
     * @param boolean $reuseTransaction           The "reuse-transaction" setting.
     * @param boolean $connectionExists           Whether the connection exists or not.
     * @param boolean $isRemoteBuild              Is this process building a db for another Adapt installation?.
     * @param boolean $isBrowserTest              Is this test a browser-test?.
     * @param boolean $dbSupportsTransactions     Whether the database supports transactions or not.
     * @param boolean $expectedCanUseTransactions The expected canUseTransactions() result.
     * @return void
     */
    #[Test]
    #[DataProvider('databaseCanUseTransactionsDataProvider')]
    public static function test_can_use_transactions(
        bool $reuseTransaction,
        bool $connectionExists,
        bool $isRemoteBuild,
        bool $isBrowserTest,
        bool $dbSupportsTransactions,
        bool $expectedCanUseTransactions
    ) {

        $configDTO = (new ConfigDTO())
            ->reuseTransaction($reuseTransaction)
            ->reuseJournal(true)
            ->connectionExists($connectionExists)
            ->isRemoteBuild($isRemoteBuild)
            ->isBrowserTest($isBrowserTest)
            ->dbSupportsReUse($dbSupportsTransactions)
            ->dbSupportsTransactions($dbSupportsTransactions)
            ->dbSupportsJournaling(true);

        self::assertSame($expectedCanUseTransactions, $configDTO->canUseTransactions());
    }



    /**
     * Provide data for the test_can_use_journaling test.
     *
     * @return mixed[][]
     */
    public static function databaseCanUseJournalingDataProvider(): array
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
     *
     * @param boolean $reuseJournal             The "reuse-journal" setting.
     * @param boolean $connectionExists         Whether the connection exists or not.
     * @param boolean $isRemoteBuild            Is this process building a db for another Adapt installation?.
     * @param boolean $isBrowserTest            Is this test a browser-test?.
     * @param boolean $dbSupportsJournaling     Whether the database supports journaling or not.
     * @param boolean $expectedCanUseJournaling The expected canUseJournaling() result.
     * @return void
     */
    #[Test]
    #[DataProvider('databaseCanUseJournalingDataProvider')]
    public static function test_can_use_journaling(
        bool $reuseJournal,
        bool $connectionExists,
        bool $isRemoteBuild,
        bool $isBrowserTest,
        bool $dbSupportsJournaling,
        bool $expectedCanUseJournaling
    ) {

        $configDTO = (new ConfigDTO())
            ->reuseTransaction(true)
            ->reuseJournal($reuseJournal)
            ->connectionExists($connectionExists)
            ->isRemoteBuild($isRemoteBuild)
            ->isBrowserTest($isBrowserTest)
            ->dbSupportsReUse(true)
            ->dbSupportsTransactions(true)
            ->dbSupportsJournaling($dbSupportsJournaling);

        self::assertSame($expectedCanUseJournaling, $configDTO->canUseJournaling());
    }



    /**
     * Provide data for the test_should_use_transactions_or_journaling test.
     *
     * @return mixed[][]
     */
    public static function databaseShouldUseTransactionsOrJournalingDataProvider(): array
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
     *
     * @param boolean $reuseTransaction              The "reuse-transaction" setting.
     * @param boolean $reuseJournal                  The "reuse-journal" setting.
     * @param boolean $expectedShouldUseTransactions The expected canUseTransactions() result.
     * @param boolean $expectedShouldUseJournaling   The expected canUseJournaling() result.
     * @param boolean $expectedReusingDB             The expected reusingDB() result.
     * @return void
     */
    #[Test]
    #[DataProvider('databaseShouldUseTransactionsOrJournalingDataProvider')]
    public static function test_should_use_transactions_or_journaling(
        bool $reuseTransaction,
        bool $reuseJournal,
        bool $expectedShouldUseTransactions,
        bool $expectedShouldUseJournaling,
        bool $expectedReusingDB
    ) {

        $configDTO = (new ConfigDTO())
            ->reuseTransaction($reuseTransaction)
            ->reuseJournal($reuseJournal)
            ->connectionExists(true)
            ->isRemoteBuild(false)
            ->isBrowserTest(false)
            ->dbSupportsReUse(true)
            ->dbSupportsTransactions(true)
            ->dbSupportsJournaling(true);

        self::assertSame($expectedShouldUseTransactions, $configDTO->shouldUseTransaction());
        self::assertSame($expectedShouldUseJournaling, $configDTO->shouldUseJournal());
        self::assertSame($expectedReusingDB, $configDTO->reusingDB());
    }



    /**
     * Provide data for the test_should_verify_database test.
     *
     * @return mixed[][]
     */
    public static function databaseShouldVerifyDatabaseDataProvider(): array
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
     *
     * @param boolean $verifyDatabase               The "verify-database" setting.
     * @param boolean $dbSupportsVerification       Whether the database supports verification or not.
     * @param boolean $shouldVerifyStructure        The expected shouldVerifyStructure() result.
     * @param boolean $shouldVerifyData             The expected shouldVerifyData() result.
     * @param boolean $expectedShouldVerifyDatabase The expected shouldVerifyDatabase() result.
     * @return void
     */
    #[Test]
    #[DataProvider('databaseShouldVerifyDatabaseDataProvider')]
    public static function test_should_verify_database(
        bool $verifyDatabase,
        bool $dbSupportsVerification,
        bool $shouldVerifyStructure,
        bool $shouldVerifyData,
        bool $expectedShouldVerifyDatabase
    ) {

        $configDTO = (new ConfigDTO())
            ->verifyDatabase($verifyDatabase)
            ->dbSupportsVerification($dbSupportsVerification);

        self::assertSame($shouldVerifyStructure, $configDTO->shouldVerifyStructure());
        self::assertSame($shouldVerifyData, $configDTO->shouldVerifyData());
        self::assertSame($expectedShouldVerifyDatabase, $configDTO->shouldVerifyDatabase());
    }



    /**
     * Test ConfigDTO->usingScenarios().
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_using_scenarios()
    {
        self::assertTrue(
            (new ConfigDTO())->scenarios(true)->dbSupportsScenarios(true)->usingScenarios()
        );
        self::assertFalse(
            (new ConfigDTO())->scenarios(true)->dbSupportsScenarios(false)->usingScenarios()
        );
        self::assertFalse(
            (new ConfigDTO())->scenarios(false)->dbSupportsScenarios(true)->usingScenarios()
        );
        self::assertFalse(
            (new ConfigDTO())->scenarios(false)->dbSupportsScenarios(false)->usingScenarios()
        );
    }



    /**
     * Test ConfigDTO->shouldBuildRemotely().
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_should_build_remotely()
    {
        self::assertTrue((new ConfigDTO())->remoteBuildUrl('https://some-host/')->shouldBuildRemotely());
        self::assertFalse((new ConfigDTO())->remoteBuildUrl('')->shouldBuildRemotely());
        self::assertFalse((new ConfigDTO())->remoteBuildUrl(null)->shouldBuildRemotely());
    }



    /**
     * Test ConfigDTO->seedingIsAllowed().
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_seeding_is_allowed()
    {
        self::assertTrue(
            (new ConfigDTO())
                ->initialImports([])
                ->migrations(true)
                ->seedingIsAllowed()
        );
        self::assertTrue(
            (new ConfigDTO())
                ->initialImports([])
                ->migrations('/some/path')
                ->seedingIsAllowed()
        );
        self::assertFalse(
            (new ConfigDTO())
                ->initialImports([])
                ->migrations(false)
                ->seedingIsAllowed()
        );
        self::assertTrue(
            (new ConfigDTO())
                ->initialImports(['sqlite' => ['some-file.sql']])
                ->migrations(true)
                ->seedingIsAllowed()
        );
        self::assertTrue(
            (new ConfigDTO())
                ->initialImports(['sqlite' => ['some-file.sql']])
                ->migrations('/some/path')
                ->seedingIsAllowed()
        );
        self::assertFalse(
            (new ConfigDTO())
                ->initialImports(['sqlite' => ['some-file.sql']])
                ->migrations(false)
                ->seedingIsAllowed()
        );
    }



    /**
     * Test ConfigDTO->snapshotsAreEnabled().
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_snapshots_are_enabled()
    {
        self::assertFalse(
            self::newConfigReusableDB()
                ->snapshots(false)
                ->snapshotsAreEnabled()
        );
        self::assertFalse(
            self::newConfigReusableDB()
                ->snapshots('afterMigrations')
                ->snapshotsAreEnabled()
        );
        self::assertFalse(
            self::newConfigReusableDB()
                ->snapshots('afterSeeders')
                ->snapshotsAreEnabled()
        );
        self::assertFalse(
            self::newConfigReusableDB()
                ->snapshots('both')
                ->snapshotsAreEnabled()
        );
        self::assertTrue(
            self::newConfigReusableDB()
                ->snapshots('!afterMigrations')
                ->snapshotsAreEnabled()
        );
        self::assertTrue(
            self::newConfigReusableDB()
                ->snapshots('!afterSeeders')
                ->snapshotsAreEnabled()
        );
        self::assertTrue(
            self::newConfigReusableDB()
                ->snapshots('!both')
                ->snapshotsAreEnabled()
        );

        self::assertFalse(
            self::newConfigNotReusableDB()
                ->snapshots(false)
                ->snapshotsAreEnabled()
        );
        self::assertTrue(
            self::newConfigNotReusableDB()
                ->snapshots('afterMigrations')
                ->snapshotsAreEnabled()
        );
        self::assertTrue(
            self::newConfigNotReusableDB()
                ->snapshots('afterSeeders')
                ->snapshotsAreEnabled()
        );
        self::assertTrue(
            self::newConfigNotReusableDB()
                ->snapshots('both')
                ->snapshotsAreEnabled()
        );
        self::assertTrue(
            self::newConfigNotReusableDB()
                ->snapshots('!afterMigrations')
                ->snapshotsAreEnabled()
        );
        self::assertTrue(
            self::newConfigNotReusableDB()
                ->snapshots('!afterSeeders')
                ->snapshotsAreEnabled()
        );
        self::assertTrue(
            self::newConfigNotReusableDB()
                ->snapshots('!both')
                ->snapshotsAreEnabled()
        );
    }



    /**
     * Provide data for the test_should_take_snapshot_after_migrations_and_seeders test.
     *
     * @return mixed[][]
     */
    public static function shouldTakeSnapshotsDataProvider(): array
    {
        $possibleReusableDB = [true, false];
        $possibleInitialImports = [['sqlite' => ['some-file.sql']], []];
        $possibleMigrations = [true, false];
        $possibleSeeders = [['DatabaseSeeder'], []];

        $return = [];
        foreach ($possibleReusableDB as $reusableDB) {
            foreach ($possibleInitialImports as $initialImports) {
                foreach ($possibleMigrations as $migrations) {
                    foreach ($possibleSeeders as $seeders) {

                        $return[] = [
                            'reusableDB' => $reusableDB,
                            'initialImports' => $initialImports,
                            'migrations' => $migrations,
                            'seeders' => $seeders,
                            'snapshots' => null,
                            'afterMigrationsExpected' => false,
                            'afterSeedersExpected' => false,
                            'snapshotTypeExpected' => null,
                        ];
                        $return[] = [
                            'reusableDB' => $reusableDB,
                            'initialImports' => $initialImports,
                            'migrations' => $migrations,
                            'seeders' => $seeders,
                            'snapshots' => 'afterMigrations',
                            'afterMigrationsExpected' => !$reusableDB && ($initialImports || $migrations),
                            'afterSeedersExpected' => false,
                            'snapshotTypeExpected' => $reusableDB ? null : 'afterMigrations',
                        ];
                        $return[] = [
                            'reusableDB' => $reusableDB,
                            'initialImports' => $initialImports,
                            'migrations' => $migrations,
                            'seeders' => $seeders,
                            'snapshots' => 'afterSeeders',
                            'afterMigrationsExpected' => !$reusableDB && ($initialImports || $migrations) && !$seeders,
                            'afterSeedersExpected' => !$reusableDB && ($initialImports || $migrations) && $seeders,
                            'snapshotTypeExpected' => $reusableDB ? null : 'afterSeeders',
                        ];
                        $return[] = [
                            'reusableDB' => $reusableDB,
                            'initialImports' => $initialImports,
                            'migrations' => $migrations,
                            'seeders' => $seeders,
                            'snapshots' => 'both',
                            'afterMigrationsExpected' => !$reusableDB && ($initialImports || $migrations),
                            'afterSeedersExpected' => !$reusableDB && ($initialImports || $migrations) && $seeders,
                            'snapshotTypeExpected' => $reusableDB ? null : 'both',
                        ];
                        $return[] = [
                            'reusableDB' => $reusableDB,
                            'initialImports' => $initialImports,
                            'migrations' => $migrations,
                            'seeders' => $seeders,
                            'snapshots' => '!afterMigrations',
                            'afterMigrationsExpected' => $initialImports || $migrations,
                            'afterSeedersExpected' => false,
                            'snapshotTypeExpected' => 'afterMigrations',
                        ];
                        $return[] = [
                            'reusableDB' => $reusableDB,
                            'initialImports' => $initialImports,
                            'migrations' => $migrations,
                            'seeders' => $seeders,
                            'snapshots' => '!afterSeeders',
                            'afterMigrationsExpected' => ($initialImports || $migrations) && !$seeders,
                            'afterSeedersExpected' => ($initialImports || $migrations) && $seeders,
                            'snapshotTypeExpected' => 'afterSeeders',
                        ];
                        $return[] = [
                            'reusableDB' => $reusableDB,
                            'initialImports' => $initialImports,
                            'migrations' => $migrations,
                            'seeders' => $seeders,
                            'snapshots' => '!both',
                            'afterMigrationsExpected' => ($initialImports || $migrations),
                            'afterSeedersExpected' => ($initialImports || $migrations) && $seeders,
                            'snapshotTypeExpected' => 'both',
                        ];
                    }
                }
            }
        }
        return $return;
    }

    /**
     * Test ConfigDTO->shouldTakeSnapshotAfterMigrations().
     *
     * @test
     *
     * @dataProvider shouldTakeSnapshotsDataProvider
     * @param boolean             $reusableDB              Can the database be reused?.
     * @param string[]            $initialImports          The initial-imports to use.
     * @param boolean|string      $migrations              The migrations to run.
     * @param string[]            $seeders                 The seeders to run.
     * @param boolean|null|string $snapshots               The snapshot type to use.
     * @param boolean             $afterMigrationsExpected Whether to take a snapshot after migrations or not.
     * @param boolean             $afterSeedersExpected    Whether to take a snapshot after seeders or not.
     * @param string|null         $snapshotTypeExpected    The type of snapshots to take.
     * @return void
     */
    #[Test]
    #[DataProvider('shouldTakeSnapshotsDataProvider')]
    public static function test_should_take_snapshot_after_migrations_and_seeders(
        bool $reusableDB,
        array $initialImports,
        $migrations,
        array $seeders,
        $snapshots,
        bool $afterMigrationsExpected,
        bool $afterSeedersExpected,
        $snapshotTypeExpected = null
    ) {

        $configDTO = $reusableDB ? self::newConfigReusableDB() : self::newConfigNotReusableDB();
        $configDTO->driver = 'sqlite';

        $configDTO
            ->initialImports($initialImports)
            ->migrations($migrations)
            ->seeders($seeders)
            ->snapshots($snapshots);

        self::assertSame($afterMigrationsExpected, $configDTO->shouldTakeSnapshotAfterMigrations());
        self::assertSame($afterSeedersExpected, $configDTO->shouldTakeSnapshotAfterSeeders());
        self::assertSame($snapshotTypeExpected, $configDTO->snapshotType());
    }





    /**
     * Create a new ConfigDTO where the database COULD be reused.
     *
     * @return ConfigDTO
     */
    private static function newConfigReusableDB(): ConfigDTO
    {
        return (new ConfigDTO())
            ->connectionExists(true)
            ->isRemoteBuild(false)
            ->isBrowserTest(false)
            ->dbSupportsSnapshots(true)
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
    private static function newConfigNotReusableDB(): ConfigDTO
    {
        return (new ConfigDTO())
            ->connectionExists(true)
            ->isRemoteBuild(false)
            ->isBrowserTest(false)
            ->dbSupportsSnapshots(true)
            ->dbSupportsReUse(true)
            ->dbSupportsTransactions(true)
            ->dbSupportsJournaling(true)
            ->reuseTransaction(false)
            ->reuseJournal(false);
    }
}
