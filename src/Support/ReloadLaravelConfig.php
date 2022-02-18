<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\FluentDotEnv\FluentDotEnv;
use CodeDistortion\FluentDotEnv\Misc\GetenvSupport;
use Illuminate\Support\Env;

/**
 * Reload Laravel config files for the given .env file.
 */
class ReloadLaravelConfig
{
    /**
     * Override the config values after loading the given .env file values.
     *
     * @param string   $envPath     The .env file to load.
     * @param string[] $configFiles The config files to load.
     * @return void
     */
    public function reload(string $envPath, array $configFiles): void
    {
        $dotEnvValues = FluentDotEnv::new()->safeLoad($envPath)->all();
        $this->addValuesToEnvHelper($dotEnvValues);
        $this->replaceConfig($configFiles);
    }

    /**
     * Apply the given values to the env() helper function.
     *
     * @param mixed[] $values The values to add.
     * @return void
     */
    private function addValuesToEnvHelper(array $values): void
    {
        class_exists(Env::class)
            ? $this->addValuesToNewEnvHelper($values)
            : $this->addValuesToOldEnvHelper($values);
    }

    /**
     * Apply the given values to the env() helper function - the way Laravel does it now.
     *
     * @param mixed[] $values The values to add.
     * @return void
     */
    private function addValuesToNewEnvHelper(array $values): void
    {
        $origServer = $_SERVER;
        $origEnv = $_ENV;
        $origGetenv = GetenvSupport::getenvValues();

        // empty these so the immutable writer inside the repository can set new values
        $_SERVER = $_ENV = [];
        GetenvSupport::replaceGetenv([]);

        $repository = method_exists(Env::class, 'getRepository')
            ? Env::getRepository()
            : Env::getFactory();

        foreach ($values as $name => $value) {
            $repository->set($name, $value);
        }

        $_SERVER = array_merge($origServer, $values);
        $_ENV = array_merge($origEnv, $values);
        GetenvSupport::replaceGetenv(array_merge($origGetenv, $values));
    }

    /**
     * Apply the given values to the env() helper function - the way Laravel used to do it.
     *
     * @param mixed[] $values The values to add.
     * @return void
     */
    private function addValuesToOldEnvHelper(array $values): void
    {
        foreach ($values as $name => $value) {
            putenv($name . '=' . $value);
        }
    }

    /**
     * Re-evaluate the config values and store them.
     *
     * @param string[] $configFiles The config files to load.
     * @return void
     */
    private function replaceConfig(array $configFiles): void
    {
        $adaptConfigPath = LaravelSupport::isRunningInOrchestra()
            ? base_path('../../../../tests/workspaces/current/config/' . Settings::LARAVEL_CONFIG_NAME . '.php')
            : config_path(Settings::LARAVEL_CONFIG_NAME . '.php');

        foreach ($configFiles as $configFile) {

            $values = [];
            if ($configFile == Settings::LARAVEL_CONFIG_NAME) {
                $values[] = $this->loadConfigFile(__DIR__ . '/../../config/config.php');
                $values[] = $this->loadConfigFile($adaptConfigPath);
            } else {
                $values[] = $this->loadConfigFile(config_path("$configFile.php"));
            }
            config([$configFile => array_merge(...$values)]);
        }
    }

    /**
     * Reload the values for the given config file.
     *
     * @param string $configPath The path to the config file.
     * @return mixed[]
     */
    private function loadConfigFile(string $configPath): array
    {
        return file_exists($configPath)
            ? (array) require($configPath)
            : [];
    }
}
