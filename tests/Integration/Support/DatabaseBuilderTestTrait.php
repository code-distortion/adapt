<?php

namespace CodeDistortion\Adapt\Tests\Integration\Support;

use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DI\Injectable\Interfaces\LogInterface;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Exec;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Filesystem;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelArtisan;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelDB;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Support\Hasher;
use CodeDistortion\Adapt\Support\LaravelSupport;
use CodeDistortion\Adapt\Support\StorageDir;
use CodeDistortion\Adapt\Tests\Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use ErrorException;
use Exception;

/**
 * Contains methods to set up a /database directory structure for testing and create a DatabaseBuilder.
 */
trait DatabaseBuilderTestTrait
{
    /** @var string The directory containing the test-workspaces. */
    private string $workspaceBaseDir = 'tests/workspaces';

    /** @var string The current workspace directory - used during testing. */
    private string $wsCurrentDir = 'tests/workspaces/current';

    /** @var string The current workspace config directory. */
    private string $wsConfigDir = 'tests/workspaces/config';

    /** @var string The current workspace adapt-test-storage directory. */
    private string $wsAdaptStorageDir = 'tests/workspaces/current/database/adapt-test-storage';

    /** @var string The current workspace databases directory. */
    private string $wsDatabaseDir = 'tests/workspaces/current/database/databases';

    /** @var string The current workspace factories directory. */
    private string $wsFactoriesDir = 'tests/workspaces/current/database/factories';

    /** @var string The current workspace migrations directory. */
    private string $wsMigrationsDir = 'tests/workspaces/current/database/migrations';

    /** @var string The current workspace initial-imports directory. */
    private string $wsInitialImportsDir = 'tests/workspaces/current/database/initial-imports';

    /** @var string The current workspace seeds directory. */
    private string $wsSeedsDir = 'tests/workspaces/current/database/seeds';


    /**
     * Build a new DIContainer object with defaults set.
     *
     * @param string $connection The connection to build a database for.
     * @return DIContainer
     */
    private function newDIContainer(string $connection): DIContainer
    {
        return (new DIContainer())
            ->artisan(new LaravelArtisan())
            ->db((new LaravelDB())->useConnection($connection))
            ->log($this->newLog())
            ->exec(new Exec())
            ->filesystem(new Filesystem());
    }

    /**
     * Build a new Log instance.
     *
     * @return LogInterface
     */
    private function newLog(): LogInterface
    {
        return LaravelSupport::newLaravelLogger(false, false, 0);
//        return LaravelSupport::newLaravelLogger(true, false, 2);
    }

    /**
     * Build a new ConfigDTO object with defaults set.
     *
     * @param string $connection The connection to build a database for.
     * @return ConfigDTO
     */
    private function newConfigDTO(string $connection): ConfigDTO
    {
        return (new ConfigDTO())
            ->projectName(null)
            ->testName('A test')
            ->connection($connection)
            ->connectionExists(true)
            ->origDatabase('database.sqlite')
//            ->database('test_db')
            ->databaseModifier('')
            ->storageDir($this->wsAdaptStorageDir)
            ->snapshotPrefix('snapshot.')
            ->databasePrefix('test-')
            ->cacheInvalidationEnabled(true)
            ->cacheInvalidationMethod('content')
            ->checksumPaths([
                $this->wsFactoriesDir,
                $this->wsMigrationsDir,
                $this->wsInitialImportsDir,
                $this->wsSeedsDir,
            ])
            ->preCalculatedBuildChecksum(null)
            ->buildSettings(
                [],
                $this->wsMigrationsDir,
                [DatabaseSeeder::class],
                null,
                false,
                false,
                false,
                false,
                'database',
                null,
            )
            ->dbAdapterSupport(
                true,
                true,
                true,
                true,
                true,
                true,
            )
            ->cacheTools(true, false, false, true)
            ->snapshots(false, 'afterMigrations')
            ->forceRebuild(false)
            ->mysqlSettings('mysql', 'mysqldump')
            ->postgresSettings('psql', 'pg_dump');
    }

