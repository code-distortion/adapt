<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\FluentDotEnv\FluentDotEnv;
use Illuminate\Support\Env;

/**
 * Reload Laravel config files for the given .env file.
 */
class ReloadLaravelConfig
{
    use LaravelSettingsTrait;

    /**
     * Override the config values after loading the given .env file values.
     *
     * @param string $envPath The .env file to load.
     * @return void
     */
    public function reload(string $envPath)
    {
        $dotEnvValues = FluentDotEnv::new()->safeLoad($envPath)->all();
        $this->addValuesToEnvHelper($dotEnvValues);
        $this->replaceConfig();
    }

    /**
     * Apply the given values to the env() helper function.
     *
     * @param string[] $values The values to add.
     * @return void
     */
    private function addValuesToEnvHelper(array $values)
    {
        // the new way that env() works in Laravel
        if (class_exists(Env::class)) {
            $repository = Env::getRepository();
            foreach ($values as $name => $value) {
                $repository->set($name, $value);
            }
            return;
        }
        // the old way that env() works in Laravel
        foreach ($values as $name => $value) {
            putenv($name.'='.$value);
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
    private function replaceConfig()
    {
        config([
            'database' => $this->loadConfigFile(config_path('database.php')),
            $this->configName => array_merge(
                $this->loadConfigFile(__DIR__.'/../../config/config.php'),
                $this->loadConfigFile(config_path($this->configName.'.php'))
            )
        ]);
    }
}
