<?php

use JTKalkman\LaravelHealth\HealthChecks\CpuLoadCheck;
use JTKalkman\LaravelHealth\HealthChecks\DatabaseConnectionCheck;
use JTKalkman\LaravelHealth\HealthChecks\DatabaseConnectionCountCheck;
use JTKalkman\LaravelHealth\HealthChecks\DiskSpaceCheck;
use JTKalkman\LaravelHealth\HealthChecks\DiskSpaceInodeCheck;
use JTKalkman\LaravelHealth\HealthChecks\MemoryCheck;

return [

    /*
    |--------------------------------------------------------------------------
    | Health Check Route
    |--------------------------------------------------------------------------
    |
    | The URI where the health endpoint will be accessible.
    |
    */
    'route' => env('HEALTH_ROUTE', '/health'),

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | The header name and value your monitoring tool sends with each request.
    | Required, the endpoint will return 404 until this is set.
    |
    | When using Semonto, set the header name to match what you configured
    | in your Semonto server health monitoring settings.
    |
    */
    'auth' => [
        'health_check_header_name' => env('HEALTH_CHECK_HEADER_NAME', 'health-monitor-access-key'),
        'health_check_secret'      => env('HEALTH_CHECK_SECRET', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTPS
    |--------------------------------------------------------------------------
    |
    | When enabled, the health endpoint will only respond to HTTPS requests.
    | Strongly recommended in production. Set to false for local development.
    |
    */
    'require_https' => env('HEALTH_REQUIRE_HTTPS', true),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Results are cached to prevent the health endpoint itself from becoming
    | a source of load on your server. Set to 0 to disable caching.
    |
    */
    'cache_ttl' => env('HEALTH_CACHE_TTL', 30),

    /*
    |--------------------------------------------------------------------------
    | Checks
    |--------------------------------------------------------------------------
    |
    | Register the health checks to run. Each check must be wrapped in a
    | closure, see the "Why closures?" section in the README.
    |
    | Run `df -P` and `df -iP` on your server to list available mount points.
    |
    */
    'checks' => [
        fn() => new DiskSpaceCheck(path: '/'),
        fn() => new DiskSpaceCheck(path: '/var', warningThreshold: 75, errorThreshold: 90),
        fn() => new DiskSpaceInodeCheck(path: '/'),
        fn() => new MemoryCheck(warningThreshold: 75, errorThreshold: 90),
        fn() => new CpuLoadCheck(minutes: 1,  warningThreshold: 70, errorThreshold: 90),
        fn() => new CpuLoadCheck(minutes: 5,  warningThreshold: 60, errorThreshold: 80),
        fn() => new CpuLoadCheck(minutes: 15, warningThreshold: 50, errorThreshold: 70),
        fn() => new DatabaseConnectionCheck(connection: 'mysql'),
        fn() => new DatabaseConnectionCountCheck(connection: 'mysql', warningThreshold: 75, errorThreshold: 90),
    ],

];
