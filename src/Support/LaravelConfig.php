<?php

namespace CodeDistortion\Adapt\Support;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Log\Logger;
use Illuminate\Log\Writer;
use Illuminate\Support\ServiceProvider;
use Monolog\Logger as MonologLogger;
use ReflectionClass;
use ReflectionObject;
use Throwable;

/**
 * Reloads the config based on the current environment values, and updates Laravel to use the new environment.
 */
class LaravelConfig
{
    /**
     * Reload Laravel's entire config, based on the current env(..) values.
     *
     * This uses Laravel's functionality to load the config files.
     *
     * @return void
     */
    public static function reloadConfig()
    {
        /** @var Repository $config */
        $config = config();
        $origConfigValues = $config->all();

        self::reloadPublishedConfigs();

        self::loadConfigsAddedByServiceProviders($origConfigValues);

        self::addLeftOverMissingConfigs($origConfigValues);

        self::updateLoggerEnvironment();
    }



    /**
     * Get Laravel to reload the published configs.
     *
     * @return void
     */
    private static function reloadPublishedConfigs()
    {
        /** @var Application $app */
        $app = app();
        (new LoadConfiguration())->bootstrap($app);
    }

    /**
     * Load the config files that were registered by service-providers.
     *
     * Because reloading the config above skips the service-provider config files, load them here.
     *
     * @param mixed[] $origConfigValues The original config values.
     * @return void
     */
    private static function loadConfigsAddedByServiceProviders(array $origConfigValues)
    {
        /** @var array<string, array<string, string>> $publishGroups */
        $publishGroups = self::readStaticPrivateProperty(ServiceProvider::class, 'publishGroups');

        /** @var Application $app */
        $app = app();
        $baseDir = rtrim($app->configPath(), '\\/') . '/';

        foreach ($publishGroups as $publishGroup) {
            foreach ($publishGroup as $srcPath => $publishPath) {

                if (mb_substr($publishPath, 0, mb_strlen($baseDir)) != $baseDir) {
                    continue;
                }

                $configFilename = mb_substr($publishPath, mb_strlen($baseDir));

                $temp = explode('.', $configFilename);
                $extension = count($temp) > 1 ? '.' . end($temp) : '';

                $configName = mb_substr($configFilename, 0, -mb_strlen($extension));
                $configName = str_replace('\\/', '.', $configName);

                // only add it if it used to exist before
                if (array_key_exists($configName, $origConfigValues)) {
                    self::mergeConfigFrom($srcPath, $configName);
                }
            }
        }
    }

    /**
     * Merge the given configuration with the existing configuration.
     *
     * @param string $path The path to load the config data from.
     * @param string $key  The config name.
     * @return void
     * @see \Illuminate\Support\ServiceProvider::mergeConfigFrom()
     */
    protected static function mergeConfigFrom($path, $key)
    {
        /** @var Application $app */
        $app = app();

        if (!($app instanceof CachesConfiguration && $app->configurationIsCached())) {

            /** @var Repository $config */
            $config = config();

            /** @var mixed[] $value */
            $value = $config->get($key, []);

            $config->set($key, array_merge(require $path, $value));
        }
    }

    /**
     * If any configs are still missing after reloading them, copy them back in based on their original values.
     *
     * e.g. tinker's config doesn't get re-populated and needs this.
     *
     * @param mixed[] $origConfigValues The original config values.
     * @return void
     */
    protected static function addLeftOverMissingConfigs($origConfigValues)
    {
        /** @var Repository $config */
        $config = config();

        foreach ($origConfigValues as $key => $values) {
            if (!$config->has($key)) {
                config([$key => $values]);
            }
        }
    }



    /**
     * The logger in older versions of Laravel don't keep up-to-date with changes to the environment.
     *
     * This updates it to use the new environment.
     *
     * The only difference this code makes is that the logging will look like:
     * "testing.DEBUG: ADAPT: …" instead of
     * "local.DEBUG: ADAPT: …"
     *
     * @return void
     */
    private static function updateLoggerEnvironment()
    {
        try {

            /** @var Application $app */
            $app = app();

            /** @var Writer $log */
            $log = $app->make('log');

            /** @var Logger|null $monolog */
            $monolog = null;
            if (method_exists($log, 'driver')) {
                $monolog = $log->driver();
            } elseif (method_exists($log, 'getMonolog')) {
                $monolog = $log->getMonolog();
            }

            if ($monolog instanceof MonologLogger) {
                self::updatePrivateProperty($monolog, 'name', $app->environment());
//            } elseif ($monolog instanceof Logger) {
            }

        } catch (Throwable $e) {
        }
    }



    /**
     * Read a class static private property.
     *
     * @param string $class        The class to look in to.
     * @param string $propertyName The property to read.
     * @return mixed
     */
    private static function readStaticPrivateProperty(string $class, string $propertyName)
    {
        $reflection = new ReflectionClass($class);

        if (!$reflection->hasProperty($propertyName)) {
            return null;
        }

        $prop = $reflection->getProperty($propertyName);
        $prop->setAccessible(true);
        return $prop->getValue();
    }

    /**
     * Update an object's private property.
     *
     * @param object $object       The object to alter.
     * @param string $propertyName The property to update.
     * @param mixed  $newValue     The value to set.
     * @return void
     */
    private static function updatePrivateProperty($object, string $propertyName, $newValue)
    {
        $reflection = new ReflectionObject($object);

        if (!$reflection->hasProperty($propertyName)) {
            return;
        }

        $prop = $reflection->getProperty($propertyName);
        $prop->setAccessible(true);
        $prop->setValue($object, $newValue);
    }



    /**
     * Re-evaluate a particular config file, based on the current env(..) values.
     *
     * @param string $configFile The config file to load.
     * @return mixed[]
     */
    public static function readConfigFile($configFile): array
    {
        $configPath = config_path("$configFile.php");

        return file_exists($configPath)
            ? (array) require($configPath)
            : [];
    }
}
