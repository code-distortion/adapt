<?php

namespace CodeDistortion\Adapt\Laravel\Middleware;

use Closure;
use CodeDistortion\Adapt\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdaptDatabaseConnectionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // extra safety check - we definitely don't want this to run in production
        if (!app()->environment('local', 'testing')) {
            return $next($request);
        }

        $databaseConfig = @unserialize($request->cookie(Settings::CONNECTIONS_COOKIE));
        if (!$databaseConfig) {
            return $next($request);
        }

        config(['database' => $databaseConfig]);

        $response = $next($request);

        $this->repeatCookie(
            $request->getHost(),
            $response,
            $request->cookie(Settings::CONNECTIONS_COOKIE)
        );

        return $response;
    }

    /**
     * Add the database config settings to the cookie again - to help it stay when the user logs out.
     *
     * @param string            $host        The host the request was made to.
     * @param Response|mixed    $response    The response object.
     * @param string|array|null $cookieValue The database config settings that were passed in the request.
     */
    private function repeatCookie(string $host, $response, $cookieValue): void
    {
        if (!($response instanceof Response)) {
            return;
        }
        if ((!is_string($cookieValue)) || (!mb_strlen($cookieValue))) {
            return;
        }

        $response->cookie(Settings::CONNECTIONS_COOKIE, $cookieValue, null, '/', $host, false, false);
    }
}
