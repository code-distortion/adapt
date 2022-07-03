<?php

namespace CodeDistortion\Adapt\Tests\Integration\Laravel\DatabaseBuilder;

use CodeDistortion\Adapt\DI\Injectable\Laravel\Filesystem;
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
                    ->snapshots(null)
                    ->seeders([DatabaseSeeder::class]),
                'expectedSnapshots' => [],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],

            'Takes snapshot after migrations' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('!afterMigrations')
                    ->seeders([DatabaseSeeder::class]),
                'expectedSnapshots' => [
                    'snapshots/snapshot.database.2881d7-0320bdd00911.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],
            'Takes snapshot after seeders' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('!afterSeeders')
                    ->seeders([DatabaseSeeder::class]),
                'expectedSnapshots' => [
                    'snapshots/snapshot.database.2881d7-059d0b188354.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],
            'Takes snapshot after migrations and seeders' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('!both')
                    ->seeders([DatabaseSeeder::class]),
                'expectedSnapshots' => [
                    'snapshots/snapshot.database.2881d7-0320bdd00911.sqlite',
                    'snapshots/snapshot.database.2881d7-059d0b188354.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],

            'Takes snapshot after migrations (no seeders)' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('!afterMigrations')
                    ->seeders([]),
                'expectedSnapshots' => [
                    'snapshots/snapshot.database.2881d7-0320bdd00911.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],
            'Takes snapshot after seeders (no seeders)' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('!afterSeeders')
                    ->seeders([]),
                'expectedSnapshots' => [
                    'snapshots/snapshot.database.2881d7-0320bdd00911.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],
            'Takes snapshot after migrations and seeders (no seeders)' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('!both')
                    ->seeders([]),
                'expectedSnapshots' => [
                    'snapshots/snapshot.database.2881d7-0320bdd00911.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],

            'Takes snapshot after migrations - with initial-import' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('!afterMigrations')
                    ->initialImports(['sqlite' => $this->wsInitialImportsDir . '/initial-import-1.sqlite'])
                    ->seeders([DatabaseSeeder::class]),
                'expectedSnapshots' => [
                    'snapshots/snapshot.database.2881d7-7ac0d1aebe0b.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],
            'Takes snapshot after seeders - with initial-import' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('!afterSeeders')
                    ->initialImports(['sqlite' => $this->wsInitialImportsDir . '/initial-import-1.sqlite'])
                    ->seeders([DatabaseSeeder::class]),
                'expectedSnapshots' => [
                    'snapshots/snapshot.database.2881d7-ab99c82ee102.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],
            'Takes snapshot after migrations and seeders - with initial-import' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('!both')
                    ->initialImports(['sqlite' => $this->wsInitialImportsDir . '/initial-import-1.sqlite'])
                    ->seeders([DatabaseSeeder::class]),
                'expectedSnapshots' => [
                    'snapshots/snapshot.database.2881d7-7ac0d1aebe0b.sqlite',
                    'snapshots/snapshot.database.2881d7-ab99c82ee102.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],

            'Takes snapshot after migrations (no seeders) - with initial-import' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('!afterMigrations')
                    ->initialImports(['sqlite' => $this->wsInitialImportsDir . '/initial-import-1.sqlite'])
                    ->seeders([]),
                'expectedSnapshots' => [
                    'snapshots/snapshot.database.2881d7-7ac0d1aebe0b.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],
            'Takes snapshot after seeders (no seeders) - with initial-import' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('!afterSeeders')
                    ->initialImports(['sqlite' => $this->wsInitialImportsDir . '/initial-import-1.sqlite'])
                    ->seeders([]),
                'expectedSnapshots' => [
                    'snapshots/snapshot.database.2881d7-7ac0d1aebe0b.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],
            'Takes snapshot after migrations and seeders (no seeders) - with initial-import' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('!both')
                    ->initialImports(['sqlite' => $this->wsInitialImportsDir . '/initial-import-1.sqlite'])
                    ->seeders([]),
                'expectedSnapshots' => [
                    'snapshots/snapshot.database.2881d7-7ac0d1aebe0b.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],

            'Imports before seeder snapshot - Takes snapshot before and after migrations and seeders' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('!both')
                    ->seeders([DatabaseSeeder::class]),
                'expectedSnapshots' => [
                    'snapshots/snapshot.database.2881d7-0320bdd00911.sqlite',
                    'snapshots/snapshot.database.2881d7-059d0b188354.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => false,
                'expectUsers' => ['imported-snapshot-after-seeders'],
            ],
            'Imports after seeder snapshot - Takes snapshot before and after migrations and seeders' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('!both')
                    ->seeders([UserSeeder::class]),
                'expectedSnapshots' => [
                    'snapshots/snapshot.database.2881d7-0320bdd00911.sqlite',
                    'snapshots/snapshot.database.2881d7-059d0b188354.sqlite',
                    'snapshots/snapshot.database.2881d7-a9b9d5e75e62.sqlite',
                ],
                'expectedDatabase' => null,
                'removeAdaptStorageDir' => false,
                'expectUsers' => ['imported-snapshot-before-seeders'],
            ],

            'Using database-modifier - Takes snapshot before and after migrations and seeders' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->databaseModifier('1')
                    ->snapshots('!both')
                    ->seeders([DatabaseSeeder::class]),
                'expectedSnapshots' => [
                    'snapshots/snapshot.database.2881d7-059d0b188354.sqlite',
                    'snapshots/snapshot.database.2881d7-0320bdd00911.sqlite',
                ],
                'expectedDatabase' => 'databases/test-database.2881d7-0161442c4a3a-1.sqlite',
                'removeAdaptStorageDir' => true,
                'expectUsers' => [],
            ],

            'Using database-modifier - Imports before seeder snapshot - Takes snapshot before and after migrations and seeders' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->databaseModifier('1')
                    ->snapshots('!both')
                    ->seeders([DatabaseSeeder::class]),
                'expectedSnapshots' => [
                    'snapshots/snapshot.database.2881d7-059d0b188354.sqlite',
                    'snapshots/snapshot.database.2881d7-0320bdd00911.sqlite',
                ],
                'expectedDatabase' => 'databases/test-database.2881d7-0161442c4a3a-1.sqlite',
                'removeAdaptStorageDir' => false,
                'expectUsers' => ['imported-snapshot-after-seeders'],
            ],
            'Using database-modifier - Imports after seeder snapshot - Takes snapshot before and after migrations and seeders' => [
                'config' => $this->newConfigDTO('sqlite')
                    ->databaseModifier('1')
                    ->snapshots('!both')
                    ->seeders([UserSeeder::class]),
                'expectedSnapshots' => [
                    'snapshots/snapshot.database.2881d7-0320bdd00911.sqlite',
                    'snapshots/snapshot.database.2881d7-059d0b188354.sqlite',
                    'snapshots/snapshot.database.2881d7-a9b9d5e75e62.sqlite',
                ],
                'expectedDatabase' => 'databases/test-database.2881d7-6b584cd41132-1.sqlite',
                'removeAdaptStorageDir' => false,
                'expectUsers' => ['imported-snapshot-before-seeders'],
            ],
        ];
    }

    /**
     * Test that the DatabaseBuilder takes snapshots.
     *
     * @test
     * @dataProvider snapshotDataProvider
     * @param ConfigDTO   $configDTO             The ConfigDTO to use which instructs what and how to build.
     * @param string[]    $expectedSnapshots     The snapshot files expected to exist.
     * @param string|null $expectedDatabase      The database that's expected to exist (is ignored when null).
     * @param boolean     $removeAdaptStorageDir Remove the adapt-storage directory from the scenario?.
     * @param string[]    $expectUsers           The list of users to expect in the database.
     * @return void
     */
    public function test_database_builder_takes_snapshots(
        ConfigDTO $configDTO,
        array $expectedSnapshots,
        ?string $expectedDatabase,
        bool $removeAdaptStorageDir,
        array $expectUsers
    ): void {

        $this->prepareWorkspace("$this->workspaceBaseDir/scenario1", $this->wsCurrentDir, $removeAdaptStorageDir);

        // build the database
        $this->newDatabaseBuilder($configDTO)->execute();

        // determine the list of expected paths
        $expectedPaths = $expectedDatabase
            ? array_merge($expectedSnapshots, [$expectedDatabase])
            : $expectedSnapshots;
        sort($expectedPaths);

        // find all the files that exist
        $filesystem = new Filesystem();
        $paths = $filesystem->filesInDir($this->wsAdaptStorageDir, true);
        foreach ($paths as $index => $path) {
            $paths[$index] = $filesystem->removeBasePath($path, $this->wsAdaptStorageDir);
        }

        // remove the current database if we're not checking for it
        if (!$expectedDatabase) {
            $dbPath = config('database.connections.sqlite.database');
            $dbFile = $filesystem->removeBasePath($dbPath, $this->wsAdaptStorageDir);
            $paths = array_diff($paths, [$dbFile]);
        }

        // remove other files that should be ignored
        $ignorePaths = ['.gitignore'];
        foreach ($paths as $index => $path) {
            if (in_array($path, $ignorePaths)) {
                unset($paths[$index]);
            }
        }
        sort($paths);
        $paths = array_values($paths);

        $this->assertSame($expectedPaths, $paths);

        // check if the 'imported-snapshot' user is present
        foreach ($expectUsers as $user) {
            $db = DB::connection($configDTO->connection);
            $row = $db->select("SELECT COUNT(*) AS total FROM `users` WHERE username = :user", ['user' => $user]);
            $this->assertSame(1, (int) $row[0]->total);
        }
    }

    /**
     * Build the two snapshot files that go into tests/workspaces/scenario1/database/adapt-test-storage
     *
     * Steps:
     * - delete the snapshot.database.x.sqlite files from tests/workspaces/scenario1/database/adapt-test-storage/
     *   - rm tests/workspaces/scenario1/database/adapt-test-storage/snapshots/*.sqlite
     * - run this test (below)
     *   - ./vendor/bin/phpunit --filter=test_build_snapshot_sqlite_databases
     * - copy the tests/workspaces/current/database/adapt-test-storage/snapshot.database.x.sqlite files to dir above.
     *   - cp -p tests/workspaces/current/database/adapt-test-storage/snapshots/snapshot.database.*.sqlite tests/workspaces/scenario1/database/adapt-test-storage/snapshots/
     */
//    public function test_build_snapshot_sqlite_databases()
//    {
//        $this->prepareWorkspace("$this->workspaceBaseDir/scenario1", $this->wsCurrentDir, true);
//
//        $configDTO = $this->newConfigDTO('sqlite')
//            ->snapshots('both', 'both')
//            ->seeders([DatabaseSeeder::class]);
//
//        // build the database
//        $this->newDatabaseBuilder($configDTO)->execute();
//
//
//
//        // find the snapshot files
//        $dir = "$this->wsAdaptStorageDir/snapshots";
//        $snapshotFiles = collect((array) scandir($dir))->filter(function ($path) use ($configDTO) {
//            return preg_match('/^'.preg_quote($configDTO->snapshotPrefix).'/', $path);
//        });
//
//        foreach ($snapshotFiles as $snapshotFile) {
//
//            DB::connection($configDTO->connection)->disconnect();
//            $key = 'database.connections.' . $configDTO->connection . '.database';
//            config([$key => "$this->wsAdaptStorageDir/snapshots/$snapshotFile"]);
//
//            $rows = DB::connection($configDTO->connection)->select("SELECT COUNT(*) AS total FROM `users`");
//            $userCount = $rows[0]->total;
//
//            DB::connection($configDTO->connection)->insert(
//                "INSERT INTO `users` (`username`) VALUES (:username)",
//                ['username' => $userCount ? 'imported-snapshot-after-seeders' : 'imported-snapshot-before-seeders']
//            );
//        }
//    }
}
