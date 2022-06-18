<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\Adapt\DI\Injectable\Interfaces\LogInterface;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelLog;
use CodeDistortion\Adapt\DTO\RemoteShareDTO;
use CodeDistortion\Adapt\Exceptions\AdaptRemoteShareException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDOException;

/**
 * Provides extra miscellaneous Laravel related support functionality.
 */
class LaravelSupport
{
    /**
     * Test to see if this code is running within an orchestra/testbench TestCase.
     *
     * @return boolean
     */
    public static function isRunningInOrchestra(): bool
    {
        $basePath = base_path();
        $realpath = (string) realpath('.');
        if (mb_strpos($basePath, $realpath) === 0) {
            $rest = mb_substr($basePath, mb_strlen($realpath));
            return (mb_substr($rest, 0, mb_strlen('/vendor/orchestra/')) == '/vendor/orchestra/');
        }
        return false;
    }

    /**
     * Re-load Laravel's entire config using the .env.testing file.
     *
     * @return void
     */
    public static function useTestingConfig()
    {
        LaravelEnv::reloadEnv(
            base_path(Settings::LARAVEL_ENV_TESTING_FILE),
            ['APP_ENV' => 'testing']
        );

        LaravelConfig::reloadConfig();
    }

    /**
     * Tell Laravel to use the desired databases for particular connections.
     *
     * Override the connection's existing databases.
     *
     * @param array<string,string> $connectionDatabases The connections' databases.
     * @return void
     */
    public static function useConnectionDatabases($connectionDatabases)
    {
        foreach ($connectionDatabases as $connection => $database) {
            if (!is_null(config("database.connections.$connection.database"))) {
                config(["database.connections.$connection.database" => $database]);
            }
        }
    }

    /**
     * Disconnect from databases that already have a connection.
     *
     * @param LogInterface $log The object to log with.
     * @return void
     */
    public static function disconnectFromConnectedDatabases($log)
    {
        $alreadyConnected = array_keys(DB::getConnections());
        foreach ($alreadyConnected as $connection) {
            $log->vDebug("Disconnecting established database connection \"$connection\"");
            DB::disconnect($connection);
        }
    }

    /**
     * make sure the code is running from the Laravel base dir.
     *
     * e.g. /var/www/html instead of /var/www/html/public
     *
     * This ensures that the paths of checksum files (migrations, seeders etc) are resolved identically, compared to
     * when Adapt is running in non-web situations tests (i.e. tests).
     *
     * @return void
     */
    public static function runFromBasePathDir()
    {
        chdir(base_path());
    }

    /**
     * Get the storage directory.
     *
     * @return string
     */
    public static function storageDir(): string
    {
        $c = Settings::LARAVEL_CONFIG_NAME;
        $return = config("$c.storage_dir");
        $return = is_string($return) ? $return : '';
        return rtrim($return, '\\/');
    }

    /**
     * Get a value from Laravel's config, and make sure it's a string.
     *
     * @param string $key     The config key to get.
     * @param string $default The default value.
     * @return string
     */
    public static function configString($key, $default = ''): string
    {
        $value = config($key, $default);
        if (is_string($value)) {
            return $value;
        }
        if (is_int($value)) {
            return (string) $value;
        }
        if (is_bool($value)) {
            return (string) $value;
        }
        return '';
    }

    /**
     * Get a value from Laravel's config, and make sure it's an array.
     *
     * @param string  $key     The config key to get.
     * @param mixed[] $default The default value.
     * @return mixed[]
     */
    public static function configArray($key, $default = []): array
    {
        $value = config($key, $default);
        if (is_array($value)) {
            return $value;
        }
        return [];
    }

    /**
     * Build a new LaravelLog object, uses the config settings as default values.
     *
     * @param boolean|null $stdout    Display messages to stdout?.
     * @param boolean|null $laravel   Add messages to Laravel's standard log.
     * @param integer|null $verbosity The current verbosity level - output at this level or lower will be displayed.
     * @return LaravelLog
     */
    public static function newLaravelLogger($stdout = null, $laravel = null, $verbosity = null): LaravelLog
    {
        $config = config(Settings::LARAVEL_CONFIG_NAME);
        return new LaravelLog((bool) ($stdout ?? $config['log']['stdout'] ?? false), (bool) ($laravel ?? $config['log']['laravel'] ?? false), (int) ($verbosity ?? $config['log']['verbosity'] ?? 0));
    }

    /**
     * Look at the seeder properties and config value, and determine what the seeders should be.
     *
     * Adapt uses $seeders, Laravel uses $seed and $seeder. This allows Adapt to respect the Laravel settings.
     *
     * @param boolean $hasSeedersProp Whether the test has the $seeders property or not.
     * @param mixed   $seedersProp    The $seeders property.
     * @param boolean $hasSeederProp  Whether the test has the $seeder property or not.
     * @param mixed   $seederProp     The $seeder property.
     * @param boolean $hasSeedProp    Whether the test has the $seed property or not.
     * @param mixed   $seedProp       The $seed property.
     * @param mixed   $seedersConfig  The code_distortion.adapt.seeders Laravel config value.
     * @return string[]
     */
    public static function resolveSeeders(
        $hasSeedersProp,
        $seedersProp,
        $hasSeederProp,
        $seederProp,
        $hasSeedProp,
        $seedProp,
        $seedersConfig
    ): array {

        // use the $seeders property first if it exists
        if ($hasSeedersProp) {
            $seeders = $seedersProp;
        // when $seed is truthy:
        // $seeder will be used (if present), and will fall back to the default DatabaseSeeder otherwise
        } elseif ($hasSeedProp) {
            $seeders = [];
            if ($seedProp) {
                $seeders = $hasSeederProp ? $seederProp : 'Database\\Seeders\\DatabaseSeeder';
            }
        // fall back to the config seeders
        } else {
            $seeders = $seedersConfig;
        }
        $seeders = is_string($seeders) ? [$seeders] : $seeders;
        return is_array($seeders) ? $seeders : [];
    }

