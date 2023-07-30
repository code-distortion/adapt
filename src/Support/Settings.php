<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\Adapt\Adapters\LaravelMySQL\LaravelMySQLSnapshot;
use CodeDistortion\Adapt\Adapters\LaravelPostgreSQL\LaravelPostgreSQLSnapshot;
use CodeDistortion\Adapt\DTO\ResolvedSettingsDTO;
use CodeDistortion\Adapt\DTO\VersionsDTO;

/**
 * Common Adapt settings.
 */
class Settings
{
    /** @var string The current package version number. */
    const PACKAGE_VERSION = '0.12.11';

    /** @var string The name of the Adapt config file. */
    const LARAVEL_CONFIG_NAME = 'code_distortion.adapt';

    /** @var string The config file that gets published. */
    const LARAVEL_PUBLISHABLE_CONFIG = '/config/config.publishable.php';

    /** @var string The config file that gets used. */
    const LARAVEL_REAL_CONFIG = '/config/config.real.php';

    /** @var string The .env.testing file to use. */
    const LARAVEL_ENV_TESTING_FILE = '.env.testing';

    /** @var string The test-class method that can be added to define a custom way of building the databases. */
    const LARAVEL_CUSTOM_BUILD_METHOD = 'databaseInit';

    /** @var string The service container name to use when checking to see if Adapt has already initialised the dbs. */
    const LARAVEL_ALREADY_INITIALISED_SERVICE_CONTAINER_NAME = 'adapt-already-initialised';

    /** @var string The prefix common for all Adapt tables. */
    const ADAPT_TABLE_PREFIX = '____adapt_';

    /** @var integer The number of seconds grace-period before stale databases & snapshot files are to be deleted. */
    const DEFAULT_STALE_GRACE_SECONDS = 14400; // 4 hours



    /** @var string The table that contains Adapt's re-use meta-information. */
    const REUSE_TABLE = self::ADAPT_TABLE_PREFIX . '___';

    /** @var string A version representing the way the reuse-table is structured and used. */
    const REUSE_TABLE_VERSION = '7';



    /** @var string The journal table containing details about each table. */
    const VERIFICATION_TABLE = self::ADAPT_TABLE_PREFIX . 'verification____';



    /** @var string The path that browsers connect to initially (when browser testing) so cookies can then be set. */
    const INITIAL_BROWSER_COOKIE_REQUEST_PATH = '/____adapt____/cookie';



    /** @var integer Included when prepping a db remotely between Adapt installations. Mismatch causes an exception. */
    const CONFIG_DTO_VERSION = 7;

    /** @var integer Included in the remote-share payload between Adapt installations. Mismatch causes an exception. */
    const REMOTE_SHARE_DTO_VERSION = 7;

    /** @var string The cookie/http-header used to pass the remote-share date between Adapt installations. */
    const REMOTE_SHARE_KEY = 'adapt-remote-share';

    /** @var string The path used by Adapt when instructing another installation of Adapt to build a database. */
    const REMOTE_BUILD_REQUEST_PATH = '/____adapt____/remote-build';

    /** @var string The name of the singleton that's registered with Laravel, contains the connection database list. */
    const REMOTE_SHARE_CONNECTIONS_SINGLETON_NAME = 'adapt-connection-dbs';

    /** @var string The name used to store the remote-build response in the service container. */
    const SERVICE_CONTAINER_REMOTE_BUILD_RESPONSE = 'adapt-remote-build-response';



    /** @var string The journal table keeping track of which tables have changed. */
    const JOURNAL_CHANGE_TRACKER_TABLE = self::ADAPT_TABLE_PREFIX . 'journal_change_tracker____';

    /** @var string The prefix to use for journal tables. */
    const JOURNAL_TABLE_PREFIX = self::ADAPT_TABLE_PREFIX . 'journal_';

    /** @var string The prefix to use for journal triggers. */
    const JOURNAL_TRIGGER_PREFIX = self::ADAPT_TABLE_PREFIX . 'journal_trigger_';



    /** @var string|null The project root directory (for use when running Adapt tests). */
    private static $projectRootDir;

    /** @var ResolvedSettingsDTO[] The ResolvedSettingsDTOs from recently built databases. */
    private static $resolvedSettingsDTOs = [];

    /** @var VersionsDTO[] The VersionsDTO from recently built databases. */
    private static $resolvedVersionsDTOs = [];

    /** @var string[] Connections whose database have been cleaned up already. */
    private static $purgedConnections = [];

    /** @var boolean Whether the snapshots have been cleaned up yet or not. */
    private static $hasPurgedSnapshots = false;

    /** @var boolean Whether orphaned sharable config files have been removed yet or not. */
    private static $removeOrphanedConfigs = false;



    /**
     * Reset anything that should be reset between internal tests of the Adapt package.
     *
     * @internal
     *
     * @return void
     */
    public static function resetStaticProps()
    {
        self::$resolvedSettingsDTOs = [];
        self::$resolvedVersionsDTOs = [];
        self::$hasPurgedSnapshots = false;
        self::$purgedConnections = [];
        Hasher::resetStaticProps();
        LaravelMySQLSnapshot::resetStaticProps();
        LaravelPostgreSQLSnapshot::resetStaticProps();
    }



    /**
     * Retrieve the project root directory (for use when running Adapt tests).
     *
     * @internal
     *
     * @return string|null
     */
    public static function getProjectRootDir()
    {
        return self::$projectRootDir;
    }

