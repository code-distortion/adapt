<?php

namespace CodeDistortion\Adapt\Support;

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
        $basePath = (string) base_path();
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
        app()->detectEnvironment(fn() => 'testing');
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
}
