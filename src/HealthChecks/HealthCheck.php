<?php

namespace JTKalkman\LaravelHealth\HealthChecks;

use JTKalkman\LaravelHealth\HealthCheckResult;

abstract class HealthCheck
{
    protected string $name = 'Health check';

    abstract protected function performHealthCheck(): HealthCheckResult;

    public function run(): HealthCheckResult
    {
        try {
            return $this->performHealthCheck();
        } catch (\Throwable $th) {
            return new HealthCheckResult(
                name: $this->name,
                status: 'error',
                description: $th->getMessage(),
            );
        }
    }
}
