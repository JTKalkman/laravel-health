<?php

namespace JTKalkman\LaravelHealth\Tests;

use JTKalkman\LaravelHealth\HealthServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class LaravelTestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            HealthServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);
    }
}
