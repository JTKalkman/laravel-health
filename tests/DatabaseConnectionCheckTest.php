<?php

namespace JTKalkman\LaravelHealth\Tests\HealthChecks;

use JTKalkman\LaravelHealth\HealthCheckStatus;
use JTKalkman\LaravelHealth\HealthChecks\DatabaseConnectionCheck;
use JTKalkman\LaravelHealth\HealthServiceProvider;
use JTKalkman\LaravelHealth\Tests\LaravelTestCase;

class DatabaseConnectionCheckTest extends LaravelTestCase
{
    // -------------------------------------------------------------------------
    // Misconfigured connection
    // -------------------------------------------------------------------------

    public function test_returns_error_for_unconfigured_connection(): void
    {
        $check = new DatabaseConnectionCheck(connection: 'doesnotexist');
        $result = $check->run();

        $this->assertEquals(HealthCheckStatus::ERROR->value, $result->status);
        $this->assertNull($result->value);
        $this->assertEquals("Database connection 'doesnotexist' is not configured.", $result->description);
    }

    // -------------------------------------------------------------------------
    // Name generation
    // -------------------------------------------------------------------------

    public function test_auto_generates_name_from_connection(): void
    {
        $check = new DatabaseConnectionCheck(connection: 'sqlite');
        $result = $check->run();

        $this->assertEquals('Database sqlite', $result->name);
    }

    public function test_accepts_custom_name(): void
    {
        $check = new DatabaseConnectionCheck(connection: 'sqlite', name: 'Primary database');
        $result = $check->run();

        $this->assertEquals('Primary database', $result->name);
    }

    // -------------------------------------------------------------------------
    // Successful connection
    // -------------------------------------------------------------------------

    public function test_returns_ok_for_valid_connection(): void
    {
        $check = new DatabaseConnectionCheck(connection: 'sqlite');
        $result = $check->run();

        $this->assertEquals(HealthCheckStatus::OK->value, $result->status);
    }

    public function test_returns_response_time_as_value(): void
    {
        $check = new DatabaseConnectionCheck(connection: 'sqlite');
        $result = $check->run();

        $this->assertNotNull($result->value);
        $this->assertGreaterThanOrEqual(0, $result->value);
    }

    public function test_value_is_rounded_to_three_decimals(): void
    {
        $check = new DatabaseConnectionCheck(connection: 'sqlite');
        $result = $check->run();

        $this->assertEquals($result->value, round($result->value, 3));
    }

    public function test_description_contains_response_time(): void
    {
        $check = new DatabaseConnectionCheck(connection: 'sqlite');
        $result = $check->run();

        $this->assertStringContainsString('Connected in', $result->description);
        $this->assertStringContainsString('s.', $result->description);
    }
}
