<?php

namespace CodeDistortion\Adapt;

use CodeDistortion\Adapt\Laravel\Commands\AdaptListCachesCommand;
use CodeDistortion\Adapt\Laravel\Commands\AdaptRemoveCachesCommand;
use CodeDistortion\Adapt\Support\Settings;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * Adapt's Laravel ServiceProvider.
 */
class AdaptLaravelServiceProvider extends BaseServiceProvider
{
    /**
     * Service-provider register method.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Service-provider boot method.
     *
     * @return void
     */
    public function boot()
    {
        $this->initialiseConfig();
        $this->initialiseCommands();
    }


    /**
     * Initialise the config settings file.
     *
     * @return void
     */
    protected function initialiseConfig()
    {
        // initialise the config
        $configPath = __DIR__ . '/../config/config.php';
        $this->mergeConfigFrom($configPath, Settings::LARAVEL_CONFIG_NAME);

        // allow the default config to be published
        if ((!$this->app->environment('testing')) && ($this->app->runningInConsole())) {

            $this->publishes(
                [$configPath => config_path(Settings::LARAVEL_CONFIG_NAME . '.php'),],
                'config'
            );
        }
    }

    /**
     * Initialise the artisan commands.
     *
     * @return void
     */
    protected function initialiseCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AdaptRemoveCachesCommand::class,
                AdaptListCachesCommand::class,
            ]);
        }
    }
}
