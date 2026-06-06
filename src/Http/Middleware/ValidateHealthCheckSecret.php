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

        if (empty($secret)) {
            logger()->warning(
                'Health check: no secret configured. ' .
                'Add HEALTH_CHECK_SECRET to your .env to enable the health endpoint.'
            );
            abort(404);
        }
                
        $header = config('health.auth.health_check_header_name', 'health-monitor-access-key');
        $provided = $request->header($header, '');

        if (!hash_equals($secret, $provided)) {
            abort(404); // Don't reveal the existence of the endpoint.
        }

        return $next($request);
    }
}
