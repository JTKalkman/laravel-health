<?php

namespace JTKalkman\LaravelHealth;

class HealthCheckResult
{
    public function __construct(
        public readonly string $name,
        public readonly string $status,
        public readonly ?float $value = null,
        public readonly ?string $description = null,
    ) {}
}