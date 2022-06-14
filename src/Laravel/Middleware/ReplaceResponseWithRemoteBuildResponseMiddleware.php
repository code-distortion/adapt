<?php

namespace CodeDistortion\Adapt\Laravel\Middleware;

use Closure;
use CodeDistortion\Adapt\Support\Settings;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

/**
 * When this request just built a database for a remote-build request, swap the current response for the remote-build
 * response. This way any changes made by other packages (like debug-bar) don't affect the output.
 */
class ReplaceResponseWithRemoteBuildResponseMiddleware
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

        $response = $next($request);

        return $app->make(Settings::SERVICE_CONTAINER_REMOTE_BUILD_RESPONSE) ?? $response;
    }
}
