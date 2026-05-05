<?php

namespace JTKalkman\LaravelHealth;

use Illuminate\Support\ServiceProvider;

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
        }

        $this->loadRoutesFrom(__DIR__ . '/../routes/health.php');
    }
}