    /**
     * Register a scoped value with Laravel's service container.
     *
     * @param string   $name     The name of the scoped value.
     * @param callable $callback The callback to run to populate the value.
     * @return void
     */
    public static function registerScoped($name, $callback)
    {
        /** @var Application $app */
        $app = app();
        method_exists($app, 'scoped')
            ? $app->scoped($name, $callback)
            : $app->singleton($name, $callback);
    }

    /**
     * Read the list of connections that have been prepared, and their corresponding databases from the framework.
     *
     * @return mixed[]|null
     */
    public static function readPreparedConnectionDBsFromFramework()
    {
        try {
            $return = app(Settings::REMOTE_SHARE_CONNECTIONS_SINGLETON_NAME);
            return is_array($return) || is_null($return) ? $return : null;
        } catch (BindingResolutionException $e) {
            return null;
        }
    }





    /**
     * Build a RemoteShareDTO from the header or cookie in a Request.
     *
     * @param Request $request The request to look in.
     * @return RemoteShareDTO|null
     * @throws AdaptRemoteShareException When the RemoteShareDTO version doesn't match.
     */
    public static function buildRemoteShareDTOFromRequest($request)
    {
        $shareHeaderValue = self::readHeaderValue($request, Settings::REMOTE_SHARE_KEY);
        $shareCookieValue = self::readCookieValue($request, Settings::REMOTE_SHARE_KEY);

        return RemoteShareDTO::buildFromPayload($shareHeaderValue)
            ?? RemoteShareDTO::buildFromPayload($shareCookieValue)
            ?? null;
    }

    /**
     * Read a cookie's raw value from the request.
     *
     * @param Request $request    The request to look in.
     * @param string  $cookieName The cookie to look for.
     * @return string
     */
    public static function readCookieValue($request, $cookieName): string
    {
        $value = $request->cookie($cookieName);
        return is_string($value) ? $value : '';
    }

    /**
     * Read a header's raw value from the request.
     *
     * @param Request $request    The request object.
     * @param string  $headerName The name of the header to read.
     * @return string
     */
    private static function readHeaderValue(Request $request, string $headerName): string
    {
        $value = $request->headers->get($headerName);
        return is_string($value) ? $value : '';
    }



    /**
     * Start a database transaction for a connection.
     *
     * (ADAPTED FROM Laravel Framework's RefreshDatabase::beginDatabaseTransaction()).
     *
     * @param string $connection The connection to use.
     * @return void
     */
    public static function startTransaction($connection)
    {
        $connection = self::getConnectionInterface($connection);
        if (self::useEventDispatcher($connection)) {
            /** @var Connection $connection */
            $dispatcher = $connection->getEventDispatcher();
            $connection->unsetEventDispatcher();
            $connection->beginTransaction();
            $connection->setEventDispatcher($dispatcher);
        } else {
            // compatible with older versions of Laravel
            $connection->beginTransaction();
        }
    }

    /**
     * Rollback the database transaction for a connection.
     *
     * (ADAPTED FROM Laravel Framework's RefreshDatabase::beginDatabaseTransaction()).
     *
     * @param string $connection The connection to use.
     * @return void
     */
    public static function rollBackTransaction($connection)
    {
        $connection = self::getConnectionInterface($connection);
        if (self::useEventDispatcher($connection)) {

            /** @var Connection $connection */
            $dispatcher = $connection->getEventDispatcher();
            $connection->unsetEventDispatcher();
            try {
                $connection->rollback();
            } catch (PDOException $e) {
                // act gracefully if the transaction was committed already? - no
            }
            $connection->setEventDispatcher($dispatcher);
//            $connection->disconnect();

        } else {
            // compatible with older versions of Laravel
            $connection->rollback();
        }
    }

    /**
     * Get the ConnectionInterface for a particular connection.
     *
     * @param string $connection The connection to use.
     * @return ConnectionInterface
     */
    private static function getConnectionInterface(string $connection): ConnectionInterface
    {
        /** @var ConnectionResolverInterface $database */
        $database = app('db');
        return $database->connection($connection);
    }

    /**
     * Check if Laravel's newer EventDispatcher should be used when applying transactions.
     *
     * @param ConnectionInterface $connection The connection to use.
     * @return boolean
     */
    private static function useEventDispatcher(ConnectionInterface $connection): bool
    {
        // this allows this code to run with older versions of Laravel versions
        return method_exists($connection, 'unsetEventDispatcher');
    }
}
