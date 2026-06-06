<?php

namespace JTKalkman\LaravelHealth\Tests\HealthChecks;

use JTKalkman\LaravelHealth\HealthCheckStatus;
use JTKalkman\LaravelHealth\HealthChecks\CpuLoadCheck;
use PHPUnit\Framework\TestCase;

class CpuLoadCheckTest extends TestCase
{
    // -------------------------------------------------------------------------
    // isAvailable()
    // -------------------------------------------------------------------------

    public function test_is_available_on_linux(): void
    {
        $check = new CpuLoadCheck();
        $result = $check->run();

        if (!is_readable('/proc/cpuinfo') || (!function_exists('sys_getloadavg') && !is_readable('/proc/loadavg'))) {
            $this->assertEquals(HealthCheckStatus::ERROR->value, $result->status);
            $this->assertEquals('Check not available on this system.', $result->description);
        } else {
            $this->assertNotEquals('Check not available on this system.', $result->description);
        }
    }

    // -------------------------------------------------------------------------
    // Name generation
    // -------------------------------------------------------------------------

    public function test_auto_generates_singular_name_for_1_minute(): void
    {
        $check = new CpuLoadCheck(minutes: 1);
        $this->assertEquals('CPU load 1 minute', $check->run()->name);
    }

    public function test_auto_generates_plural_name_for_5_minutes(): void
    {
        $check = new CpuLoadCheck(minutes: 5);
        $this->assertEquals('CPU load 5 minutes', $check->run()->name);
    }

    public function test_auto_generates_plural_name_for_15_minutes(): void
    {
        $check = new CpuLoadCheck(minutes: 15);
        $this->assertEquals('CPU load 15 minutes', $check->run()->name);
    }

    public function test_accepts_custom_name(): void
    {
        $check = new CpuLoadCheck(minutes: 1, name: 'Server load');
        $this->assertEquals('Server load', $check->run()->name);
    }

    // -------------------------------------------------------------------------
    // Invalid configuration
    // -------------------------------------------------------------------------

    public function test_returns_error_when_warning_threshold_exceeds_error_threshold(): void
    {
        $check = new CpuLoadCheck(warningThreshold: 90, errorThreshold: 75);
        $result = $check->run();

        $this->assertEquals(HealthCheckStatus::ERROR->value, $result->status);
        $this->assertEquals('Warning threshold must be less than error threshold.', $result->description);
        $this->assertNull($result->value);
    }

    public function test_returns_error_when_thresholds_are_equal(): void
    {
        $check = new CpuLoadCheck(warningThreshold: 75, errorThreshold: 75);
        $result = $check->run();

        $this->assertEquals(HealthCheckStatus::ERROR->value, $result->status);
        $this->assertEquals('Warning threshold must be less than error threshold.', $result->description);
        $this->assertNull($result->value);
    }

    public function test_returns_error_for_invalid_minutes(): void
    {
        $check = new CpuLoadCheck(minutes: 10);
        $result = $check->run();

        $this->assertEquals(HealthCheckStatus::ERROR->value, $result->status);
        $this->assertEquals('Minutes must be 1, 5, or 15.', $result->description);
        $this->assertNull($result->value);
    }

    // -------------------------------------------------------------------------
    // Result: only runs on Linux with /proc/cpuinfo and load avg available
    // -------------------------------------------------------------------------

    public function test_returns_valid_status(): void
    {
        if (!is_readable('/proc/cpuinfo') || !function_exists('sys_getloadavg')) {
            $this->markTestSkipped('CPU load check not available on this system.');
        }

        $check = new CpuLoadCheck(minutes: 1);
        $result = $check->run();

        $this->assertContains($result->status, [
            HealthCheckStatus::OK->value,
            HealthCheckStatus::WARNING->value,
            HealthCheckStatus::ERROR->value,
        ]);
    }

    public function test_returns_percentage_as_value(): void
    {
        if (!is_readable('/proc/cpuinfo') || !function_exists('sys_getloadavg')) {
            $this->markTestSkipped('CPU load check not available on this system.');
        }

        $check = new CpuLoadCheck(minutes: 1);
        $result = $check->run();

        $this->assertNotNull($result->value);
        $this->assertGreaterThanOrEqual(0, $result->value);
    }

    public function test_returns_ok_when_below_warning_threshold(): void
    {
        if (!is_readable('/proc/cpuinfo') || !function_exists('sys_getloadavg')) {
            $this->markTestSkipped('CPU load check not available on this system.');
        }

        $check = new CpuLoadCheck(minutes: 1, warningThreshold: 99, errorThreshold: 100);
        $result = $check->run();

        $this->assertEquals(HealthCheckStatus::OK->value, $result->status);
    }

    public function test_returns_warning_when_above_warning_threshold(): void
    {
        if (!is_readable('/proc/cpuinfo') || !function_exists('sys_getloadavg')) {
            $this->markTestSkipped('CPU load check not available on this system.');
        }

        $check = new CpuLoadCheck(minutes: 1, warningThreshold: 0, errorThreshold: 100);
        $result = $check->run();

        $this->assertEquals(HealthCheckStatus::WARNING->value, $result->status);
    }

    public function test_returns_error_when_above_error_threshold(): void
    {
        if (!is_readable('/proc/cpuinfo') || !function_exists('sys_getloadavg')) {
            $this->markTestSkipped('CPU load check not available on this system.');
        }

        $check = new CpuLoadCheck(minutes: 1, warningThreshold: 0, errorThreshold: 1);
        $result = $check->run();

        $this->assertEquals(HealthCheckStatus::ERROR->value, $result->status);
    }
}
