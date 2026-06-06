<?php

namespace JTKalkman\LaravelHealth\HealthChecks;

use JTKalkman\LaravelHealth\HealthCheckResult;
use JTKalkman\LaravelHealth\HealthCheckStatus;
use JTKalkman\LaravelHealth\Support\Formatter;

final class MemoryCheck extends HealthCheck
{
    protected string $name = 'Memory usage';

    public function __construct(
        protected int $warningThreshold = 75,
        protected int $errorThreshold = 90,
        ?string $name = null,
    ) {
        if ($name !== null) {
            $this->name = $name;
        }
    }

    protected function isAvailable(): bool
    {
        return is_readable('/proc/meminfo');
    }

    protected function performHealthCheck(): HealthCheckResult
    {
        if ($this->warningThreshold >= $this->errorThreshold) {
            return new HealthCheckResult(
                name: $this->name,
                status: HealthCheckStatus::ERROR->value,
                description: 'Warning threshold must be less than error threshold.',
            );
        }

        $meminfo = file_get_contents('/proc/meminfo');

        if ($meminfo === false) {
            return new HealthCheckResult(
                name: $this->name,
                status: HealthCheckStatus::ERROR->value,
                description: 'Could not read /proc/meminfo.',
            );
        }

        $total     = $this->parseMeminfo($meminfo, 'MemTotal');
        $available = $this->parseMeminfo($meminfo, 'MemAvailable');

        if ($total === null || $available === null || $total === 0) {
            return new HealthCheckResult(
                name: $this->name,
                status: HealthCheckStatus::ERROR->value,
                description: 'Could not parse memory information.',
            );
        }

        $used           = $total - $available;
        $usedPercentage = round(($used / $total) * 100, 1);

        $status = match (true) {
            $usedPercentage >= $this->errorThreshold   => HealthCheckStatus::ERROR->value,
            $usedPercentage >= $this->warningThreshold => HealthCheckStatus::WARNING->value,
            default                                    => HealthCheckStatus::OK->value,
        };

        return new HealthCheckResult(
            name: $this->name,
            status: $status,
            value: $usedPercentage,
            description: Formatter::percentage($usedPercentage) . " used.",
        );
    }

    private function parseMeminfo(string $meminfo, string $key): ?int
    {
        if (preg_match("/^{$key}:\s+(\d+)\s+kB/m", $meminfo, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
