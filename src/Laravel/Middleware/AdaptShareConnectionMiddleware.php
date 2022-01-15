<?php

namespace CodeDistortion\Adapt\Laravel\Middleware;

use Closure;
use CodeDistortion\Adapt\Support\LaravelSupport;
use CodeDistortion\Adapt\Support\Settings;
use Illuminate\Http\Request;

/**
 * Adapt Middleware - used to detect the list of connection databases from remote installations of Adapt.
 *
 * Added to local and testing environments.
 */
class AdaptShareConnectionMiddleware
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
        $this->reassignConnectionDatabases($request);

        return $next($request);
    }

    /**
     * Look for the header, and if present re-assign Laravel's database connection databases.
     *
     * @param Request $request The request object.
     * @return void
     */
    private function reassignConnectionDatabases(Request $request): void
    {
        // the service-provider won't register this middleware when in production
        // this is an extra safety check - we definitely don't want this to run in production
        if (!app()->environment('local', 'testing')) {
            return;
        }

        $connectionDatabases = $this->pickConnectionDBsFromRequest($request);
        if (!is_array($connectionDatabases)) {
            return;
        }

        $this->prepareConfig($connectionDatabases);
    }

    /**
     * Look for the http header in the request.
     *
     * @param Request $request The request object.
     * @return array|null
     */
    private function pickConnectionDBsFromRequest(Request $request): ?array
    {
        $connectionDatabases = $request->headers->get(Settings::SHARE_CONNECTIONS_HTTP_HEADER_NAME);
        if (!mb_strlen($connectionDatabases)) {
            return null;
        }

        $connectionDatabases = @unserialize($connectionDatabases);
        if (!is_array($connectionDatabases)) {
            return null;
        }

        return $connectionDatabases;
    }

    /**
     * Build Laravel's config.
     *
     * @param array $connectionDatabases The connections' databases.
     * @return void
     */
    private function prepareConfig(array $connectionDatabases): void
    {
        LaravelSupport::useTestingConfig();

        // override the connection's databases
        foreach ($connectionDatabases as $connection => $database) {
            if (config("database.connections.$connection.database")) {
                config(["database.connections.$connection.database" => $database]);
            }
        }
    }
}