    /**
     * Retrieve the project root directory (for use when running Adapt tests).
     *
     * @internal
     *
     * @param string $dir The project root directory.
     * @return void
     */
    public static function setProjectRootDir($dir)
    {
        self::$projectRootDir = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }



    /**
     * Get a recently resolved ResolvedSettingsDTO.
     *
     * @internal
     *
     * @param string|null $currentScenarioChecksum The scenario-checksum to return for.
     * @return ResolvedSettingsDTO|null
     */
    public static function getResolvedSettingsDTO($currentScenarioChecksum)
    {
        return self::$resolvedSettingsDTOs[$currentScenarioChecksum] ?? null;
    }

    /**
     * Store recently resolved ResolvedSettingsDTO for reference later.
     *
     * @internal
     *
     * @param string|null         $currentScenarioChecksum The scenario-checksum to store this against.
     * @param ResolvedSettingsDTO $resolvedSettingsDTO     A recently resolved ResolvedSettingsDTO.
     * @return void
     */
    public static function storeResolvedSettingsDTO(
        $currentScenarioChecksum,
        $resolvedSettingsDTO
    ) {
        self::$resolvedSettingsDTOs[$currentScenarioChecksum] = $resolvedSettingsDTO;
    }



    /**
     * Get a recently resolved VersionsDTO.
     *
     * @internal
     *
     * @param string      $connection The connection being used.
     * @param string|null $driver     The driver being used.
     * @return ResolvedSettingsDTO|null
     */
    public static function getResolvedVersionsDTO($connection, $driver)
    {
        return self::$resolvedVersionsDTOs[$connection][(string) $driver] ?? null;
    }

    /**
     * Store recently resolved VersionsDTO for reference later.
     *
     * @internal
     *
     * @param string      $connection  The connection being used.
     * @param string|null $driver      The driver being used.
     * @param VersionsDTO $versionsDTO A recently resolved ResolvedSettingsDTO.
     * @return void
     */
    public static function storeResolvedVersionsDTO(
        $connection,
        $driver,
        $versionsDTO
    ) {
        self::$resolvedVersionsDTOs[$connection][(string) $driver] = $versionsDTO;
    }



    /**
     * Check if a connection's databases have been purged.
     *
     * @internal
     *
     * @param string $connection The connection to check.
     * @return boolean
     */
    public static function getHasPurgedConnection($connection): bool
    {
        return in_array($connection, static::$purgedConnections);
    }

    /**
     * Record that a connection's databases have been purged.
     *
     * @internal
     *
     * @param string $connection The connection to record.
     * @return void
     */
    public static function setHasPurgedConnection($connection)
    {
        static::$purgedConnections[] = $connection;
    }

    /**
     * Get the has-purged-snapshots value.
     *
     * @internal
     *
     * @return boolean
     */
    public static function getHasPurgedSnapshots(): bool
    {
        return static::$hasPurgedSnapshots;
    }

    /**
     * Set the has-purged-snapshots value.
     *
     * @internal
     *
     * @return void
     */
    public static function setHasPurgedSnapshots()
    {
        static::$hasPurgedSnapshots = true;
    }

    /**
     * Get the has-removed-orphaned-configs value.
     *
     * @internal
     *
     * @return boolean
     */
    public static function getHasRemovedOrphanedConfigs(): bool
    {
        return static::$removeOrphanedConfigs;
    }

    /**
     * Set the has-removed-orphaned-configs value.
     *
     * @internal
     *
     * @return void
     */
    public static function setHasRemovedOrphanedConfigs()
    {
        static::$removeOrphanedConfigs = true;
    }





    /**
     * Resolve the base storage directory.
     *
     * @internal
     *
     * @param string      $baseDir  The base directory for all stored files.
     * @param string|null $filename An optional filename to add.
     * @return string
     */
    public static function baseStorageDir($baseDir, $filename = null): string
    {
        return self::buildDir($baseDir, $filename);
    }

    /**
     * Resolve the database storage directory.
     *
     * @internal
     *
     * @param string      $baseDir  The base directory for all stored files.
     * @param string|null $filename An optional filename to add.
     * @return string
     */
    public static function databaseDir($baseDir, $filename = null): string
    {
        return self::buildDir("$baseDir/databases", $filename);
    }

    /**
     * Resolve the snapshot storage directory.
     *
     * @internal
     *
     * @param string      $baseDir  The base directory for all stored files.
     * @param string|null $filename An optional filename to add.
     * @return string
     */
    public static function snapshotDir($baseDir, $filename = null): string
    {
        return self::buildDir("$baseDir/snapshots", $filename);
    }

    /**
     * Resolve the share-config storage directory.
     *
     * @internal
     *
     * @param string      $baseDir  The base directory for all stored files.
     * @param string|null $filename An optional filename to add.
     * @return string
     */
    public static function shareConfigDir($baseDir, $filename = null): string
    {
        return self::buildDir("$baseDir/share-configs", $filename);
    }

    /**
     * Append a filename to a directory.
     *
     * @internal
     *
     * @param string      $dir      The directory to use.
     * @param string|null $filename The filename to add (optional).
     * @return string
     */
    private static function buildDir(string $dir, $filename = null): string
    {
        return mb_strlen((string) $filename) ? "$dir/$filename" : "$dir";
    }
}
