<?php

namespace JTKalkman\LaravelHealth\HealthChecks;

use JTKalkman\LaravelHealth\HealthCheckResult;
use JTKalkman\LaravelHealth\HealthCheckStatus;

final class DiskSpaceInodeCheck extends HealthCheck
{
    public function __construct(
        protected string $path = '/',
        protected int $warningThreshold = 75,
        protected int $errorThreshold = 90,
        ?string $name = null,
    ) {
        $this->name = $name ?? "Disk space inodes {$path}";
    }

    protected function isAvailable(): bool
    {
        return $this->canExec();
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

        exec("df -iP " . escapeshellarg($this->path) . " 2>&1", $output, $returnCode);

        if ($returnCode !== 0 || count($output) < 2) {
            return new HealthCheckResult(
                name: $this->name,
                status: HealthCheckStatus::ERROR->value,
                description: "Failed to retrieve inode usage on disk {$this->path}.",
            );
        }
 
        // Second line contains the data, columns are:
        // Filesystem Inodes IUsed IFree IUse% Mounted
        $columns = preg_split('/\s+/', trim($output[1]));
        $usedPercentage = (float) rtrim($columns[4], '%');

        $status = match (true) {
            $usedPercentage >= $this->errorThreshold   => HealthCheckStatus::ERROR->value,
            $usedPercentage >= $this->warningThreshold => HealthCheckStatus::WARNING->value,
            default                                    => HealthCheckStatus::OK->value,
        };

        $description = "{$this->path} {$usedPercentage}% inodes used.";
 
        return new HealthCheckResult(
            name: $this->name,
            status: $status,
            value: $usedPercentage,
            description: $description,
        );
    }
}
