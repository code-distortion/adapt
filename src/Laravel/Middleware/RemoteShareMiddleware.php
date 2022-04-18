<?php

namespace CodeDistortion\Adapt\Laravel\Middleware;

use Closure;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Filesystem;
use CodeDistortion\Adapt\DTO\RemoteShareDTO;
use CodeDistortion\Adapt\Exceptions\AdaptBrowserTestException;
use CodeDistortion\Adapt\Support\LaravelSupport;
use CodeDistortion\Adapt\Support\Settings;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Throwable;

/**
 * Adapt Middleware - used to load temporary config files during browser tests.
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

        $shareHeaderValue = $this->readHeaderValue($request, Settings::REMOTE_SHARE_KEY);
        $shareCookieValue = $this->readCookieValue($request, Settings::REMOTE_SHARE_KEY);

        $used = $this->useTemporaryConfig($shareHeaderValue)
            ?: $this->useTemporaryConfig($shareCookieValue)
            ?: $this->useConnectionDBs($shareHeaderValue)
            ?: $this->useConnectionDBs($shareCookieValue);

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
     * Read a cookie's raw value from the request.
     *
     * @param Request $request    The request to rook in.
     * @param string  $cookieName The cookie to look for.
     * @return string
     */
    private function readCookieValue(Request $request, string $cookieName): string
    {
        $value = $request->cookie($cookieName);
        return is_string($value) ? $value : '';
    }

    /**
     * Read a header's raw value from the request.
     *
     * @param Request $request    The request object.
     * @param string  $headerName The name of the header to read.
     * @return string
     */
    private function readHeaderValue(Request $request, string $headerName): string
    {
        $value = $request->headers->get($headerName);
        return is_string($value) ? $value : '';
    }



    /**
     * Load the temporary config file the cookie or header points to.
     *
     * @param string $rawValue The remote-share raw value passed in the request.
     * @return boolean
     * @throws AdaptBrowserTestException When there was a problem loading the temporary config.
     */
    private function useTemporaryConfig(string $rawValue): bool
    {
        $remoteShareDTO = RemoteShareDTO::buildFromPayload($rawValue);
        if (!$remoteShareDTO) {
            return false;
        }

        if (!$remoteShareDTO->tempConfigPath) {
            return false;
        }

        if (!(new Filesystem())->fileExists($remoteShareDTO->tempConfigPath)) {
//            throw AdaptBrowserTestException::tempConfigFileNotLoaded($tempCachePath);
            return false;
        }

        try {
            $configData = require $remoteShareDTO->tempConfigPath;
        } catch (Throwable $e) {
            throw AdaptBrowserTestException::tempConfigFileNotLoaded($remoteShareDTO->tempConfigPath, $e);
        }

        if (!is_array($configData)) {
            throw AdaptBrowserTestException::tempConfigFileNotLoaded($remoteShareDTO->tempConfigPath);
        }

        $this->replaceWholeConfig($configData);

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
        foreach (array_keys(Config::all()) as $index) {
            Config::offsetUnset($index);
        }
        Config::set($configData);
    }



    /**
     * Use the list of connections that Adapt has prepared, and their corresponding databases.
     *
     * Loads Laravel's testing config, and overwrites the connections' databases if present.
     *
     * @param string $rawValue The remote-share raw value passed in the request.
     * @return boolean
     */
    private function useConnectionDBs(string $rawValue): bool
    {
        $remoteShareDTO = RemoteShareDTO::buildFromPayload($rawValue);
        if (!$remoteShareDTO) {
            return false;
        }

        LaravelSupport::useTestingConfig();
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

        if ((!is_string($cookieValue)) || (!mb_strlen($cookieValue))) {
            return;
        }

        $response->cookie($cookieName, $cookieValue, null, '/', null, false, false);
    }
}
