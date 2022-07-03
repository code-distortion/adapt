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
    public const PACKAGE_VERSION = '0.12.0';

    /** @var string The name of the Adapt config file. */
    public const LARAVEL_CONFIG_NAME = 'code_distortion.adapt';

    /** @var string The config file that gets published. */
    public const LARAVEL_PUBLISHABLE_CONFIG = '/config/config.publishable.php';

    /** @var string The config file that gets used. */
    public const LARAVEL_REAL_CONFIG = '/config/config.real.php';

    /** @var string The .env.testing file to use. */
    public const LARAVEL_ENV_TESTING_FILE = '.env.testing';

    /** @var string The test-class method that can be added to define a custom way of building the databases. */
    public const LARAVEL_CUSTOM_BUILD_METHOD = 'databaseInit';

    /** @var string The prefix common for all Adapt tables. */
    public const ADAPT_TABLE_PREFIX = '____adapt_';

    /** @var integer The number of seconds grace-period before stale databases & snapshot files are to be deleted. */
    public const DEFAULT_STALE_GRACE_SECONDS = 14400; // 4 hours



    /** @var string The table that contains Adapt's re-use meta-information. */
    public const REUSE_TABLE = self::ADAPT_TABLE_PREFIX . '___';

    /** @var string A version representing the way the reuse-table is structured and used. */
    public const REUSE_TABLE_VERSION = '7';



    /** @var string The journal table containing details about each table. */
    public const VERIFICATION_TABLE = self::ADAPT_TABLE_PREFIX . 'verification____';



    /** @var string The path that browsers connect to initially (when browser testing) so cookies can then be set. */
    public const INITIAL_BROWSER_COOKIE_REQUEST_PATH = '/____adapt____/cookie';



    /** @var integer Included when prepping a db remotely between Adapt installations. Mismatch causes an exception. */
    public const CONFIG_DTO_VERSION = 7;

    /** @var integer Included in the remote-share payload between Adapt installations. Mismatch causes an exception. */
    public const REMOTE_SHARE_DTO_VERSION = 7;

    /** @var string The cookie/http-header used to pass the remote-share date between Adapt installations. */
    public const REMOTE_SHARE_KEY = 'adapt-remote-share';

    /** @var string The path used by Adapt when instructing another installation of Adapt to build a database. */
    public const REMOTE_BUILD_REQUEST_PATH = '/____adapt____/remote-build';

    /** @var string The name of the singleton that's registered with Laravel, contains the connection database list. */
    public const REMOTE_SHARE_CONNECTIONS_SINGLETON_NAME = 'adapt-connection-dbs';

    /** @var string The name used to store the remote-build response in the service container. */
    public const SERVICE_CONTAINER_REMOTE_BUILD_RESPONSE = 'adapt-remote-build-response';



    /** @var string The journal table keeping track of which tables have changed. */
    public const JOURNAL_CHANGE_TRACKER_TABLE = self::ADAPT_TABLE_PREFIX . 'journal_change_tracker____';

    /** @var string The prefix to use for journal tables. */
    public const JOURNAL_TABLE_PREFIX = self::ADAPT_TABLE_PREFIX . 'journal_';

    /** @var string The prefix to use for journal triggers. */
    public const JOURNAL_TRIGGER_PREFIX = self::ADAPT_TABLE_PREFIX . 'journal_trigger_';



    /** @var ResolvedSettingsDTO[] The ResolvedSettingsDTOs from recently built databases. */
    public static array $resolvedSettingsDTOs = [];

    /** @var VersionsDTO[] The VersionsDTO from recently built databases. */
    public static array $resolvedVersionsDTOs = [];

    /** @var string[] Connections whose database have been cleaned up already. */
    public static array $purgedConnections = [];

    /** @var boolean Whether the snapshots have been cleaned up yet or not. */
    public static bool $hasPurgedSnapshots = false;

    /** @var boolean Whether orphaned sharable config files have been removed yet or not. */
    public static bool $removeOrphanedConfigs = false;



    /**
     * Reset anything that should be reset between internal tests of the Adapt package.
     *
     * @return void
     */
    public static function resetStaticProps(): void
    {
        Settings::$resolvedSettingsDTOs = [];
        Settings::$resolvedVersionsDTOs = [];
        Settings::$hasPurgedSnapshots = false;
        Settings::$purgedConnections = [];
        Hasher::resetStaticProps();
        LaravelMySQLSnapshot::resetStaticProps();
        LaravelPostgreSQLSnapshot::resetStaticProps();
    }



    /**
     * Get a recently resolved ResolvedSettingsDTO.
     *
     * @param string|null $currentScenarioChecksum The scenario-checksum to return for.
     * @return ResolvedSettingsDTO|null
     */
    public static function getResolvedSettingsDTO(?string $currentScenarioChecksum): ?ResolvedSettingsDTO
    {
        return Settings::$resolvedSettingsDTOs[$currentScenarioChecksum] ?? null;
    }

    /**
     * Store recently resolved ResolvedSettingsDTO for reference later.
     *
     * @param string|null         $currentScenarioChecksum The scenario-checksum to store this against.
     * @param ResolvedSettingsDTO $resolvedSettingsDTO     A recently resolved ResolvedSettingsDTO.
     * @return void
     */
    public static function storeResolvedSettingsDTO(
        ?string $currentScenarioChecksum,
        ResolvedSettingsDTO $resolvedSettingsDTO
    ): void {
        Settings::$resolvedSettingsDTOs[$currentScenarioChecksum] = $resolvedSettingsDTO;
    }



    /**
     * Get a recently resolved VersionsDTO.
     *
     * @param string      $connection The connection being used.
     * @param string|null $driver     The driver being used.
     * @return ResolvedSettingsDTO|null
     */
    public static function getResolvedVersionsDTO(string $connection, ?string $driver): ?VersionsDTO
    {
        return Settings::$resolvedVersionsDTOs[$connection][(string) $driver] ?? null;
    }

    /**
     * Store recently resolved VersionsDTO for reference later.
     *
     * @param string      $connection  The connection being used.
     * @param string|null $driver      The driver being used.
     * @param VersionsDTO $versionsDTO A recently resolved ResolvedSettingsDTO.
     * @return void
     */
    public static function storeResolvedVersionsDTO(
        string $connection,
        ?string $driver,
        VersionsDTO $versionsDTO
    ): void {
        Settings::$resolvedVersionsDTOs[$connection][(string) $driver] = $versionsDTO;
    }



    /**
     * Check if a connection's databases have been purged.
     *
     * @param string $connection The connection to check.
     * @return boolean
     */
    public static function getHasPurgedConnection(string $connection): bool
    {
        return in_array($connection, static::$purgedConnections);
    }

    /**
     * Record that a connection's databases have been purged.
     *
     * @param string $connection The connection to record.
     * @return void
     */
    public static function setHasPurgedConnection(string $connection): void
    {
        static::$purgedConnections[] = $connection;
    }

    /**
     * Get the has-purged-snapshots value.
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
     * @return void
     */
    public static function setHasPurgedSnapshots(): void
    {
        static::$hasPurgedSnapshots = true;
    }

    /**
     * Get the has-removed-orphaned-configs value.
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
     * @return void
     */
    public static function setHasRemovedOrphanedConfigs(): void
    {
        static::$removeOrphanedConfigs = true;
    }





    /**
     * Resolve the base storage directory.
     *
     * @param string      $baseDir  The base directory for all stored files.
     * @param string|null $filename An optional filename to add.
     * @return string
     */
    public static function baseStorageDir(string $baseDir, ?string $filename = null): string
    {
        return self::buildDir($baseDir, $filename);
    }

    /**
     * Resolve the database storage directory.
     *
     * @param string      $baseDir  The base directory for all stored files.
     * @param string|null $filename An optional filename to add.
     * @return string
     */
    public static function databaseDir(string $baseDir, ?string $filename = null): string
    {
        return self::buildDir("$baseDir/databases", $filename);
    }

    /**
     * Resolve the snapshot storage directory.
     *
     * @param string      $baseDir  The base directory for all stored files.
     * @param string|null $filename An optional filename to add.
     * @return string
     */
    public static function snapshotDir(string $baseDir, ?string $filename = null): string
    {
        return self::buildDir("$baseDir/snapshots", $filename);
    }

    /**
     * Resolve the share-config storage directory.
     *
     * @param string      $baseDir  The base directory for all stored files.
     * @param string|null $filename An optional filename to add.
     * @return string
     */
    public static function shareConfigDir(string $baseDir, ?string $filename = null): string
    {
        return self::buildDir("$baseDir/share-configs", $filename);
    }

    /**
     * Append a filename to a directory.
     *
     * @param string      $dir      The directory to use.
     * @param string|null $filename The filename to add (optional).
     * @return string
     */
    private static function buildDir(string $dir, ?string $filename = null): string
    {
        return mb_strlen((string) $filename) ? "$dir/$filename" : "$dir";
    }
}
