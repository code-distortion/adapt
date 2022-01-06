<?php

namespace CodeDistortion\Adapt;

use CodeDistortion\Adapt\Boot\BootRemoteBuildLaravel;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Laravel\Commands\AdaptListCachesCommand;
use CodeDistortion\Adapt\Laravel\Commands\AdaptRemoveCachesCommand;
use CodeDistortion\Adapt\Laravel\Middleware\AdaptMiddleware;
use CodeDistortion\Adapt\Support\ReloadLaravelConfig;
use CodeDistortion\Adapt\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel as HttpKernel;

/**
 * Adapt's Laravel ServiceProvider.
 */
class AdaptLaravelServiceProvider extends ServiceProvider
{
    /** @var string The path to the config file in the filesystem. */
    private $configPath = __DIR__ . '/../config/config.php';



    /**
     * Service-provider register method.
     *
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * Service-provider boot method.
     *
     * @param Router $router Laravel's router.
     * @return void
     */
    public function boot(Router $router): void
    {
        $this->initialiseConfig();
        $this->publishConfig();
        $this->initialiseCommands();
        $this->initialiseMiddleware($router);
        $this->initialiseRoutes($router);
    }



    /**
     * Initialise the config settings file.
     *
     * @return void
     */
    protected function initialiseConfig(): void
    {
        $this->mergeConfigFrom($this->configPath, Settings::LARAVEL_CONFIG_NAME);
    }

    /**
     * Allow the default config to be published.
     *
     * @return void
     */
    protected function publishConfig(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }
        if ($this->app->environment('testing')) {
            return;
        }

        $this->publishes(
            [$this->configPath => config_path(Settings::LARAVEL_CONFIG_NAME . '.php'),],
            'config'
        );
    }



    /**
     * Initialise the artisan commands.
     *
     * @return void
     */
    protected function initialiseCommands(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            AdaptRemoveCachesCommand::class,
            AdaptListCachesCommand::class,
        ]);
    }



    /**
     * Initialise the middleware.
     *
     * @param Router $router Laravel's router.
     * @return void
     */
    protected function initialiseMiddleware(Router $router): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }
        if (!$this->app->environment('local', 'testing')) {
            return;
        }

        $httpKernel = $this->app->make(HttpKernel::class);
        $middlewareGroups = method_exists($httpKernel, 'getMiddlewareGroups')
            ? array_keys($httpKernel->getMiddlewareGroups())
            : ['web', 'api'];
        foreach ($middlewareGroups as $middlewareGroup) {
            $router->prependMiddlewareToGroup((string) $middlewareGroup, AdaptMiddleware::class);
        }
    }



    /**
     * Initialise the routes.
     *
     * @param Router $router Laravel's router.
     * @return void
     */
    protected function initialiseRoutes(Router $router): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }
        if (!$this->app->environment('local', 'testing')) {
            return;
        }

        // The path that browsers connect to initially (when browser testing) so that cookies can then be set
        // (the browser will reject new cookies before it's loaded a webpage)
        // this route bypasses all middleware
        $router->get(Settings::INITIAL_BROWSER_COOKIE_REQUEST_PATH, fn() => '');

        // Adapt sends "remote build" requests to this url
        $router->post(
            Settings::REMOTE_BUILD_REQUEST_PATH,
            function(Request $request) {

                $this->runFromBasePathDir();

                // use settings from .env.testing
                $this->useTestingConfig();

                $database = $this->executeBuilder($request->input('configDTO'));

                print $database;
            }
        );
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
    private function runFromBasePathDir(): void
    {
        chdir(base_path());
    }

    /**
     * Re-load Laravel's config using the .env.testing file.
     *
     * @return void
     */
    private function useTestingConfig(): void
    {
        $envFile = '.env.testing';
        (new ReloadLaravelConfig())->reload(base_path($envFile));
    }

    /**
     * Take the config data (from the request), build the Builder based on it, and execute it.
     *
     * @param string $serialisedConfigData The serialised configDTO data, from the request.
     * @return string
     */
    private function executeBuilder(string $serialisedConfigData): string
    {
        $configData = unserialize($serialisedConfigData);
        $remoteConfig = ConfigDTO::buildFromRemoteBuildRequest($configData);

        $bootRemoteBuildLaravel = new BootRemoteBuildLaravel();
        $bootRemoteBuildLaravel->ensureStorageDirExists();
        $builder = $bootRemoteBuildLaravel->makeNewBuilder($remoteConfig);
        $builder->execute();

        return $builder->getDatabase();
    }
}
