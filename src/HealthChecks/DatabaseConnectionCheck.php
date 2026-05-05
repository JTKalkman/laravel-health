<?php

namespace JTKalkman\LaravelHealth\HealthChecks;

use Illuminate\Support\Facades\DB;
use JTKalkman\LaravelHealth\HealthCheckResult;
use JTKalkman\LaravelHealth\HealthCheckStatus;

final class DatabaseConnectionCheck extends HealthCheck
{
    public function __construct(
        protected string $connection = 'mysql',
        ?string $name = null,
    ) {
        $this->name = $name ?? "Database {$this->connection}";
    }

    protected function performHealthCheck(): HealthCheckResult
    {
        if (!array_key_exists($this->connection, config('database.connections', []))) {
            return new HealthCheckResult(
                name: $this->name,
                status: HealthCheckStatus::ERROR->value,
                description: "Database connection '{$this->connection}' is not configured.",
            );
        }

        $start = microtime(true);

        DB::connection($this->connection)->select('SELECT 1');

        $value = round(microtime(true) - $start, 3);

        return new HealthCheckResult(
            name: $this->name,
            status: HealthCheckStatus::OK->value,
            value: $value,
            description: "Connected in {$value}s.",
        );
    }
}
