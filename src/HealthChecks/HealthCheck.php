<?php

namespace JTKalkman\LaravelHealth\HealthChecks;

use JTKalkman\LaravelHealth\HealthCheckResult;
use JTKalkman\LaravelHealth\HealthCheckStatus;

abstract class HealthCheck
{
    protected string $name = 'Health check';

    public function name(): string
    {
        return $this->name;
    }

    protected function canExec(): bool
    {
        $disabled = array_map('trim', explode(',', ini_get('disable_functions')));

        return function_exists('exec') && !in_array('exec', $disabled);
    }

    protected function isAvailable(): bool
    {
        return true;
    }

    abstract protected function performHealthCheck(): HealthCheckResult;

    /**
     * Executes the health check safely. Any unexpected exception thrown by
     * performHealthCheck() is caught and returned as an error result, ensuring
     * the health endpoint always produces a valid response regardless of
     * individual check failures.
     */
    public function run(): HealthCheckResult
    {
        if (!$this->isAvailable()) {
            return new HealthCheckResult(
                name: $this->name,
                status: HealthCheckStatus::ERROR,
                description: "{$this->name} is not available on this system.",
            );
        }

        try {
            return $this->performHealthCheck();
        } catch (\Throwable $th) {
            return new HealthCheckResult(
                name: $this->name,
                status: HealthCheckStatus::ERROR,
                description: $th->getMessage(),
            );
        }
    }
}
