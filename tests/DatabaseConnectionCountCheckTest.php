<?php

namespace JTKalkman\LaravelHealth\Tests\HealthChecks;

use JTKalkman\LaravelHealth\HealthCheckStatus;
use JTKalkman\LaravelHealth\HealthChecks\DatabaseConnectionCountCheck;
use JTKalkman\LaravelHealth\HealthServiceProvider;
use JTKalkman\LaravelHealth\Tests\LaravelTestCase;

class DatabaseConnectionCountCheckTest extends LaravelTestCase
{
    // -------------------------------------------------------------------------
    // Invalid configuration
    // -------------------------------------------------------------------------

    public function test_returns_error_when_warning_threshold_exceeds_error_threshold(): void
    {
        $check = new DatabaseConnectionCountCheck(
            connection: 'sqlite',
            warningThreshold: 90,
            errorThreshold: 75,
        );
        $result = $check->run();

        $this->assertEquals(HealthCheckStatus::ERROR, $result->status);
        $this->assertEquals('Warning threshold must be less than error threshold.', $result->description);
        $this->assertNull($result->value);
    }

    public function test_returns_error_when_thresholds_are_equal(): void
    {
        $check = new DatabaseConnectionCountCheck(
            connection: 'sqlite',
            warningThreshold: 75,
            errorThreshold: 75,
        );
        $result = $check->run();

        $this->assertEquals(HealthCheckStatus::ERROR, $result->status);
        $this->assertEquals('Warning threshold must be less than error threshold.', $result->description);
        $this->assertNull($result->value);
    }

    public function test_returns_error_for_unconfigured_connection(): void
    {
        $check = new DatabaseConnectionCountCheck(connection: 'doesnotexist');
        $result = $check->run();

        $this->assertEquals(HealthCheckStatus::ERROR, $result->status);
        $this->assertEquals("Database connection 'doesnotexist' is not configured.", $result->description);
        $this->assertNull($result->value);
    }

    // -------------------------------------------------------------------------
    // Unsupported driver
    // -------------------------------------------------------------------------

    public function test_returns_error_for_unsupported_driver(): void
    {
        // SQLite is a valid configured connection but unsupported by this check
        $check = new DatabaseConnectionCountCheck(connection: 'sqlite');
        $result = $check->run();

        $this->assertEquals(HealthCheckStatus::ERROR, $result->status);
        $this->assertStringContainsString('sqlite', $result->description);
        $this->assertNull($result->value);
    }

    // -------------------------------------------------------------------------
    // Name generation
    // -------------------------------------------------------------------------

    public function test_auto_generates_name_from_connection(): void
    {
        $check = new DatabaseConnectionCountCheck(connection: 'sqlite');
        $result = $check->run();

        $this->assertEquals('Database connections sqlite', $result->name);
    }

    public function test_accepts_custom_name(): void
    {
        $check = new DatabaseConnectionCountCheck(connection: 'sqlite', name: 'Primary DB connections');
        $result = $check->run();

        $this->assertEquals('Primary DB connections', $result->name);
    }

    // -------------------------------------------------------------------------
    // Happy path, requires a real MySQL/MariaDB connection.
    // The actual query logic (max_connections, Threads_connected)
    // cannot be meaningfully tested with SQLite in-memory. Manual 
    // testing against a real database instance is required for full 
    // coverage.
    //
    // TODO: Consider a MySQL service container in CI/CD for
    // integration tests covering the happy path and threshold behaviour.
    // -------------------------------------------------------------------------
}
