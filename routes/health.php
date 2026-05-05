<?php

use Illuminate\Support\Facades\Route;
use JTKalkman\LaravelHealth\Http\Controllers\HealthController;
use JTKalkman\LaravelHealth\Http\Middleware\RequireHttps;
use JTKalkman\LaravelHealth\Http\Middleware\ValidateHealthCheckSecret;

Route::get(config('health.route', '/health'), HealthController::class)
    ->middleware([RequireHttps::class, ValidateHealthCheckSecret::class])
    ->name('health');
