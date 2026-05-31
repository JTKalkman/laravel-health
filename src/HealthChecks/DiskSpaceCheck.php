<?php

namespace JTKalkman\LaravelHealth\HealthChecks;

use JTKalkman\LaravelHealth\HealthCheckResult;
use JTKalkman\LaravelHealth\HealthCheckStatus;

final class DiskSpaceCheck extends HealthCheck
{
    public function __construct(
        protected string $path = '/',
        protected int $warningThreshold = 75,
        protected int $errorThreshold = 90,
        ?string $name = null,
    ) {
        $this->name = $name ?? "Disk space {$path}";
    }

    protected function isAvailable(): bool
    {
        return $this->canExec() || (function_exists('disk_free_space') && function_exists('disk_total_space'));
    }

    private function buildResult(float $usedPercentage): HealthCheckResult
    {
        $status = match (true) {
            $usedPercentage >= $this->errorThreshold   => HealthCheckStatus::ERROR->value,
            $usedPercentage >= $this->warningThreshold => HealthCheckStatus::WARNING->value,
            default                                    => HealthCheckStatus::OK->value,
        };
 
        $description = "{$this->path} {$usedPercentage}% used.";
 
        return new HealthCheckResult(
            name: $this->name,
            status: $status,
            value: $usedPercentage,
            description: $description,
        );
    }

    private function runWithExec(): HealthCheckResult
    {
        exec("df -P " . escapeshellarg($this->path) . " 2>&1", $output, $returnCode);
 
        if ($returnCode !== 0 || count($output) < 2) {
            return new HealthCheckResult(
                name: $this->name,
                status: HealthCheckStatus::ERROR->value,
                description: "Failed to retrieve disk space for {$this->path}.",
            );
        }
 
        // Second line contains the data, columns are:
        // Filesystem 1024-blocks Used Available Capacity% Mounted
        $columns = preg_split('/\s+/', trim($output[1]));
        $usedPercentage = (float) rtrim($columns[4], '%');
 
        return $this->buildResult($usedPercentage);
    }

    private function runWithNative(): HealthCheckResult
    {
        $free = disk_free_space($this->path);
        $total = disk_total_space($this->path);
 
        if ($free === false || $total === false || $total === 0.0) {
            return new HealthCheckResult(
                name: $this->name,
                status: HealthCheckStatus::ERROR->value,
                description: "Could not retrieve disk space for {$this->path}.",
            );
        }
 
        $usedPercentage = round((($total - $free) / $total) * 100, 1);
 
        return $this->buildResult($usedPercentage);
    }

    protected function performHealthCheck(): HealthCheckResult
    {
        if (!is_dir($this->path)) {
            return new HealthCheckResult(
                name: $this->name,
                status: HealthCheckStatus::ERROR->value,
                description: "Path {$this->path} not found.",
            );
        }

        if ($this->warningThreshold >= $this->errorThreshold) {
            return new HealthCheckResult(
                name: $this->name,
                status: HealthCheckStatus::ERROR->value,
                description: "Warning threshold must be less than error threshold.",
            );
        }

        return $this->canExec()
            ? $this->runWithExec()
            : $this->runWithNative();
    }
}
