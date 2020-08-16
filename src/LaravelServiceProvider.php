<?php

namespace CodeDistortion\Adapt;

use CodeDistortion\Adapt\Support\LaravelSettingsTrait;
use CodeDistortion\Adapt\Laravel\Commands\AdaptListCachesCommand;
use CodeDistortion\Adapt\Laravel\Commands\AdaptRemoveCachesCommand;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * Adapt LaravelServiceProvider.
 */
class LaravelServiceProvider extends BaseServiceProvider
{
    use LaravelSettingsTrait;


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
        $configPath = __DIR__.'/../config/config.php';
        $this->mergeConfigFrom($configPath, $this->configName);

        // allow the default config to be published
        if ((!$this->app->environment('testing'))
            && ($this->app->runningInConsole())) {

            $this->publishes(
                [$configPath => config_path($this->configName.'.php'),],
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
