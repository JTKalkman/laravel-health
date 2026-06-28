<?php

namespace JTKalkman\LaravelHealth;

use Illuminate\Support\ServiceProvider;
use JTKalkman\LaravelHealth\Commands\HealthCheckCommand;
use JTKalkman\LaravelHealth\Commands\HealthClearCommand;

final class HealthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/health.php',
            'health'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/health.php' => config_path('health.php'),
            ], 'health-config');

            $this->commands([
                HealthClearCommand::class,
                HealthCheckCommand::class,
            ]);
        }

        $this->loadRoutesFrom(__DIR__ . '/../routes/health.php');
    }
}
