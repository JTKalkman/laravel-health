<?php
 
namespace JTKalkman\LaravelHealth\Http\Middleware;
 
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
 
final class RequireHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('health.require_https', true)) {
            return $next($request);
        }
 
        if (!$request->isSecure()) {
            abort(404); // Don't reveal the existence of the endpoint.
        }
 
        return $next($request);
    }
}
