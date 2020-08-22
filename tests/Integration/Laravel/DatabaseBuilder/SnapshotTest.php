<?php

namespace CodeDistortion\Adapt\Tests\Integration\Laravel\DatabaseBuilder;

use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Tests\Database\Seeders\DatabaseSeeder;
use CodeDistortion\Adapt\Tests\Integration\Support\AssignClassAlias;
use CodeDistortion\Adapt\Tests\Integration\Support\DatabaseBuilderTestTrait;
use CodeDistortion\Adapt\Tests\LaravelTestCase;
use DB;

AssignClassAlias::databaseBuilderSetUpTrait(__NAMESPACE__);

/**
 * Test that the DatabaseBuilder class creates snapshots properly.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class SnapshotTest extends LaravelTestCase
{
    use DatabaseBuilderSetUpTrait;
    use DatabaseBuilderTestTrait;


    /**
     * Provide data for the test_database_builder_takes_snapshots test.
     *
     * @return mixed[][]
     */
    public function snapshotDataProvider(): array
    {
        return [
            'Snapshots disabled' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(false, true, true)
                    ->seeders([DatabaseSeeder::class]),
                'expectedFiles' => [],
            ],

            'Takes snapshot after migrations' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(true, true, false)
                    ->seeders([DatabaseSeeder::class]),
                'expectedFiles' => [
                    'snapshot.database.3dd190cf729cf1fc-a34cd538e35f9b7d.sqlite',
                ],
            ],
            'Takes snapshot after seeders' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(true, false, true)
                    ->seeders([DatabaseSeeder::class]),
                'expectedFiles' => [
                    'snapshot.database.3dd190cf729cf1fc-8bd51f9f0b21313b.sqlite',
                ],
            ],
            'Takes snapshot after migrations and seeders' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(true, true, true)
                    ->seeders([DatabaseSeeder::class]),
                'expectedFiles' => [
                    'snapshot.database.3dd190cf729cf1fc-8bd51f9f0b21313b.sqlite',
                    'snapshot.database.3dd190cf729cf1fc-a34cd538e35f9b7d.sqlite',
                ],
            ],

            'Takes snapshot after migrations (no seeders)' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(true, true, false)
                    ->seeders([]),
                'expectedFiles' => [
                    'snapshot.database.3dd190cf729cf1fc-a34cd538e35f9b7d.sqlite',
                ],
            ],
            'Takes snapshot after seeders (no seeders)' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(true, false, false)
                    ->seeders([]),
                'expectedFiles' => [],
            ],
            'Takes snapshot after migrations and seeders (no seeders)' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(true, true, true)
                    ->seeders([]),
                'expectedFiles' => [
                    'snapshot.database.3dd190cf729cf1fc-a34cd538e35f9b7d.sqlite',
                ],
            ],

            'Takes snapshot after migrations - with pre-migration-import' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(true, true, false)
                    ->preMigrationImports(['sqlite' => $this->wsPreMigrationsDir.'/pre-migration-import-1.sqlite'])
                    ->seeders([DatabaseSeeder::class]),
                'expectedFiles' => [
                    'snapshot.database.3dd190cf729cf1fc-abda224253072c22.sqlite',
                ],
            ],
            'Takes snapshot after seeders - with pre-migration-import' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(true, false, true)
                    ->preMigrationImports(['sqlite' => $this->wsPreMigrationsDir.'/pre-migration-import-1.sqlite'])
                    ->seeders([DatabaseSeeder::class]),
                'expectedFiles' => [
                    'snapshot.database.3dd190cf729cf1fc-0a77482526651b75.sqlite',
                ],
            ],
            'Takes snapshot after migrations and seeders - with pre-migration-import' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(true, true, true)
                    ->preMigrationImports(['sqlite' => $this->wsPreMigrationsDir.'/pre-migration-import-1.sqlite'])
                    ->seeders([DatabaseSeeder::class]),
                'expectedFiles' => [
                    'snapshot.database.3dd190cf729cf1fc-0a77482526651b75.sqlite',
                    'snapshot.database.3dd190cf729cf1fc-abda224253072c22.sqlite',
                ],
            ],

            'Takes snapshot after migrations (no seeders) - with pre-migration-import' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(true, true, false)
                    ->preMigrationImports(['sqlite' => $this->wsPreMigrationsDir.'/pre-migration-import-1.sqlite'])
                    ->seeders([]),
                'expectedFiles' => [
                    'snapshot.database.3dd190cf729cf1fc-abda224253072c22.sqlite',
                ],
            ],
            'Takes snapshot after seeders (no seeders) - with pre-migration-import' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(true, false, false)
                    ->preMigrationImports(['sqlite' => $this->wsPreMigrationsDir.'/pre-migration-import-1.sqlite'])
                    ->seeders([]),
                'expectedFiles' => [],
            ],
            'Takes snapshot after migrations and seeders (no seeders) - with pre-migration-import' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(true, true, true)
                    ->preMigrationImports(['sqlite' => $this->wsPreMigrationsDir.'/pre-migration-import-1.sqlite'])
                    ->seeders([]),
                'expectedFiles' => [
                    'snapshot.database.3dd190cf729cf1fc-abda224253072c22.sqlite',
                ],
            ],
        ];
    }

    /**
     * Test that the DatabaseBuilder takes a snapshot after migrations.
     *
     * @test
     * @dataProvider snapshotDataProvider
     * @param ConfigDTO $config        The ConfigDTO to use which instructs what and how to build.
     * @param array     $expectedFiles The files expected to exist.
     * @return void
     */
    public function test_database_builder_takes_snapshots(ConfigDTO $config, array $expectedFiles): void
    {
        $this->prepareWorkspace("$this->workspaceBaseDir/scenario1", $this->wsCurrentDir);

        // build the database
        $this->newDatabaseBuilder($config)->execute();

        // get db name
        $dbPath = config('database.connections.sqlite.database');
        $temp = explode('/', $dbPath);
        $dbFile = array_pop($temp);

        // find out which other files exist
        $files = array_values(array_diff(
            scandir($this->wsAdaptStorageDir),
            ['.', '..', '.gitignore', $dbFile]
        ));

        $this->assertSame($expectedFiles, $files);
    }
}
