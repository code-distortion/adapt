<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use CodeDistortion\FluentDotEnv\Exceptions\InvalidPathException;
use CodeDistortion\FluentDotEnv\FluentDotEnv;
use CodeDistortion\FluentDotEnv\Misc\GetenvSupport;
use Illuminate\Support\Env;

/**
 * Reload .env values.
 */
class LaravelEnv
{
    /**
     * Override the config values after loading the given .env file values.
     *
     * @param string   $envPath   The .env file to load.
     * @param string[] $overrides Values to override.
     * @return void
     * @throws AdaptConfigException When the .env file can't be read.
     */
    public static function reloadEnv($envPath, $overrides = [])
    {
        try {
            $dotEnvValues = FluentDotEnv::new()->load($envPath)->all();
            $dotEnvValues = array_merge($dotEnvValues, $overrides);
        } catch (InvalidPathException $e) {
            throw AdaptConfigException::cannotLoadEnvTestingFile();
        }

        class_exists(Env::class)
            ? self::addValuesToNewEnvHelper($dotEnvValues)
            : self::addValuesToOldEnvHelper($dotEnvValues);
    }

    /**
     * Apply the given values to the env() helper function - the way Laravel does it now.
     *
     * @param mixed[] $values The values to add.
     * @return void
     */
    private static function addValuesToNewEnvHelper(array $values)
    {
        $origServer = $_SERVER;
        $origEnv = $_ENV;
        $origGetenv = GetenvSupport::getAllGetenvVariables();

        // empty these so the immutable writer inside the repository can set new values
        $_SERVER = $_ENV = [];
        GetenvSupport::replaceAllGetenvVariables([]);

        $repository = method_exists(Env::class, 'getRepository')
            ? Env::getRepository()
            : Env::getFactory();

        foreach ($values as $name => $value) {
            $repository->set($name, $value);
        }

        $_SERVER = array_merge($origServer, $values);
        $_ENV = array_merge($origEnv, $values);
        GetenvSupport::replaceAllGetenvVariables(array_merge($origGetenv, $values));
    }

    /**
     * Apply the given values to the env() helper function - the way Laravel used to do it.
     *
     * @param mixed[] $values The values to add.
     * @return void
     */
    private static function addValuesToOldEnvHelper(array $values)
    {
        $_SERVER = array_merge($_SERVER, $values);
        $_ENV = array_merge($_ENV, $values);

        foreach ($values as $name => $value) {
            putenv($name . '=' . $value);
        }
    }
}