    /**
     * Build a new DatabaseBuilder object.
     *
     * @param ConfigDTO|null   $configDTO The ConfigDTO to use.
     * @param DIContainer|null $di        The DIContainer to use.
     * @return DatabaseBuilder
     */
    private function newDatabaseBuilder(?ConfigDTO $configDTO = null, ?DIContainer $di = null): DatabaseBuilder
    {
        $configDTO ??= $this->newConfigDTO('sqlite');
        $di ??= $this->newDIContainer($configDTO->connection);

        $pickDriver = function (string $connection) {
            return config("database.connections.$connection.driver", 'unknown');
        };

        return new DatabaseBuilder(
            'laravel',
            $di,
            $configDTO,
            $this->newHasher($configDTO, $di),
            $pickDriver
        );
    }

    /**
     * Use a config as the environment.
     *
     * @param ConfigDTO|null   $configDTO The ConfigDTO to use.
     * @param DIContainer|null $di        The DIContainer to use.
     * @return void
     */
    public function useConfig(?ConfigDTO $configDTO = null, ?DIContainer $di = null): void
    {
        // generate a build-checksum based on the current cache_invalidation.locations
        $this->newHasher($configDTO, $di)->getBuildChecksum();
    }

    /**
     * Build a new Hasher based on a ConfigDTO and DIContainer.
     *
     * @param ConfigDTO|null   $configDTO The ConfigDTO to use.
     * @param DIContainer|null $di        The DIContainer to use.
     * @return Hasher
     */
    private function newHasher(?ConfigDTO $configDTO = null, ?DIContainer $di = null): Hasher
    {
        $configDTO ??= $this->newConfigDTO('sqlite');
        $di ??= $this->newDIContainer($configDTO->connection);
        return new Hasher($di, $configDTO);
    }


    /**
     * Prepare the workspace directory by emptying it and copying the contents of another into it.
     *
     * @param string  $sourceDir             The directory to make a copy of.
     * @param string  $destDir               The directory to replace.
     * @param boolean $removeAdaptStorageDir Remove the adapt-storage directory?.
     * @return void
     */
    private function prepareWorkspace(string $sourceDir, string $destDir, bool $removeAdaptStorageDir = true): void
    {
        $this->delTree($destDir);
        $this->copyDirRecursive($sourceDir, $destDir);
        if ($removeAdaptStorageDir) {
            $this->delTree($destDir . '/database/adapt-test-storage');
        }
        $this->createGitIgnoreFile($destDir . '/.gitignore');
        $this->loadConfigs($destDir . '/config');

        StorageDir::ensureStorageDirsExist($this->wsAdaptStorageDir, new Filesystem(), $this->newLog());
    }

    /**
     * Remove the given directory and it's contents.
     *
     * @param string $dir The directory to remove.
     * @return boolean
     */
    private function delTree(string $dir): bool
    {
        $files = array_filter((array) scandir($dir));
        $files = array_diff($files, ['.', '..']);
        foreach ($files as $file) {
            if (is_dir("$dir/$file")) {
                $this->delTree("$dir/$file");
            } else {
                unlink("$dir/$file");
            }
        }
        return rmdir($dir);
    }

    /**
     * Recursively copy a directory.
     *
     * @param string $sourceDir The directory to read from.
     * @param string $destDir   The directory to write to (will be created if it doesn't exist).
     * @return void
     */
    private function copyDirRecursive(string $sourceDir, string $destDir): void
    {
        @mkdir($destDir);
        $files = array_filter((array) scandir($sourceDir));
        $files = array_diff($files, ['.', '..']);
        foreach ($files as $file) {
            if (is_dir("$sourceDir/$file")) {
                $this->copyDirRecursive("$sourceDir/$file", "$destDir/$file");
            } else {
                copy("$sourceDir/$file", "$destDir/$file");
            }
        }
    }

    /**
     * Create a .gitignore file in the given directory to ignore all files.
     *
     * @param string $destPath The location to write the file in.
     * @return boolean
     */
    private function createGitIgnoreFile(string $destPath): bool
    {
        $fp = fopen($destPath, 'w');
        if (!$fp) {
            return false;
        }

        fwrite($fp, '*' . PHP_EOL);
        fwrite($fp, '!.gitignore' . PHP_EOL);
        fclose($fp);
        return true;
    }


