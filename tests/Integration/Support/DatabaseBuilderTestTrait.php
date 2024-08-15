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
use CodeDistortion\Adapt\Support\PlatformSupport;
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
    private static $workspaceBaseDir = 'tests/workspaces';

    /** @var string The current workspace directory - used during testing. */
    private static $defaultWSCurrentDir = 'tests/workspaces/current';

    /** @var string  */
    private static $wsCurrentDir = 'tests/workspaces/current';

    /** @var integer A count of the current workspace directories used (for GitHub Actions on Windows). */
    private static $wsCurrentDirCount = 0;

    /** @var string The current workspace config directory. */
    private static $wsConfigDir = 'tests/workspaces/config';



    /**
     * Build a new DIContainer object with defaults set.
     *
     * @param string $connection The connection to build a database for.
     * @return DIContainer
     */
    private static function newDIContainer(string $connection): DIContainer
    {
        return (new DIContainer())
            ->artisan(new LaravelArtisan())
            ->db((new LaravelDB())->useConnection($connection))
            ->log(static::newLog())
            ->exec(new Exec())
            ->filesystem(new Filesystem());
    }

    /**
     * Build a new Log instance.
     *
     * @return LogInterface
     */
    private static function newLog(): LogInterface
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
    private static function newConfigDTO(string $connection): ConfigDTO
    {
        return (new ConfigDTO())
            ->projectName(null)
            ->testName('A test')
            ->connection($connection)
            ->isDefaultConnection(null)
            ->connectionExists(true)
            ->origDatabase('database.sqlite')
//            ->database('test_db')
            ->databaseModifier('')
            ->storageDir(self::wsAdaptStorageDir())
            ->snapshotPrefix('snapshot.')
            ->databasePrefix('test-')
            ->cacheInvalidationEnabled(true)
            ->cacheInvalidationMethod('content')
            ->checksumPaths([
                self::wsFactoriesDir(),
                self::wsMigrationsDir(),
                self::wsInitialImportsDir(),
                self::wsSeedsDir(),
            ])
            ->preCalculatedBuildChecksum(null)
            ->buildSettings(
                [],
                self::wsMigrationsDir(),
                [DatabaseSeeder::class],
                null,
                false,
                false,
                false,
                false,
                'database',
                null
            )
            ->dbAdapterSupport(
                true,
                true,
                true,
                true,
                true,
                true
            )
            ->cacheTools(true, false, false, true)
            ->snapshots('afterMigrations')
            ->forceRebuild(false)
            ->mysqlSettings('mysql', 'mysqldump')
            ->postgresSettings('psql', 'pg_dump');
    }

    /**
     * Update the directories recorded in a ConfigDTO now that they're known.
     *
     * @param ConfigDTO $configDTO The ConfigDTO to update.
     * @return void
     */
    private static function updateConfigDTODirs(ConfigDTO $configDTO)
    {
        $configDTO
            ->storageDir(self::wsAdaptStorageDir())
            ->checksumPaths([
                self::wsFactoriesDir(),
                self::wsMigrationsDir(),
                self::wsInitialImportsDir(),
                self::wsSeedsDir(),
            ]);

        if (is_string($configDTO->migrations)) {
            $configDTO->migrations(self::wsMigrationsDir());
        }
    }

    /**
     * Build a new DatabaseBuilder object.
     *
     * @param ConfigDTO|null   $configDTO The ConfigDTO to use.
     * @param DIContainer|null $di        The DIContainer to use.
     * @return DatabaseBuilder
     */
    private static function newDatabaseBuilder($configDTO = null, $di = null): DatabaseBuilder
    {
        $configDTO = $configDTO ?? self::newConfigDTO('sqlite');
        $di = $di ?? self::newDIContainer($configDTO->connection);

        $pickDriver = function (string $connection) {
            return config("database.connections.$connection.driver", 'unknown');
        };

        return new DatabaseBuilder(
            'laravel',
            $di,
            $configDTO,
            self::newHasher($configDTO, $di),
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
    public static function useConfig($configDTO = null, $di = null)
    {
        // generate a build-checksum based on the current cache_invalidation.locations
        self::newHasher($configDTO, $di)->getBuildChecksum();
    }

    /**
     * Build a new Hasher based on a ConfigDTO and DIContainer.
     *
     * @param ConfigDTO|null   $configDTO The ConfigDTO to use.
     * @param DIContainer|null $di        The DIContainer to use.
     * @return Hasher
     */
    private static function newHasher($configDTO = null, $di = null): Hasher
    {
        $configDTO = $configDTO ?? self::newConfigDTO('sqlite');
        $di = $di ?? self::newDIContainer($configDTO->connection);
        return new Hasher($di, $configDTO);
    }



    /**
     * Prepare the workspace directory by emptying it and copying the contents of another into it.
     *
     * @param string  $sourceDir             The directory to make a copy of.
     * @param boolean $removeAdaptStorageDir Remove the adapt-storage directory?.
     * @return void
     */
    private static function prepareWorkspace(string $sourceDir, bool $removeAdaptStorageDir = true)
    {
        // PDO connections are closed during the __destruct() method,
        // make sure this happens so the sqlite files are closed, and can be deleted
//        gc_collect_cycles();

        self::resolveNewWorkspaceDir();
        $destDir = self::wsCurrentDir();

        self::delTree($destDir);
        self::copyDirRecursive($sourceDir, $destDir);
//        self::fileStringReplace("$destDir/config/code_distortion.adapt.php", 'tests/workspaces/current/', "$destDir/");
        if ($removeAdaptStorageDir) {
            self::delTree("$destDir/database/adapt-test-storage");
        }
        self::createGitIgnoreFile("$destDir/.gitignore");
        self::loadConfigs("$destDir/config");



        StorageDir::ensureStorageDirsExist(self::wsAdaptStorageDir(), new Filesystem(), self::newLog());
    }

    /**
     * Remove the given directory and it's contents.
     *
     * (Recurse through the directory and removes all files and directories).
     *
     * @param string $dir The directory to remove.
     * @return boolean
     */
    private static function delTree(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return false;
        }

        $files = array_filter((array) scandir($dir));
        $files = array_diff($files, ['.', '..']);
        foreach ($files as $file) {
            if (is_dir("$dir/$file")) {
                self::delTree("$dir/$file");
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
    private static function copyDirRecursive(string $sourceDir, string $destDir)
    {
        @mkdir($destDir);
        $files = array_filter((array) scandir($sourceDir));
        $files = array_diff($files, ['.', '..']);
        foreach ($files as $file) {
            if (is_dir("$sourceDir/$file")) {
                self::copyDirRecursive("$sourceDir/$file", "$destDir/$file");
            } else {
                copy("$sourceDir/$file", "$destDir/$file");
            }
        }
    }

    /**
     * Perform a string replace in a file.
     *
     * @param string $path    The path to the file.
     * @param string $search  The string to search for.
     * @param string $replace The string to replace it with.
     * @return void
     */
    private static function fileStringReplace(string $path, string $search, string $replace)
    {
        if (!file_exists($path)) {
            return;
        }

        file_put_contents(
            $path,
            str_replace($search, $replace, file_get_contents($path))
        );
    }

    /**
     * Create a .gitignore file in the given directory to ignore all files.
     *
     * @param string $destPath The location to write the file in.
     * @return boolean
     */
    private static function createGitIgnoreFile(string $destPath): bool
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
    private static function loadConfigs(string $dir)
    {
        foreach (self::pickConfigFiles($dir) as $configName => $path) {
            config([$configName => require($path)]);
        }

        // put the default sqlite database within the workspace
        config(['database.connections.sqlite.database' => self::wsDatabaseDir() . "/database.sqlite"]);
    }

    /**
     * Find the Laravel config files in the given directory.
     *
     * @param string $dir The directory to look in.
     * @return array<string, string>
     */
    private static function pickConfigFiles(string $dir): array
    {
        try {
            $files = array_filter((array) scandir($dir));
            return self::mapConfigPaths($dir, $files);
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
    private static function mapConfigPaths(string $dir, array $files): array
    {
        $return = [];
        foreach ($files as $file) {

            if (!self::isPHPFile("$dir/$file")) {
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
    private static function isPHPFile(string $path): bool
    {
        return ((mb_substr($path, -4) == '.php') && (is_file($path)));
    }

    /**
     * Determine the database driver for the given connection.
     *
     * @param string $connection The connection to grab the database-driver for.
     * @return string|null
     */
    private static function getDBDriver(string $connection)
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
    private static function assertTableList(string $connection, array $expectedTables)
    {
        switch (self::getDBDriver($connection)) {
            case 'mysql':
                throw new Exception('mysql driver not implemented yet');
            case 'sqlite':
                self::assertQueryValues(
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
    private static function assertTableValues(string $connection, ExpectedValuesDTO $expectedValues)
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

        self::assertSame($expectedValues->values, $values);
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
    private static function assertQueryValues(
        string $connection,
        string $query,
        array $values,
        array $expected,
        bool $sort = false
    ) {
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

        self::assertSame($expected, $values);
    }



    /**
     * Resolve which directory to use as the working directory.
     *
     * @return void
     */
    private static function resolveNewWorkspaceDir()
    {
        self::$wsCurrentDir = self::$defaultWSCurrentDir;
        return;
        if (!PlatformSupport::isRunningGitHubActions()) {
            return;
        }
        if (!PlatformSupport::isWindows()) {
            return;
        }

        // Running on Windows in GitHub Actions seems to have a problem where SQLite files aren't closed after use, and
        // can't be deleted when preparing the directory for the next test.
        // e.g.
        // "ErrorException: unlink(tests/workspaces/current/database/adapt-test-storage/databases/test-database.29b88401
        //                  61442c4a3a.sqlite): Resource temporarily unavailable"

        // this avoids this problem by using a new directory for each test

        self::$wsCurrentDirCount++;
        self::$wsCurrentDir = self::$defaultWSCurrentDir . self::$wsCurrentDirCount;
    }

    /**
     * Resolve which directory to use as the working directory.
     *
     * @return string
     */
    private static function wsCurrentDir(): string
    {
        return self::$wsCurrentDir;
    }

    /**
     * Resolve the current workspace adapt-test-storage directory.
     *
     * @return string
     */
    private static function wsAdaptStorageDir(): string
    {
        return self::$wsCurrentDir . '/database/adapt-test-storage';
    }

    /**
     * Resolve the current workspace databases directory.
     *
     * @return string
     */
    private static function wsDatabaseDir(): string
    {
        return self::$wsCurrentDir . '/database/databases';
    }

    /**
     * Resolve the current workspace factories directory.
     *
     * @return string
     */
    private static function wsFactoriesDir(): string
    {
        return self::$wsCurrentDir . '/database/factories';
    }

    /**
     * Resolve the current workspace migrations directory.
     *
     * @return string
     */
    private static function wsMigrationsDir(): string
    {
        return self::$wsCurrentDir . '/database/migrations';
    }

    /**
     * Resolve the current workspace initial-imports directory.
     *
     * @return string
     */
    private static function wsInitialImportsDir(): string
    {
        return self::$wsCurrentDir . '/database/initial-imports';
    }

    /**
     * Resolve the current workspace seeds directory.
     *
     * @return string
     */
    private static function wsSeedsDir(): string
    {
        return self::$wsCurrentDir . '/database/seeds';
    }
}
