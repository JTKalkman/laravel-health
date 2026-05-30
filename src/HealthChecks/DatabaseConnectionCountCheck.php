<?php

namespace JTKalkman\LaravelHealth\HealthChecks;

use Illuminate\Support\Facades\DB;
use JTKalkman\LaravelHealth\HealthCheckResult;
use JTKalkman\LaravelHealth\HealthCheckStatus;

final class DatabaseConnectionCountCheck extends HealthCheck
{
    public function __construct(
        protected string $connection = 'mysql',
        protected int $warningThreshold = 75,
        protected int $errorThreshold = 90,
        ?string $name = null,
    ) {
        $this->name = $name ?? "Database connections {$this->connection}";
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

        if ($this->warningThreshold >= $this->errorThreshold) {
            return new HealthCheckResult(
                name: $this->name,
                status: HealthCheckStatus::ERROR->value,
                description: 'Warning threshold must be less than error threshold.',
            );
        }

        $driver = config("database.connections.{$this->connection}.driver");

        return match ($driver) {
            'mysql', 'mariadb' => $this->checkMysql(),
            'pgsql'            => $this->checkPgsql(),
            default            => new HealthCheckResult(
                name: $this->name,
                status: HealthCheckStatus::ERROR->value,
                description: "Unsupported database driver '{$driver}'.",
            ),
        };
    }

    private function checkMysql(): HealthCheckResult
    {
        $maxConnections = DB::connection($this->connection)
            ->selectOne("SHOW VARIABLES LIKE 'max_connections'")
            ?->Value;

        $currentConnections = DB::connection($this->connection)
            ->selectOne("SHOW STATUS LIKE 'Threads_connected'")
            ?->Value;

        return $this->buildResult((int) $currentConnections, (int) $maxConnections);
    }

    private function checkPgsql(): HealthCheckResult
    {
        $maxConnections = DB::connection($this->connection)
            ->selectOne("SELECT setting::int AS value FROM pg_settings WHERE name = 'max_connections'")
            ?->value;

        $currentConnections = DB::connection($this->connection)
            ->selectOne('SELECT COUNT(*) AS value FROM pg_stat_activity')
            ?->value;

        return $this->buildResult((int) $currentConnections, (int) $maxConnections);
    }

    private function buildResult(int $current, int $max): HealthCheckResult
    {
        if ($max === 0) {
            return new HealthCheckResult(
                name: $this->name,
                status: HealthCheckStatus::ERROR->value,
                description: 'Could not retrieve maximum connection count.',
            );
        }

        $percentage = round(($current / $max) * 100, 1);

        $status = match (true) {
            $percentage >= $this->errorThreshold   => HealthCheckStatus::ERROR->value,
            $percentage >= $this->warningThreshold => HealthCheckStatus::WARNING->value,
            default                                => HealthCheckStatus::OK->value,
        };

        return new HealthCheckResult(
            name: $this->name,
            status: $status,
            value: $percentage,
            description: "{$percentage}% used ({$current}/{$max} connections).",
        );
    }
}
