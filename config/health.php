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
    | The header name and value Semonto (or any monitor) will send with each
    | request. Leave 'secret' null to disable authentication entirely,
    | not recommended in production.
    |
    */

    'auth' => [
        'health_check_header_name' => env('HEALTH_CHECK_HEADER_NAME', 'health-monitor-access-key'),
        'health_check_secret' => env('HEALTH_CHECK_SECRET', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTPS
    |--------------------------------------------------------------------------
    |
    | When enabled, the health endpoint will only respond to HTTPS requests.
    | Strongly recommended in production.
    |
    */

    'require_https' => env('HEALTH_REQUIRE_HTTPS', true),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Results are cached to prevent flooding the server with expensive checks
    | on every request. Set to 0 to disable caching.
    |
    */

    'cache_ttl' => env('HEALTH_CACHE_TTL', 30),

    /*
    |--------------------------------------------------------------------------
    | Checks
    |--------------------------------------------------------------------------
    |
    | Register the health checks you want to run. Each check is an instance
    | of a class extending JTKalkman\LaravelHealth\HealthChecks\HealthCheck.
    | Custom checks can be added anywhere in your application.
    |
    | Example:
    |   fn() => new DiskSpaceCheck(path: '/', warningThreshold: 75, errorThreshold: 90),
    |   fn() => new DiskSpaceInodeCheck(path: '/'),
    |   fn() => new \App\HealthChecks\RedisCheck(),
    |
    | Why closures?
    | Health checks are registered as closures rather than instantiated objects 
    | to ensure they are only created when the health endpoint is actually called. 
    | Instantiating checks directly in the config array would cause them to be 
    | constructed on every request during Laravel's bootstrap cycle, regardless 
    | of whether the health endpoint was hit.
    */

    'checks' => [
        fn() => new DiskSpaceCheck(path: '/'),
        fn() => new DiskSpaceInodeCheck(path: '/'),
        fn() => new DatabaseConnectionCheck(connection: 'mysql'),
        fn() => new DatabaseConnectionCountCheck(connection: 'mysql'),
        fn() => new MemoryCheck(warningThreshold: 50, errorThreshold: 75),
        fn() => new CpuLoadCheck(minutes: 1),
        fn() => new CpuLoadCheck(minutes: 5),
        fn() => new CpuLoadCheck(minutes: 15),
    ],
];