    /**
     * Load the laravel config settings from the files in $dir.
     *
     * @param string $dir The directory to look for config files in.
     * @return void
     */
    private function loadConfigs(string $dir): void
    {
        foreach ($this->pickConfigFiles($dir) as $configName => $path) {
            config([$configName => require($path)]);
        }

        // put the default sqlite database within the workspace
        config(['database.connections.sqlite.database' => "$this->wsDatabaseDir/database.sqlite"]);
    }

    /**
     * Find the Laravel config files in the given directory.
     *
     * @param string $dir The directory to look in.
     * @return array<string, string>
     */
    private function pickConfigFiles(string $dir): array
    {
        try {
            $files = array_filter((array) scandir($dir));
            return $this->mapConfigPaths($dir, $files);
        } catch (ErrorException $e) {
            return [];
        }
    }

    /**
     * Take the list of files and create an assoc array config-name.path.
     *
     * @param string   $dir   The directory the files are in.
     * @param string[] $files The files in the directory.
     * @return array<string, string>
     */
    private function mapConfigPaths(string $dir, array $files): array
    {
        $return = [];
        foreach ($files as $file) {

            if (!$this->isPHPFile("$dir/$file")) {
                continue;
            }

            $configName = mb_substr($file, 0, -4);
            $return[$configName] = "$dir/$file";
        }
        return $return;
    }

    /**
     * Check if the given path is a php file.
     *
     * @param string $path The path to check.
     * @return boolean
     */
    private function isPHPFile(string $path): bool
    {
        return ((mb_substr($path, -4) == '.php') && (is_file($path)));
    }

    /**
     * Determine the database driver for the given connection.
     *
     * @param string $connection The connection to grab the database-driver for.
     * @return string|null
     */
    private function getDBDriver(string $connection): ?string
    {
        $return = config("database.connections.$connection.driver", 'unknown');
        return is_string($return) || is_null($return) ? $return : null; // phpstan
    }

    /**
     * Check that the existing tables match an expected list.
     *
     * @param string   $connection     The connection to check on.
     * @param string[] $expectedTables The expected tables.
     * @return void
     * @throws Exception When an unknown database driver is found.
     */
    private function assertTableList(string $connection, array $expectedTables): void
    {
        switch ($this->getDBDriver($connection)) {
            case 'mysql':
                throw new Exception('mysql driver not implemented yet');
            case 'sqlite':
                $this->assertQueryValues(
                    $connection,
                    "SELECT name FROM sqlite_master WHERE type='table'",
                    [],
                    $expectedTables,
                    true
                );
                break;
            default:
                throw new Exception('Unknown database driver');
        }
    }


    /**
     * Check that the values of a particular field in a table match the expected values.
     *
     * @param string            $connection     The connection to query on.
     * @param ExpectedValuesDTO $expectedValues The expected values.
     * @return void
     */
    private function assertTableValues(string $connection, ExpectedValuesDTO $expectedValues): void
    {
        $escFields = "`" . implode('`, `', $expectedValues->fields) . "`";
        $rows = DB::connection($connection)->select("SELECT " . $escFields . " FROM `" . $expectedValues->table . "`");

        $values = collect($rows)->map(function ($row) use ($expectedValues) {
            $return = [];
            foreach ($expectedValues->fields as $field) {
                $return[] = $row->$field;
            }
            return $return;
        })->toArray();

        $this->assertSame($expectedValues->values, $values);
    }

    /**
     * Check that the values of a particular field in a table match the expected values.
     *
     * @param string  $connection The connection to query on.
     * @param string  $query      The query to run.
     * @param mixed[] $values     The values to use in the query.
     * @param mixed[] $expected   The expected values.
     * @param boolean $sort       Sort the values before comparing?.
     * @return void
     */
    private function assertQueryValues(
        string $connection,
        string $query,
        array $values,
        array $expected,
        bool $sort = false
    ): void {
        $rows = DB::connection($connection)->select($query, $values);

        $values = [];
        if (count($rows)) {
            $fieldNames = array_keys((array) $rows[0]);
            $firstField = (string) reset($fieldNames);
            $values = collect($rows)->pluck($firstField)->toArray();
        }
        if ($sort) {
            sort($values);
            sort($expected);
        }

        $this->assertSame($expected, $values);
    }
}
