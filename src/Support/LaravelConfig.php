<?php

namespace CodeDistortion\Adapt\Support;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Log\Logger;
use Illuminate\Log\Writer;
use Monolog\Logger as MonologLogger;
use ReflectionObject;
use Throwable;

/**
 * Reloads the config based on .env.testing, and updates Laravel to use the new environment.
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
        /** @var Application $app */
        $app = app();
        (new LoadConfiguration())->bootstrap($app);

        self::updateLoggerEnvironment();
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
