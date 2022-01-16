<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\FluentDotEnv\FluentDotEnv;
use Illuminate\Support\Env;

/**
 * Reload Laravel config files for the given .env file.
 */
class ReloadLaravelConfig
{
    /**
     * Override the config values after loading the given .env file values.
     *
     * @param string $envPath The .env file to load.
     * @return void
     */
    public function reload(string $envPath): void
    {
        $dotEnvValues = FluentDotEnv::new()->safeLoad($envPath)->all();
        $this->addValuesToEnvHelper($dotEnvValues);
        $this->replaceConfig();
    }

    /**
     * Apply the given values to the env() helper function.
     *
     * @param mixed[] $values The values to add.
     * @return void
     */
    private function addValuesToEnvHelper(array $values): void
    {
        // the new way that env() works in Laravel
        if (class_exists(Env::class)) {
            $repository =  method_exists(Env::class, 'getRepository')
                ? Env::getRepository()
                : Env::getFactory();
            foreach ($values as $name => $value) {
                $repository->set($name, $value);
            }
            return;
        }
        // the old way that env() works in Laravel
        foreach ($values as $name => $value) {
            putenv($name . '=' . $value);
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
        return (file_exists($configPath)
            ? (array) require($configPath)
            : []
        );
    }

    /**
     * Re-evaluate the config values and store them.
     *
     * @return void
     */
    private function replaceConfig(): void
    {
        $adaptConfigPath = LaravelSupport::isRunningInOrchestra()
            ? base_path('../../../../tests/workspaces/current/config/' . Settings::LARAVEL_CONFIG_NAME . '.php')
            : config_path(Settings::LARAVEL_CONFIG_NAME . '.php');

        config([
            'database' => $this->loadConfigFile(config_path('database.php')),
            Settings::LARAVEL_CONFIG_NAME => array_merge(
                $this->loadConfigFile(__DIR__ . '/../../config/config.php'),
                $this->loadConfigFile($adaptConfigPath)
            )
        ]);
    }
}
