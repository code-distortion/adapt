<?php

namespace CodeDistortion\Adapt\Laravel\Middleware;

use Closure;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Filesystem;
use CodeDistortion\Adapt\DTO\RemoteShareDTO;
use CodeDistortion\Adapt\Exceptions\AdaptBrowserTestException;
use CodeDistortion\Adapt\Support\LaravelSupport;
use CodeDistortion\Adapt\Support\Settings;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

/**
 * Adapt Middleware - used to load sharable config files during browser tests.
 *
 * Is only added to local and testing environments.
 */
class RemoteShareMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request The request object.
     * @param Closure $next    The next thing to run.
     * @return mixed
     */
    public function handle($request, $next)
    {
        // the service-provider won't register this middleware when in production
        // this is an extra safety check - we definitely don't want this to run in production
        /** @var Application $app */
        $app = app();
        if (!$app->environment('local', 'testing')) {
            return $next($request);
        }

        $remoteShareDTO = LaravelSupport::buildRemoteShareDTOFromRequest($request);
        $shareCookieValue = LaravelSupport::readCookieValue($request, Settings::REMOTE_SHARE_KEY);

        $used = $this->useSharableConfig($remoteShareDTO) ?: $this->useConnectionDBs($remoteShareDTO);

        // make it look like the cookie never existed
        $request->cookies->remove(Settings::REMOTE_SHARE_KEY);

        $response = $next($request);

        // put the cookie back again
        if ($used) {
            $this->reSetCookie($response, Settings::REMOTE_SHARE_KEY, $shareCookieValue);
        }

        return $response;
    }







    /**
     * Load the sharable config file the cookie or header points to.
     *
     * @param RemoteShareDTO|null $remoteShareDTO The RemoteShareDTO, built from the request.
     * @return boolean
     * @throws AdaptBrowserTestException When there was a problem loading the sharable config.
     */
    private function useSharableConfig($remoteShareDTO): bool
    {
        if (!$remoteShareDTO) {
            return false;
        }

        if (!$remoteShareDTO->sharableConfigPath) {
            return false;
        }

        if (!(new Filesystem())->fileExists($remoteShareDTO->sharableConfigPath)) {
            // don't throw, the config details might have been passed from a remote Adapt instance
            // and the config file won't exist here, which is fine
//            throw AdaptBrowserTestException::sharableConfigFileNotLoaded($remoteShareDTO->sharableConfigPath);
            return false;
        }

        $configData = null;
        try {
            $configData = require $remoteShareDTO->sharableConfigPath;
        } catch (Throwable $e) {
            throw AdaptBrowserTestException::sharableConfigFileNotLoaded($remoteShareDTO->sharableConfigPath, $e);
        }

        if (!is_array($configData)) {
            throw AdaptBrowserTestException::sharableConfigFileNotLoaded($remoteShareDTO->sharableConfigPath);
        }

        $this->replaceWholeConfig($configData);

        // Laravel connects to the database in some situations before reaching here (e.g. when using debug-bar).
        // when using scenarios, this is the wrong database to use
        // disconnect now to start a fresh
        LaravelSupport::disconnectFromConnectedDatabases(LaravelSupport::newLaravelLogger());

        return true;
    }

    /**
     * Replace the whole config with new values.
     *
     * @param mixed[] $configData The config data to use instead.
     * @return void
     */
    private function replaceWholeConfig(array $configData)
    {
        /** @var Repository $config */
        $config = config();

        foreach (array_keys($config->all()) as $index) {
            $config->offsetUnset($index);
        }
        $config->set($configData);
    }



    /**
     * Use the list of connections that Adapt has prepared, and their corresponding databases.
     *
     * Loads Laravel's testing config, and overwrites the connections' databases if present.
     *
     * @param RemoteShareDTO|null $remoteShareDTO The RemoteShareDTO, built from the request.
     * @return boolean
     */
    private function useConnectionDBs($remoteShareDTO): bool
    {
        if (!$remoteShareDTO) {
            return false;
        }

        LaravelSupport::useTestingConfig();

        // Laravel connects to the database in some situations before reaching here (e.g. when using debug-bar).
        // when using scenarios, this is the wrong database to use
        // disconnect now to start a fresh
        LaravelSupport::disconnectFromConnectedDatabases(LaravelSupport::newLaravelLogger());

        LaravelSupport::useConnectionDatabases($remoteShareDTO->connectionDBs);

        return true;
    }



    /**
     * Add a cookie to the response - to help it stay when the user logs out.
     *
     * @param Response|mixed $response    The response object.
     * @param string         $cookieName  The cookie's name.
     * @param string         $cookieValue The cookie's value.
     * @return void
     */
    private function reSetCookie($response, string $cookieName, string $cookieValue)
    {
        if (!($response instanceof Response)) {
            return;
        }

        if (!mb_strlen($cookieValue)) {
            return;
        }

        $response->cookie($cookieName, $cookieValue, null, '/', null, false, false);
    }
}
