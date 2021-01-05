<?php

namespace CodeDistortion\Adapt\Tests\Unit\DTO;

use App;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Tests\PHPUnitTestCase;

/**
 * Test the ConfigDTO class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class ConfigDTOTest extends PHPUnitTestCase
{
    /**
     * Provide data for the config_dto_can_set_and_get_values test.
     *
     * @return mixed[][]
     */
    public function configDtoDataProvider(): array
    {
        return [
            'projectName' => [
                'method' => 'projectName',
                'params' => ['projectName' => 'my-project'],
            ],
            'connection' => [
                'method' => 'connection',
                'params' => ['connection' => 'mysql'],
            ],
            'driver' => [
                'method' => 'driver',
                'params' => ['driver' => 'mysql'],
            ],
            'database' => [
                'method' => 'database',
                'params' => ['database' => 'my_database'],
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
            'hashPaths' => [
                'method' => 'hashPaths',
                'params' => ['hashPaths' => ['/someplace1', '/someplace2']],
            ],

            'buildSettings 1' => [
                'method' => 'buildSettings',
                'params' => [
                    'preMigrationImports' => ['mysql' => 'someFile.sql'],
                    'migrations' => true,
                    'seeders' => ['DatabaseSeeder', 'TestSeeder'],
                    'isBrowserTest' => true,
                ],
            ],
            'buildSettings 2' => [
                'method' => 'buildSettings',
                'params' => [
                    'preMigrationImports' => ['mysql' => 'someFile.sql'],
                    'migrations' => false,
                    'seeders' => ['DatabaseSeeder', 'TestSeeder'],
                    'isBrowserTest' => true,
                ],
            ],
            'buildSettings 3' => [
                'method' => 'buildSettings',
                'params' => [
                    'preMigrationImports' => ['mysql' => 'someFile.sql'],
                    'migrations' => true,
                    'seeders' => ['DatabaseSeeder', 'TestSeeder'],
                    'isBrowserTest' => false,
                ],
            ],
            'buildSettings 4' => [
                'method' => 'buildSettings',
                'params' => [
                    'preMigrationImports' => ['mysql' => 'someFile.sql'],
                    'migrations' => '/migrations-path',
                    'seeders' => ['DatabaseSeeder', 'TestSeeder'],
                    'isBrowserTest' => true,
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
            'isBrowserTest 1' => [
                'method' => 'isBrowserTest',
                'params' => ['isBrowserTest' => true],
            ],
            'isBrowserTest 2' => [
                'method' => 'isBrowserTest',
                'params' => ['isBrowserTest' => false],
            ],

            'cacheTools 1' => [
                'method' => 'cacheTools',
                'params' => [
                    'reuseTestDBs' => true,
                    'dynamicTestDBs' => true,
                    'transactions' => true,
                ],
            ],
            'cacheTools 2' => [
                'method' => 'cacheTools',
                'params' => [
                    'reuseTestDBs' => false,
                    'dynamicTestDBs' => true,
                    'transactions' => true,
                ],
            ],
            'cacheTools 3' => [
                'method' => 'cacheTools',
                'params' => [
                    'reuseTestDBs' => true,
                    'dynamicTestDBs' => false,
                    'transactions' => true,
                ],
            ],
            'cacheTools 4' => [
                'method' => 'cacheTools',
                'params' => [
                    'reuseTestDBs' => true,
                    'dynamicTestDBs' => true,
                    'transactions' => false,
                ],
            ],

            'reuseTestDBs' => [
                'method' => 'reuseTestDBs',
                'params' => [
                    'reuseTestDBs' => true,
                ],
            ],
            'dynamicTestDBs' => [
                'method' => 'dynamicTestDBs',
                'params' => [
                    'dynamicTestDBs' => true,
                ],
            ],
            'transactions' => [
                'method' => 'transactions',
                'params' => [
                    'transactions' => true,
                ],
            ],

            'snapshots 1' => [
                'method' => 'snapshots',
                'params' => [
                    'snapshotsEnabled' => true,
                    'takeSnapshotAfterMigrations' => true,
                    'takeSnapshotAfterSeeders' => true,
                ],
            ],
            'snapshots 2' => [
                'method' => 'snapshots',
                'params' => [
                    'snapshotsEnabled' => false,
                    'takeSnapshotAfterMigrations' => true,
                    'takeSnapshotAfterSeeders' => true,
                ],
            ],
            'snapshots 3' => [
                'method' => 'snapshots',
                'params' => [
                    'snapshotsEnabled' => true,
                    'takeSnapshotAfterMigrations' => false,
                    'takeSnapshotAfterSeeders' => true,
                ],
            ],
            'snapshots 4' => [
                'method' => 'snapshots',
                'params' => [
                    'snapshotsEnabled' => true,
                    'takeSnapshotAfterMigrations' => true,
                    'takeSnapshotAfterSeeders' => false,
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
     * @param array  $preMigrationImports The pre-migration-imports value (same as what could be put in the config).
     * @param string $driver              The driver to read from.
     * @param mixed  $expected            The expected output.
     * @return void
     */
    public function test_pick_pre_migration_dumps_getter(array $preMigrationImports, string $driver, $expected): void
    {
        $this->assertSame(
            $expected,
            (new ConfigDTO())->preMigrationImports($preMigrationImports)->driver($driver)->pickPreMigrationDumps()
        );
    }
}
