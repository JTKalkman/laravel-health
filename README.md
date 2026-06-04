# Laravel Health

A lightweight, configurable Laravel package that exposes a health check endpoint with [Semonto server health monitoring](https://semonto.com/feature/server-health-monitoring) compatible JSON output.

No GUI, no bloat, just a clean JSON endpoint that tells you and your monitoring tools whether your server is healthy.

## Requirements

- PHP 8.3+
- Laravel 12 or 13

## Installation

```bash
composer require jtkalkman/laravel-health
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=health-config
```

## Configuration

The published `config/health.php` is the single place to configure everything, which checks to run, thresholds, authentication, caching, and the endpoint route.

### Example `config/health.php`

```php
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
    */
    'route' => env('HEALTH_ROUTE', '/health'),

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | The header name and value your monitoring tool sends with each request.
    | Leave 'health_check_secret' null to disable authentication, not
    | recommended in production.
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
```

### Environment variables

Add these to your `.env`:

```env
HEALTH_CHECK_SECRET=your-secret-key
HEALTH_REQUIRE_HTTPS=true

# Optional overrides
HEALTH_ROUTE=/health
HEALTH_CHECK_HEADER_NAME=health-monitor-access-key
HEALTH_CACHE_TTL=30
```

For local development:

```env
HEALTH_REQUIRE_HTTPS=false
HEALTH_CHECK_SECRET=my-test-secret
```

## Finding your mount points

Run the following commands on your server to list available mount points before configuring disk checks:

```bash
# Disk space
df -P

# Inode usage
df -iP
```

Add a `DiskSpaceCheck` and/or `DiskSpaceInodeCheck` for each mount point you want to monitor.

## Built-in checks

| Check | Description | `value` |
|---|---|---|
| `DiskSpaceCheck` | Disk space usage for a mount point | % used |
| `DiskSpaceInodeCheck` | Inode usage for a mount point | % used |
| `MemoryCheck` | System memory usage | % used |
| `CpuLoadCheck` | CPU load average (1, 5, or 15 minutes) as % of total capacity | % of capacity |
| `DatabaseConnectionCheck` | Database connection and query response time | seconds |
| `DatabaseConnectionCountCheck` | Active DB connections as % of max (MySQL/MariaDB/PostgreSQL) | % used |

### CPU load and core count

CPU load thresholds are expressed as a percentage of total CPU capacity. A load of `1.0` per core equals 100% utilisation, so on a 4-core server, a load of `4.0` is 100%. The package detects your core count automatically, so you configure thresholds as percentages regardless of the server's core count:

```php
// Conservative thresholds, sustained load is more concerning than spikes
fn() => new CpuLoadCheck(minutes: 1,  warningThreshold: 70, errorThreshold: 90),  // spikes OK
fn() => new CpuLoadCheck(minutes: 5,  warningThreshold: 60, errorThreshold: 80),
fn() => new CpuLoadCheck(minutes: 15, warningThreshold: 50, errorThreshold: 70),  // sustained load
```

### Multiple database connections

Laravel supports multiple database connections. Add a check for each one you want to monitor:

```php
fn() => new DatabaseConnectionCheck(connection: 'mysql'),
fn() => new DatabaseConnectionCheck(connection: 'pgsql'),
fn() => new DatabaseConnectionCheck(connection: 'tenant'),
```

## Custom checks

Extend the `HealthCheck` abstract class anywhere in your application, no need to touch the vendor folder:

```php
<?php

namespace App\HealthChecks;

use JTKalkman\LaravelHealth\HealthChecks\HealthCheck;
use JTKalkman\LaravelHealth\HealthCheckResult;
use JTKalkman\LaravelHealth\HealthCheckStatus;

class RedisCheck extends HealthCheck
{
    protected string $name = 'Redis ping';

    protected function performHealthCheck(): HealthCheckResult
    {
        $start = microtime(true);
        \Illuminate\Support\Facades\Redis::ping();
        $duration = round(microtime(true) - $start, 3);

        return new HealthCheckResult(
            name: $this->name,
            status: HealthCheckStatus::OK->value,
            value: $duration,
            description: "Redis responded in {$duration}s.",
        );
    }
}
```

Register it in `config/health.php`:

```php
'checks' => [
    // ...
    fn() => new \App\HealthChecks\RedisCheck(),
],
```

The abstract class handles all error cases, if `performHealthCheck()` throws for any reason, the check returns an `error` result automatically. Your check only needs to handle the happy path.

## Why closures?

Checks are registered as closures rather than direct instances:

```php
// Correct
fn() => new DiskSpaceCheck(path: '/'),

// Avoid
new DiskSpaceCheck(path: '/'),
```

Instantiating checks directly in the config array would cause them to be constructed on every request during Laravel's bootstrap cycle, regardless of whether the health endpoint was hit.

Closures ensure checks are only instantiated when the health endpoint is actually called.

## JSON response

The endpoint returns a JSON response compatible with [Semonto server health monitoring](https://semonto.com/feature/server-health-monitoring):

```json
{
    "results": [
        {
            "name": "Disk space /",
            "description": "/ 9% used.",
            "value": 9,
            "status": "ok"
        },
        {
            "name": "Memory usage",
            "description": "19.6% used.",
            "value": 19.6,
            "status": "ok"
        }
    ],
    "status": "ok"
}
```

### Status values

| Status | Meaning |
|---|---|
| `ok` | Check passed |
| `warning` | Check passed but approaching threshold |
| `critic` | Check failed at warning level |
| `error` | Check failed or could not run |

The top-level `status` reflects the worst status across all checks. The HTTP response code is `200` for `ok` and `503` for anything else.

## Security

- The endpoint returns `404` for any request that fails authentication or HTTPS requirements, no information is leaked about the endpoint's existence
- Secret key comparison uses `hash_equals()` to prevent timing attacks
- Results are cached to prevent the endpoint from being used as a DoS vector
- Always use HTTPS in production, the secret key travels in a request header

## Semonto compatibility

This package produces a standard JSON health endpoint that is compatible with [Semonto server health monitoring](https://semonto.com). The endpoint can also be consumed by any monitoring tool, custom dashboard, or deployment pipeline that can make an HTTP request and parse JSON.

To connect Semonto to your health endpoint, add the endpoint URL in your Semonto server health monitoring settings and configure an access key. Set the same key as `HEALTH_CHECK_SECRET` in your .env and make sure `HEALTH_CHECK_HEADER_NAME` matches the header name configured in Semonto, which defaults to `health-monitor-access-key`.

## License

MIT License

Copyright (c) 2025 J.T. Kalkman
