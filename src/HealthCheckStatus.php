<?php

namespace JTKalkman\LaravelHealth;

enum HealthCheckStatus: string
{
    case OK = 'ok';
    case WARNING = 'warning';
    case ERROR = 'error';

    public function priority(): int
    {
        return match ($this) {
            self::OK      => 0,
            self::WARNING => 1,
            self::ERROR   => 2,
        };
    }

    public function worst(HealthCheckStatus $other): HealthCheckStatus
    {
        return $other->priority() > $this->priority()
            ? $other
            : $this;
    }
}
