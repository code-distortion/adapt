<?php

namespace CodeDistortion\Adapt\Laravel\Middleware;

use Closure;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Filesystem;
use CodeDistortion\Adapt\Exceptions\AdaptBrowserTestException;
use CodeDistortion\Adapt\Support\Settings;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Throwable;

/**
 * Adapt Middleware - used to load temporary config files during browser tests.
 *
 * Added to local and testing environments.
 */
class AdaptShareConfigMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request The request object.
     * @param \Closure                 $next    The next thing to run.
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

        $cookieValue = $request->cookie(Settings::CONFIG_COOKIE);
        $cookieValue = is_string($cookieValue) ? $cookieValue : '';
        $usedTempConfig = $this->useTemporaryConfig($cookieValue);

        $response = $next($request);
        if ($usedTempConfig) {
            $this->reSetCookie($response, (string) $cookieValue);
        }
        return $response;
    }

    /**
     * Read the Adapt cookie, and if present, load the temporary config file it points to.
     *
     * @param string $cookieValue The cookie value.
     * @return boolean
     * @throws AdaptBrowserTestException When there was a problem loading the temporary config.
     */
    private function useTemporaryConfig(string $cookieValue): bool
    {
        $tempCachePath = $this->getTempCachePath($cookieValue);
        if (!$tempCachePath) {
            return false;
        }

        if (!(new Filesystem())->fileExists($tempCachePath)) {
            throw AdaptBrowserTestException::tempConfigFileNotLoaded($tempCachePath);
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
    private function getTempCachePath(string $cookieValue)
    {
        $cookieValue = base64_decode($cookieValue, true);
        if (!is_string($cookieValue)) {
            return null;
        }

        $cookieValue = @unserialize($cookieValue);
        if (!is_array($cookieValue)) {
            return null;
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
    private function replaceWholeConfig(array $configData)
    {
        foreach (array_keys(Config::all()) as $index) {
            Config::offsetUnset($index);
        }
        Config::set($configData);
    }

    /**
     * Add the database config settings to the cookie again - to help it stay when the user logs out.
     *
     * @param Response|mixed $response    The response object.
     * @param string         $cookieValue The cookie value.
     * @return void
     */
    private function reSetCookie($response, string $cookieValue)
    {
        if (!($response instanceof Response)) {
            return;
        }

        if ((!is_string($cookieValue)) || (!mb_strlen($cookieValue))) {
            return;
        }

        $response->cookie(Settings::CONFIG_COOKIE, $cookieValue, null, '/', null, false, false);
    }
}
