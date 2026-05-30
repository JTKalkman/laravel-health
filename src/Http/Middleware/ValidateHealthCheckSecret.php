<?php

namespace JTKalkman\LaravelHealth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ValidateHealthCheckSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('health.auth.health_check_secret');

        // If no secret is configured, block all requests in production.
        if (empty($secret)) {
            if (app()->isProduction()) {
                abort(404); // Don't reveal the existence of the endpoint.
            }
        }
                
        $header = config('health.auth.health_check_header_name', 'health-monitor-access-key');
        $provided = $request->header($header, '');


        if (!hash_equals($secret, $provided)) {
            abort(404); // Don't reveal the existence of the endpoint.
        }

        return $next($request);
    }
}
