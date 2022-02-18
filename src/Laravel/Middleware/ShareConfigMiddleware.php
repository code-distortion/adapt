<?php

namespace CodeDistortion\Adapt\Laravel\Middleware;

use Closure;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Filesystem;
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
class ShareConfigMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request The request object.
     * @param Closure $next    The next thing to run.
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // the service-provider won't register this middleware when in production
        // this is an extra safety check - we definitely don't want this to run in production
        /** @var Application $app */
        $app = app();
        if (!$app->environment('local', 'testing')) {
            return $next($request);
        }

        $configHeaderValue = $this->readHeaderValue($request, Settings::SHARE_CONFIG_KEY);
        $configCookieValue = $this->readCookieValue($request, Settings::SHARE_CONFIG_KEY);
        $connectionDBsHeaderValue = $this->readHeaderValue($request, Settings::SHARE_CONNECTION_DB_LIST_KEY);
        $connectionDBsCookieValue = $this->readCookieValue($request, Settings::SHARE_CONNECTION_DB_LIST_KEY);

        $used = $this->useTemporaryConfig($configHeaderValue)
            ?: $this->useTemporaryConfig($configCookieValue)
            ?: $this->useConnectionDBs($connectionDBsHeaderValue)
            ?: $this->useConnectionDBs($connectionDBsCookieValue);

        // make it look like the cookies never existed
        if ($used) {
            $request->cookies->remove(Settings::SHARE_CONFIG_KEY);
            $request->cookies->remove(Settings::SHARE_CONNECTION_DB_LIST_KEY);
        }

        $response = $next($request);

        // put the cookies back again
        if ($used) {
            $this->reSetCookie($response, Settings::SHARE_CONFIG_KEY, $configCookieValue);
            $this->reSetCookie($response, Settings::SHARE_CONNECTION_DB_LIST_KEY, $connectionDBsCookieValue);
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
     * @param Request $request The request object.
     * @return array<string,string>|null
     */
    private function readHeaderValue(Request $request, string $headerName): string
    {
        $value = $request->headers->get($headerName);
        return is_string($value) ? $value : '';
    }

    /**
     * Decode the cookie or header's value.
     *
     * @param string $cookieValue The cookie value.
     * @return mixed
     */
    private function decode(string $cookieValue)
    {
        $cookieValue = base64_decode($cookieValue, true);
        if (!is_string($cookieValue)) {
            return null;
        }

        $cookieValue = @unserialize($cookieValue);
        if ($cookieValue === false) {
            return null;
        }

        return $cookieValue;
    }



    /**
     * Load the temporary config file the cookie or header points to.
     *
     * @param string $rawValue The cookie or header's raw value.
     * @return boolean
     * @throws AdaptBrowserTestException When there was a problem loading the temporary config.
     */
    private function useTemporaryConfig(string $rawValue): bool
    {
        $tempCachePath = $this->getTempCachePath($rawValue);
        if (!$tempCachePath) {
            return false;
        }

        if (!(new Filesystem())->fileExists($tempCachePath)) {
//            throw AdaptBrowserTestException::tempConfigFileNotLoaded($tempCachePath);
            return false;
        }

        try {
            $configData = require $tempCachePath;
        } catch (Throwable $e) {
            throw AdaptBrowserTestException::tempConfigFileNotLoaded($tempCachePath, $e);
        }

        if (!is_array($configData)) {
            throw AdaptBrowserTestException::tempConfigFileNotLoaded($tempCachePath);
        }

        $this->replaceWholeConfig($configData);

        return true;
    }

    /**
     * Pick the temporary cache-path from the Adapt cookie.
     *
     * @param string $cookieValue The cookie value.
     * @return string|null
     */
    private function getTempCachePath(string $cookieValue): ?string
    {
        $cookieValue = $this->decode($cookieValue);
        if (!is_array($cookieValue)) {
            return false;
        }

        if (!array_key_exists('tempConfigPath', $cookieValue)) {
            return null;
        }

        return $cookieValue['tempConfigPath'];
    }

    /**
     * Replace the whole config with new values.
     *
     * @param mixed[] $configData The config data to use instead.
     * @return void
     */
    private function replaceWholeConfig(array $configData): void
    {
        foreach (array_keys(Config::all()) as $index) {
            Config::offsetUnset($index);
        }
        Config::set($configData);
    }



    /**
     * Use the list of connections that Adapt has prepared, and their corresponding databases.
     *
     * Overwrite the connections' databases if present.
     *
     * @param string $rawValue The cookie or header's raw value.
     * @return boolean
     */
    private function useConnectionDBs(string $rawValue): bool
    {
        $connectionDatabases = $this->decode($rawValue);
        if (!is_array($connectionDatabases)) {
            return false;
        }

        LaravelSupport::useTestingConfig();
        LaravelSupport::useConnectionDatabases($connectionDatabases);

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
    private function reSetCookie($response, string $cookieName, string $cookieValue): void
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
