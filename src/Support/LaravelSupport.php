<?php

namespace CodeDistortion\Adapt\Support;

use Illuminate\Foundation\Application;

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
     * Re-load Laravel's config using the .env.testing file.
     *
     * @param string $envFile The env-file to use.
     * @return void
     */
    public static function useTestingConfig(string $envFile = '.env.testing'): void
    {
        (new ReloadLaravelConfig())->reload(base_path($envFile));
        /** @var Application $app */
        $app = app();
        $app->detectEnvironment(fn() => 'testing');
    }

    /**
     * make sure the code is running from the Laravel base dir.
     *
     * e.g. /var/www/html instead of /var/www/html/public
     *
     * This ensures that the paths of hash files (migrations, seeders etc) are resolved identically, compared to when
     * Adapt is running in non-web situations tests (e.g. tests).
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
     * @param string $key     The config key to get.
     * @param array  $default The default value.
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
}
