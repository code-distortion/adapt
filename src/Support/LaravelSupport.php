<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\Adapt\DTO\RemoteShareDTO;
use CodeDistortion\Adapt\Exceptions\AdaptRemoteShareException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

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
    public static function useTestingConfig(): void
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
    public static function useConnectionDatabases(array $connectionDatabases): void
    {
        foreach ($connectionDatabases as $connection => $database) {
            if (!is_null(config("database.connections.$connection.database"))) {
                config(["database.connections.$connection.database" => $database]);
            }
        }
    }

    /**
     * make sure the code is running from the Laravel base dir.
     *
     * e.g. /var/www/html instead of /var/www/html/public
     *
     * This ensures that the paths of hash files (migrations, seeders etc) are resolved identically, compared to when
     * Adapt is running in non-web situations tests (i.e. tests).
     *
     * @return void
     */
    public static function runFromBasePathDir(): void
    {
        chdir(base_path());
    }

    /**
     * Get a value from Laravel's config, and make sure it's a string.
     *
     * @param string $key     The config key to get.
     * @param string $default The default value.
     * @return string
     */
    public static function configString(string $key, string $default = ''): string
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
    public static function configArray(string $key, array $default = []): array
    {
        $value = config($key, $default);
        if (is_array($value)) {
            return $value;
        }
        return [];
    }

    /**
     * Look at the seeder properties and config value, and determine what the seeders should be.
     *
     * @param boolean $hasSeedersProp Whether the test has the $seeders property or not.
     * @param mixed   $seedersProp    The $seeders property.
     * @param boolean $hasSeedProp    Whether the test has the $seed property or not.
     * @param mixed   $seedProp       The $seed property.
     * @param mixed   $seedersConfig  The code_distortion.adapt.seeders Laravel config value.
     * @return string[]
     */
    public static function resolveSeeders(
        bool $hasSeedersProp,
        $seedersProp,
        bool $hasSeedProp,
        $seedProp,
        $seedersConfig
    ): array {

        // use the $seeders property first if it exists
        if ($hasSeedersProp) {
            $seeders = $seedersProp;
        // use the default DatabaseSeeder when $seed is truthy
        // or none if $seed exists and is falsey
        } elseif ($hasSeedProp) {
            $seeders = $seedProp ? 'Database\\Seeders\\DatabaseSeeder' : [];
        // fall back to the seeders
        } else {
            $seeders = $seedersConfig;
        }
        $seeders = is_string($seeders) ? [$seeders] : $seeders;
        return is_array($seeders) ? $seeders : [];
    }

    /**
     * Record the list of connections that have been prepared, and their corresponding databases with the framework.
     *
     * @param array<string,string> $connectionDatabases The connections and the databases created for them.
     * @return void
     */
    public static function registerPreparedConnectionDBsWithFramework(array $connectionDatabases): void
    {
        /** @var Application $app */
        $app = app();
        method_exists($app, 'scoped')
            ? $app->scoped(Settings::REMOTE_SHARE_CONNECTIONS_SINGLETON_NAME, fn() => $connectionDatabases)
            : $app->singleton(Settings::REMOTE_SHARE_CONNECTIONS_SINGLETON_NAME, fn() => $connectionDatabases);
    }

    /**
     * Read the list of connections that have been prepared, and their corresponding databases from the framework.
     *
     * @return array<string, string>|null
     */
    public static function readPreparedConnectionDBsFromFramework(): ?array
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
     * @param Request $request
     * @return RemoteShareDTO|null
     * @throws AdaptRemoteShareException When the
     */
    public static function buildRemoteShareDTOFromRequest(Request $request): ?RemoteShareDTO
    {
        $shareHeaderValue = static::readHeaderValue($request, Settings::REMOTE_SHARE_KEY);
        $shareCookieValue = static::readCookieValue($request, Settings::REMOTE_SHARE_KEY);

        return RemoteShareDTO::buildFromPayload($shareHeaderValue)
            ?? RemoteShareDTO::buildFromPayload($shareCookieValue)
            ?? null;
    }

    /**
     * Read a cookie's raw value from the request.
     *
     * @param Request $request    The request to rook in.
     * @param string  $cookieName The cookie to look for.
     * @return string
     */
    public static function readCookieValue(Request $request, string $cookieName): string
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
}
