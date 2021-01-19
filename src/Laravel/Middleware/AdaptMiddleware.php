<?php

namespace CodeDistortion\Adapt\Laravel\Middleware;

use Closure;
use CodeDistortion\Adapt\DI\Injectable\Filesystem;
use CodeDistortion\Adapt\Exceptions\AdaptBrowserTestException;
use CodeDistortion\Adapt\Support\Settings;
use Config;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

/**
 * Adapt Middleware - used to load temporary config files during browser tests.
 *
 * Added to local and testing environments.
 */
class AdaptMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request The request object.
     * @param \Closure                 $next    The next thing to run.
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // the service-provider won't register this middleware when in production
        // this is an extra safety check - we definitely don't want this to run in production
        if (!app()->environment('local', 'testing')) {
            return $next($request);
        }

        $usedTempConfig = $this->useTemporaryConfig($request);
        $response = $next($request);
        if ($usedTempConfig) {
            $this->reSetCookie($request, $response);
        }
        return $response;
    }

    /**
     * Read the Adapt cookie, and if present, load the temporary config file it points to.
     *
     * @param Request $request The request object.
     * @return boolean
     * @throws AdaptBrowserTestException When there was a problem loading the temporary config.
     */
    private function useTemporaryConfig(Request $request): bool
    {
        $tempCachePath = $this->getTempCachePath($request);
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
     * @param Request $request The request object.
     * @return string|null
     */
    private function getTempCachePath(Request $request): ?string
    {
        $cookieValue = $request->cookie(Settings::CONNECTIONS_COOKIE);
        if (!is_string($cookieValue)) {
            return null;
        }

        $cookieValue = base64_decode($cookieValue);
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
    private function replaceWholeConfig(array $configData): void
    {
        foreach (array_keys(Config::all()) as $index) {
            Config::offsetUnset($index);
        }
        Config::set($configData);
    }

    /**
     * Add the database config settings to the cookie again - to help it stay when the user logs out.
     *
     * @param Request        $request  The request object.
     * @param Response|mixed $response The response object.
     * @return void
     */
    private function reSetCookie(Request $request, $response): void
    {
        if (!($response instanceof Response)) {
            return;
        }

        $cookieValue = $request->cookie(Settings::CONNECTIONS_COOKIE);
        if ((!is_string($cookieValue)) || (!mb_strlen($cookieValue))) {
            return;
        }

        $response->cookie(Settings::CONNECTIONS_COOKIE, $cookieValue, null, '/', $request->getHost(), false, false);
    }
}
