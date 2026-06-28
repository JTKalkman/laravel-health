<?php

namespace JTKalkman\LaravelHealth\Tests\HealthChecks;

use JTKalkman\LaravelHealth\HealthCheckStatus;
use JTKalkman\LaravelHealth\HealthChecks\MemoryCheck;
use PHPUnit\Framework\TestCase;

class MemoryCheckTest extends TestCase
{
    // -------------------------------------------------------------------------
    // isAvailable()
    // -------------------------------------------------------------------------

    public function test_is_available_on_linux(): void
    {
        $check = new MemoryCheck();
        $result = $check->run();

        // On Linux /proc/meminfo should be readable.
        // If this test runs on a non-Linux system it will return an error,
        // which is the correct and expected behaviour.
        if (!is_readable('/proc/meminfo')) {
            $this->assertEquals(HealthCheckStatus::ERROR, $result->status);
            $this->assertEquals('Check not available on this system.', $result->description);
        } else {
            $this->assertNotEquals('Check not available on this system.', $result->description);
        }
    }

    // -------------------------------------------------------------------------
    // Invalid configuration
    // -------------------------------------------------------------------------

    public function test_returns_error_when_warning_threshold_exceeds_error_threshold(): void
    {
        $check = new MemoryCheck(warningThreshold: 90, errorThreshold: 75);
        $result = $check->run();

        $this->assertEquals(HealthCheckStatus::ERROR, $result->status);
        $this->assertEquals('Warning threshold must be less than error threshold.', $result->description);
        $this->assertNull($result->value);
    }

    public function test_returns_error_when_thresholds_are_equal(): void
    {
        $check = new MemoryCheck(warningThreshold: 75, errorThreshold: 75);
        $result = $check->run();

        $this->assertEquals(HealthCheckStatus::ERROR, $result->status);
        $this->assertEquals('Warning threshold must be less than error threshold.', $result->description);
        $this->assertNull($result->value);
    }

    // -------------------------------------------------------------------------
    // Name
    // -------------------------------------------------------------------------

    public function test_has_default_name(): void
    {
        $check = new MemoryCheck();
        $result = $check->run();

        $this->assertEquals('Memory usage', $result->name);
    }

    public function test_accepts_custom_name(): void
    {
        $check = new MemoryCheck(name: 'RAM');
        $result = $check->run();

        $this->assertEquals('RAM', $result->name);
    }

    // -------------------------------------------------------------------------
    // Result: only runs on Linux with /proc/meminfo available
    // -------------------------------------------------------------------------

    public function test_returns_valid_status(): void
    {
        if (!is_readable('/proc/meminfo')) {
            $this->markTestSkipped('/proc/meminfo not available on this system.');
        }

        $check = new MemoryCheck();
        $result = $check->run();

        $this->assertContains($result->status, [
            HealthCheckStatus::OK,
            HealthCheckStatus::WARNING,
            HealthCheckStatus::ERROR,
        ]);
    }

    public function test_returns_percentage_between_0_and_100(): void
    {
        if (!is_readable('/proc/meminfo')) {
            $this->markTestSkipped('/proc/meminfo not available on this system.');
        }

        $check = new MemoryCheck();
        $result = $check->run();

        $this->assertNotNull($result->value);
        $this->assertGreaterThanOrEqual(0, $result->value);
        $this->assertLessThanOrEqual(100, $result->value);
    }

    public function test_returns_ok_when_below_warning_threshold(): void
    {
        if (!is_readable('/proc/meminfo')) {
            $this->markTestSkipped('/proc/meminfo not available on this system.');
        }

        $check = new MemoryCheck(warningThreshold: 99, errorThreshold: 100);
        $result = $check->run();

        $this->assertEquals(HealthCheckStatus::OK, $result->status);
    }

    public function test_returns_warning_when_above_warning_threshold(): void
    {
        if (!is_readable('/proc/meminfo')) {
            $this->markTestSkipped('/proc/meminfo not available on this system.');
        }

        $check = new MemoryCheck(warningThreshold: 0, errorThreshold: 100);
        $result = $check->run();

        $this->assertEquals(HealthCheckStatus::WARNING, $result->status);
    }

    public function test_returns_error_when_above_error_threshold(): void
    {
        if (!is_readable('/proc/meminfo')) {
            $this->markTestSkipped('/proc/meminfo not available on this system.');
        }

        $check = new MemoryCheck(warningThreshold: 0, errorThreshold: 1);
        $result = $check->run();

        $this->assertEquals(HealthCheckStatus::ERROR, $result->status);
    }
}
