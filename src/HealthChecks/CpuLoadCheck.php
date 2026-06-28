<?php

namespace JTKalkman\LaravelHealth\HealthChecks;

use JTKalkman\LaravelHealth\HealthCheckResult;
use JTKalkman\LaravelHealth\HealthCheckStatus;
use JTKalkman\LaravelHealth\Support\Formatter;

final class CpuLoadCheck extends HealthCheck
{
    public function __construct(
        protected int $minutes = 1,
        protected int $warningThreshold = 75,
        protected int $errorThreshold = 90,
        ?string $name = null,
    ) {
        $this->name = $name ?? "CPU load {$minutes} minute" . ($minutes > 1 ? 's' : '');
    }

    protected function isAvailable(): bool
    {
        return (function_exists('sys_getloadavg') || is_readable('/proc/loadavg'))
            && is_readable('/proc/cpuinfo');
    }

    private function getLoadAverages(): ?array
    {
        if (function_exists('sys_getloadavg')) {
            $loads = sys_getloadavg();
            return $loads !== false ? $loads : null;
        }

        if (is_readable('/proc/loadavg')) {
            $content = file_get_contents('/proc/loadavg');
            if ($content === false) {
                return null;
            }
            $loads = preg_split('/\s+/', trim($content));
            if (count($loads) < 3) {
                return null;
            }
            return array_map('floatval', array_slice($loads, 0, 3));
        }

        return null;
    }

    private function getCoreCount(): ?int
    {
        if (!is_readable('/proc/cpuinfo')) {
            return null;
        }

        $count = substr_count(file_get_contents('/proc/cpuinfo'), 'processor');
    
        return $count > 0 ? $count : null;
    }

    protected function performHealthCheck(): HealthCheckResult
    {
        if ($this->warningThreshold >= $this->errorThreshold) {
            return new HealthCheckResult(
                name: $this->name,
                status: HealthCheckStatus::ERROR,
                description: "Warning threshold must be less than error threshold.",
            );
        }

        if (!in_array($this->minutes, [1, 5, 15])) {
            return new HealthCheckResult(
                name: $this->name,
                status: HealthCheckStatus::ERROR,
                description: "Minutes must be 1, 5, or 15.",
            );
        }

        $cores = $this->getCoreCount();

        if ($cores === null) {
            return new HealthCheckResult(
                name: $this->name,
                status: HealthCheckStatus::ERROR,
                description: 'Could not determine CPU core count.',
            );
        }

        $loads = $this->getLoadAverages();

        if ($loads === null) {
            return new HealthCheckResult(
                name: $this->name,
                status: HealthCheckStatus::ERROR,
                description: 'Could not retrieve CPU load averages.',
            );
        }

        $index = match ($this->minutes) {
            1  => 0,
            5  => 1,
            15 => 2,
        };
        $load = $loads[$index];
        $percentage = round(($load / $cores) * 100, 1);

        $status = match (true) {
            $percentage >= $this->errorThreshold   => HealthCheckStatus::ERROR,
            $percentage >= $this->warningThreshold => HealthCheckStatus::WARNING,
            default                                => HealthCheckStatus::OK,
        };

        return new HealthCheckResult(
            name: $this->name,
            status: $status,
            value: $percentage,
            description: "Load: " . Formatter::number($load) . " (" . Formatter::percentage($percentage) . " of {$cores} cores).",
        );
    }
}
