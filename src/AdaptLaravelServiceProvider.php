<?php

namespace CodeDistortion\Adapt;

use CodeDistortion\Adapt\Boot\BootRemoteBuildLaravel;
use CodeDistortion\Adapt\DI\Injectable\Interfaces\LogInterface;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelLog;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Exceptions\AdaptBootException;
use CodeDistortion\Adapt\Exceptions\AdaptRemoteShareException;
use CodeDistortion\Adapt\Laravel\Commands\AdaptListCachesCommand;
use CodeDistortion\Adapt\Laravel\Commands\AdaptRemoveCachesCommand;
use CodeDistortion\Adapt\Laravel\Middleware\RemoteShareMiddleware;
use CodeDistortion\Adapt\Support\Exceptions;
use CodeDistortion\Adapt\Support\LaravelSupport;
use CodeDistortion\Adapt\Support\Settings;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Http\Kernel;
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
    public function register()
    {
        $this->initialiseConfig();
    }

    /**
     * Service-provider boot method.
     *
     * @param Router $router Laravel's router.
     * @return void
     */
    public function boot(Router $router)
    {
        $this->publishConfig();
        $this->initialiseCommands();
        $this->initialiseMiddleware();
        $this->initialiseRoutes($router);

        /** @var Request $request */
        $request = request(); // request is obtained this way because older versions of Laravel don't inject it
        $this->detectAdaptRequest($request);
    }



    /**
     * Initialise the config settings file.
     *
     * @return void
     */
    protected function initialiseConfig()
    {
        $this->mergeConfigFrom($this->configPath, Settings::LARAVEL_CONFIG_NAME);
    }

    /**
     * Allow the default config to be published.
     *
     * @return void
     */
    protected function publishConfig()
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
    protected function initialiseCommands()
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
     * @return void
     */
    protected function initialiseMiddleware()
    {
        if ($this->app->runningInConsole()) {
            return;
        }
        if (!$this->app->environment('local', 'testing')) {
            return;
        }

        /** @var Kernel $httpKernel */
        $httpKernel = $this->app->make(HttpKernel::class);
        $httpKernel->prependMiddleware(RemoteShareMiddleware::class);
    }



    /**
     * Initialise the routes.
     *
     * @param Router $router Laravel's router.
     * @return void
     */
    protected function initialiseRoutes($router)
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
        $router->get(Settings::INITIAL_BROWSER_COOKIE_REQUEST_PATH, function () {
            return new Response();
        });

        // Adapt sends "remote build" requests to this url
        $callback = function (Request $request) {
            return $this->handleBuildRequest($request);
        };

        $router->post(Settings::REMOTE_BUILD_REQUEST_PATH, $callback);
        $router->post('/{catchall}' . Settings::REMOTE_BUILD_REQUEST_PATH, $callback)->where('catchall', '.*');
    }





    /**
     * Build a test-database for a remote installation of Adapt.
     *
     * @param Request $request The request object.
     * @return ResponseFactory|Response
     */
    private function handleBuildRequest(Request $request)
    {
        $log = null;
        try {

            LaravelSupport::runFromBasePathDir();
            LaravelSupport::useTestingConfig();
            $log = $this->newLog();

            return $this->executeBuilder($request, $log);

        } catch (Throwable $e) {
            return $this->handleException($e, $log);
        }
    }

    /**
     * Build a new Log instance.
     *
     * @return LogInterface
     */
    private function newLog(): LogInterface
    {
        // don't use stdout debugging, it will ruin the response being generated that the calling Adapt instance reads.
        return new LaravelLog(false, (bool) config(Settings::LARAVEL_CONFIG_NAME . '.log.laravel'), (int) config(Settings::LARAVEL_CONFIG_NAME . '.log.verbosity'));
    }

    /**
     * Create a DatabaseBuilder, execute it, and build the response to return.
     *
     * @param Request      $request The request object.
     * @param LogInterface $log     The logger to use.
     * @return ResponseFactory|Response
     * @throws AdaptBootException When the ConfigDTO can't be built from its payload.
     */
    private function executeBuilder(Request $request, LogInterface $log)
    {
        $payload = $request->input('configDTO');
        $payload = is_string($payload) ? $payload : ''; // phpstan

        $configDTO = $this->buildConfigDTO($payload);
        if (!$configDTO) {
            throw AdaptBootException::couldNotReadRemoteConfiguration();
        }

        $builder = $this->makeBuilder($configDTO, $log);
        $builder->execute();

        $resolvedSettingsDTO = $builder->getResolvedSettingsDTO();
        return response(
            $resolvedSettingsDTO ? $resolvedSettingsDTO->buildPayload() : null
        );
    }

    /**
     * Build the ConfigDTO to use based on the payload passed by the caller.
     *
     * @param string $payload The raw ConfigDTO data from the request.
     * @return ConfigDTO|null
     */
    private function buildConfigDTO(string $payload)
    {
        return ConfigDTO::buildFromPayload($payload);
    }

    /**
     * Create a new build to use.
     *
     * @param ConfigDTO    $configDTO The ConfigDTO passed by the caller.
     * @param LogInterface $log       The logger to use.
     * @return DatabaseBuilder
     */
    private function makeBuilder(ConfigDTO $configDTO, LogInterface $log): DatabaseBuilder
    {
        return (new BootRemoteBuildLaravel())
            ->log($log)
            ->ensureStorageDirExists()
            ->makeNewBuilder($configDTO);
    }

    /**
     * Handle an exception.
     *
     * @param Throwable         $e   The exception that occurred.
     * @param LogInterface|null $log The logger to use.
     * @return ResponseFactory|Response
     */
    private function handleException(Throwable $e, $log)
    {
        if ($log) {
            Exceptions::logException($log, $e, true);
        }

        $exceptionClass = Exceptions::readableExceptionClass($e);
        return response("$exceptionClass: {$e->getMessage()}", 500);
    }




    /**
     * Detect if the request is from an external instance of Adapt.
     *
     * This is done here in the service-provider so the "testing" environment can be set sooner than when the
     * middleware runs. (this is done because things like Telescope seem to force a connection to the .env database
     * before the middleware runs).
     *
     * @param Request $request The request to inspect.
     * @return void
     * @throws AdaptRemoteShareException Thrown if the remote-share header/cookie is present but invalid.
     */
    private function detectAdaptRequest(Request $request)
    {
        if (!LaravelSupport::buildRemoteShareDTOFromRequest($request)) {
            return;
        }

        LaravelSupport::useTestingConfig();
    }
}
