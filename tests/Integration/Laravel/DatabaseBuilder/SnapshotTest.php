<?php

namespace CodeDistortion\Adapt\Tests\Integration\Laravel\DatabaseBuilder;

use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Tests\Database\Seeders\DatabaseSeeder;
use CodeDistortion\Adapt\Tests\Database\Seeders\UserSeeder;
use CodeDistortion\Adapt\Tests\Integration\Support\AssignClassAlias;
use CodeDistortion\Adapt\Tests\Integration\Support\DatabaseBuilderTestTrait;
use CodeDistortion\Adapt\Tests\LaravelTestCase;
use Illuminate\Support\Facades\DB;

AssignClassAlias::databaseBuilderSetUpTrait(__NAMESPACE__);

/**
 * Test that the DatabaseBuilder class creates snapshots properly.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class SnapshotTest extends LaravelTestCase
{
    use DatabaseBuilderSetUpTrait; // this is chosen above by AssignClassAlias depending on the version of Laravel used
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
                    ->snapshots(false, false)
                    ->seeders([DatabaseSeeder::class]),
                'expectedSnapshots' => [],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],

            'Takes snapshot after migrations' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('afterMigrations', false)
                    ->seeders([DatabaseSeeder::class]),
                'expectedSnapshots' => [
                    'snapshot.database.338349-a34cd538e35f.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],
            'Takes snapshot after seeders' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('afterSeeders', false)
                    ->seeders([DatabaseSeeder::class]),
                'expectedSnapshots' => [
                    'snapshot.database.338349-8bd51f9f0b21.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],
            'Takes snapshot after migrations and seeders' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('both', false)
                    ->seeders([DatabaseSeeder::class]),
                'expectedSnapshots' => [
                    'snapshot.database.338349-8bd51f9f0b21.sqlite',
                    'snapshot.database.338349-a34cd538e35f.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],

            'Takes snapshot after migrations (no seeders)' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('afterMigrations', false)
                    ->seeders([]),
                'expectedSnapshots' => [
                    'snapshot.database.338349-a34cd538e35f.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],
            'Takes snapshot after seeders (no seeders)' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('afterSeeders', false)
                    ->seeders([]),
                'expectedSnapshots' => [
                    'snapshot.database.338349-a34cd538e35f.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],
            'Takes snapshot after migrations and seeders (no seeders)' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('both', false)
                    ->seeders([]),
                'expectedSnapshots' => [
                    'snapshot.database.338349-a34cd538e35f.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],

            'Takes snapshot after migrations - with pre-migration-import' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('afterMigrations', false)
                    ->preMigrationImports(['sqlite' => $this->wsPreMigrationsDir . '/pre-migration-import-1.sqlite'])
                    ->seeders([DatabaseSeeder::class]),
                'expectedSnapshots' => [
                    'snapshot.database.338349-abda22425307.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],
            'Takes snapshot after seeders - with pre-migration-import' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('afterSeeders', false)
                    ->preMigrationImports(['sqlite' => $this->wsPreMigrationsDir . '/pre-migration-import-1.sqlite'])
                    ->seeders([DatabaseSeeder::class]),
                'expectedSnapshots' => [
                    'snapshot.database.338349-0a7748252665.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],
            'Takes snapshot after migrations and seeders - with pre-migration-import' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('both', false)
                    ->preMigrationImports(['sqlite' => $this->wsPreMigrationsDir . '/pre-migration-import-1.sqlite'])
                    ->seeders([DatabaseSeeder::class]),
                'expectedSnapshots' => [
                    'snapshot.database.338349-0a7748252665.sqlite',
                    'snapshot.database.338349-abda22425307.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],

            'Takes snapshot after migrations (no seeders) - with pre-migration-import' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('afterMigrations', false)
                    ->preMigrationImports(['sqlite' => $this->wsPreMigrationsDir . '/pre-migration-import-1.sqlite'])
                    ->seeders([]),
                'expectedSnapshots' => [
                    'snapshot.database.338349-abda22425307.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],
            'Takes snapshot after seeders (no seeders) - with pre-migration-import' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('afterSeeders', false)
                    ->preMigrationImports(['sqlite' => $this->wsPreMigrationsDir . '/pre-migration-import-1.sqlite'])
                    ->seeders([]),
                'expectedSnapshots' => [
                    'snapshot.database.338349-abda22425307.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],
            'Takes snapshot after migrations and seeders (no seeders) - with pre-migration-import' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('both', false)
                    ->preMigrationImports(['sqlite' => $this->wsPreMigrationsDir . '/pre-migration-import-1.sqlite'])
                    ->seeders([]),
                'expectedSnapshots' => [
                    'snapshot.database.338349-abda22425307.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],

            'Imports before seeder snapshot - Takes snapshot before and after migrations and seeders' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('both', false)
                    ->seeders([DatabaseSeeder::class]),
                'expectedSnapshots' => [
                    'snapshot.database.338349-8bd51f9f0b21.sqlite',
                    'snapshot.database.338349-a34cd538e35f.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => false,
                'expectUsers' => ['imported-snapshot-after-seeders'],
            ],
            'Imports after seeder snapshot - Takes snapshot before and after migrations and seeders' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('both', false)
                    ->seeders([UserSeeder::class]),
                'expectedSnapshots' => [
                    'snapshot.database.338349-5f8175d30493.sqlite',
                    'snapshot.database.338349-8bd51f9f0b21.sqlite',
                    'snapshot.database.338349-a34cd538e35f.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => false,
                'expectUsers' => ['imported-snapshot-before-seeders'],
            ],

            'Using database-modifier - Takes snapshot before and after migrations and seeders' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->databaseModifier('1')
                    ->snapshots('both', false)
                    ->seeders([DatabaseSeeder::class]),
                'expectedSnapshots' => [
                    'snapshot.database.338349-8bd51f9f0b21.sqlite',
                    'snapshot.database.338349-a34cd538e35f.sqlite',
                ],
                'expectedDatabase' => 'test-database.338349-e2dee1963369-1.sqlite',
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],

            'Using database-modifier - Imports before seeder snapshot - Takes snapshot before and after migrations and seeders' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->databaseModifier('1')
                    ->snapshots('both', false)
                    ->seeders([DatabaseSeeder::class]),
                'expectedSnapshots' => [
                    'snapshot.database.338349-8bd51f9f0b21.sqlite',
                    'snapshot.database.338349-a34cd538e35f.sqlite',
                ],
                'expectedDatabase' => 'test-database.338349-e2dee1963369-1.sqlite',
                'removeAdaptStorageDir' => false,
                'expectUsers' => ['imported-snapshot-after-seeders'],
            ],
            'Using database-modifier - Imports after seeder snapshot - Takes snapshot before and after migrations and seeders' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->databaseModifier('1')
                    ->snapshots('both', false)
                    ->seeders([UserSeeder::class]),
                'expectedSnapshots' => [
                    'snapshot.database.338349-5f8175d30493.sqlite',
                    'snapshot.database.338349-8bd51f9f0b21.sqlite',
                    'snapshot.database.338349-a34cd538e35f.sqlite',
                ],
                'expectedDatabase' => 'test-database.338349-82c5cbc33fdd-1.sqlite',
                'removeAdaptStorageDir' => false,
                'expectUsers' => ['imported-snapshot-before-seeders'],
            ],
        ];
    }

    /**
     * Test that the DatabaseBuilder takes a snapshot after migrations.
     *
     * @test
     * @dataProvider snapshotDataProvider
     * @param ConfigDTO   $config                The ConfigDTO to use which instructs what and how to build.
     * @param string[]    $expectedSnapshots     The snapshot files expected to exist.
     * @param string|null $expectedDatabase      The database that's expected to exist (ignored when null).
     * @param boolean     $removeAdaptStorageDir Remove the adapt-storage directory from the scenario?.
     * @param string[]    $expectUsers           The list of users to expect in the database.
     * @return void
     */
    public function test_database_builder_takes_snapshots(
        ConfigDTO $config,
        array $expectedSnapshots,
        ?string $expectedDatabase,
        bool $removeAdaptStorageDir,
        array $expectUsers
    ): void {

        $this->prepareWorkspace("$this->workspaceBaseDir/scenario1", $this->wsCurrentDir, $removeAdaptStorageDir);

        // build the database
        $this->newDatabaseBuilder($config)->execute();

        // look for the current database in the list, or ignore it
        $dbFile = null;
        if ($expectedDatabase) {
            $expectedFiles = array_merge($expectedSnapshots, [$expectedDatabase]);
            sort($expectedFiles);
        } else {
            $dbPath = config('database.connections.sqlite.database');
            $temp = explode('/', $dbPath);
            $dbFile = array_pop($temp);

            $expectedFiles = $expectedSnapshots;
        }

        // find out which other files exist
        $files = array_values(array_diff(
            array_filter((array) scandir($this->wsAdaptStorageDir)),
            ['.', '..', '.gitignore', $dbFile]
        ));
        $this->assertSame($expectedFiles, $files);

        // check if the 'imported-snapshot' user is present
        foreach ($expectUsers as $user) {
            $row = DB::connection($config->connection)
                ->select("SELECT COUNT(*) AS total FROM `users` WHERE username = :user", ['user' => $user]);
            $this->assertSame(1, (int) $row[0]->total);
        }
    }

    /**
     * Build the two snapshot files that go into tests/workspaces/scenario1/database/adapt-test-storage
     *
     * Steps:
     * - delete the snapshot.database.x.sqlite files from tests/workspaces/scenario1/database/adapt-test-storage/
     * - run this test
     * - copy the tests/workspaces/current/database/adapt-test-storage/snapshot.database.x.sqlite files to dir above.
     */
/*
    public function test_build_snapshot_sqlite_databases()
    {
        $this->prepareWorkspace("$this->workspaceBaseDir/scenario1", $this->wsCurrentDir, true);

        $config = $this->newConfigDTO('sqlite')
            ->snapshots(true, true, true)
            ->seeders([DatabaseSeeder::class]);

        // build the database
        $this->newDatabaseBuilder($config)->execute();



        // find the snapshot files
        $snapshotFiles = collect((array) scandir($this->wsAdaptStorageDir))->filter(function ($path) use ($config) {
            return preg_match('/^'.preg_quote($config->snapshotPrefix).'/', $path);
        });

        foreach ($snapshotFiles as $snapshotFile) {

            DB::connection($config->connection)->disconnect();
            config([
                'database.connections.' . $config->connection . '.database' => "$this->wsAdaptStorageDir/$snapshotFile"
            ]);

            $userCount = DB::connection($config->connection)->select("SELECT COUNT(*) AS total FROM `users`")[0]->total;

            DB::connection($config->connection)->insert(
                "INSERT INTO `users` (`username`) VALUES (:username)",
                ['username' => $userCount ? 'imported-snapshot-after-seeders' : 'imported-snapshot-before-seeders']
            );
        }
    }
*/
}
