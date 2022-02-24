<?php

namespace CodeDistortion\Adapt;

use CodeDistortion\Adapt\Boot\BootRemoteBuildLaravel;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Laravel\Commands\AdaptListCachesCommand;
use CodeDistortion\Adapt\Laravel\Commands\AdaptRemoveCachesCommand;
use CodeDistortion\Adapt\Laravel\Middleware\RemoteShareMiddleware;
use CodeDistortion\Adapt\Support\Exceptions;
use CodeDistortion\Adapt\Support\LaravelSupport;
use CodeDistortion\Adapt\Support\Settings;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Throwable;

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

        foreach ($this->getMiddlewareGroups() as $middlewareGroup) {
            $router->prependMiddlewareToGroup($middlewareGroup, RemoteShareMiddleware::class);
        }
    }

    /**
     * Generate the list of Laravel's middleware groups.
     *
     * @return string[]
     */
    private function getMiddlewareGroups(): array
    {
        $httpKernel = $this->app->make(HttpKernel::class);
        return method_exists($httpKernel, 'getMiddlewareGroups')
            ? array_keys($httpKernel->getMiddlewareGroups())
            : ['web', 'api'];
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
        $router->get(Settings::INITIAL_BROWSER_COOKIE_REQUEST_PATH, fn() => new Response());

        // Adapt sends "remote build" requests to this url
        $callback = fn(Request $request) => $this->handleBuildRequest($request);

        $router->post(Settings::REMOTE_BUILD_REQUEST_PATH, $callback);
        $router->post('/{catchall}' . Settings::REMOTE_BUILD_REQUEST_PATH, $callback)->where('catchall', '.*');
    }



    /**
     * Build a test-database for a remote installation of Adapt.
     *
     * @param Request $request The request object.
     * @return Response
     */
    private function handleBuildRequest(Request $request): Response
    {
        try {

            LaravelSupport::runFromBasePathDir();
            LaravelSupport::useTestingConfig();

            $remoteConfigDTO = ConfigDTO::buildFromPayload($request->input('configDTO'));

            $builder = (new BootRemoteBuildLaravel())
                ->ensureStorageDirExists()
                ->makeNewBuilder($remoteConfigDTO);

            $builder->execute();

            $resolvedSettingsDTO = $builder->getResolvedSettingsDTO();

            return response($resolvedSettingsDTO->buildPayload());

        } catch (Throwable $e) {

            $exceptionClass = Exceptions::resolveExceptionClass($e);
            return response("$exceptionClass: {$e->getMessage()}", 500);
        }
    }
}
