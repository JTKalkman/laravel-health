<?php

namespace JTKalkman\LaravelHealth;

enum HealthCheckStatus: string
{
    case OK = 'ok';
    case WARNING = 'warning';
    case ERROR = 'error';
}
