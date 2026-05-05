<?php

namespace JTKalkman\LaravelHealth\HealthChecks;

use JTKalkman\LaravelHealth\HealthCheckStatus;
use PHPUnit\Framework\TestCase;

class DiskSpaceInodeCheckTest extends TestCase
{
    // -------------------------------------------------------------------------
    // isAvailable()
    // -------------------------------------------------------------------------
    public function test_is_available_with_valid_path(): void
    {
        $check = new DiskSpaceInodeCheck('/');
        $result = $check->run();
 
        // If / exists and functions are available, it should not return
        // "Check not available on this system."
        $this->assertNotEquals('Check not available on this system.', $result->description);
    }

    public function test_returns_description_containing_path(): void
    {
        $check = new DiskSpaceInodeCheck('/');
        $result = $check->run();
 
        $this->assertStringContainsString('/', $result->description);
    }

    // -------------------------------------------------------------------------
    // Name generation
    // -------------------------------------------------------------------------
    public function test_auto_generates_name_from_path(): void
    {
        $check = new DiskSpaceInodeCheck('/var');
        $result = $check->run();
 
        $this->assertEquals('Disk space inodes /var', $result->name);
    }

    public function test_accepts_custom_name(): void
    {
        $check = new DiskSpaceInodeCheck(path: '/', name: 'Primary disk');
        $result = $check->run();
 
        $this->assertEquals('Primary disk', $result->name);
    }

    // -------------------------------------------------------------------------
    // Result structure
    // -------------------------------------------------------------------------
    public function test_returns_valid_status(): void
    {
        $check = new DiskSpaceInodeCheck('/');
        $result = $check->run();
 
        $this->assertContains($result->status, [
            HealthCheckStatus::OK->value,
            HealthCheckStatus::WARNING->value,
            HealthCheckStatus::ERROR->value,
        ]);
    }
 
    public function test_returns_float_value_between_0_and_100(): void
    {
        $check = new DiskSpaceInodeCheck('/');
        $result = $check->run();
 
        $this->assertNotNull($result->value);
        $this->assertGreaterThanOrEqual(0, $result->value);
        $this->assertLessThanOrEqual(100, $result->value);
    }
 
    public function test_returns_description_containing_path_in_result(): void
    {
        $check = new DiskSpaceInodeCheck('/var');
        $result = $check->run();
 
        $this->assertStringContainsString('/var', $result->description);
    }

    // -------------------------------------------------------------------------
    // Thresholds
    // -------------------------------------------------------------------------
 
    public function test_returns_ok_when_below_warning_threshold(): void
    {
        // Set thresholds impossibly high so the check always returns ok
        $check = new DiskSpaceInodeCheck(path: '/', warningThreshold: 99, errorThreshold: 100);
        $result = $check->run();
 
        $this->assertEquals(HealthCheckStatus::OK->value, $result->status);
    }
 
    public function test_returns_warning_when_above_warning_threshold(): void
    {
        // Set thresholds impossibly low so the check always returns warning
        $check = new DiskSpaceInodeCheck(path: '/', warningThreshold: 0, errorThreshold: 100);
        $result = $check->run();
 
        $this->assertEquals(HealthCheckStatus::WARNING->value, $result->status);
    }
 
    public function test_returns_error_when_above_error_threshold(): void
    {
        // Set both thresholds to 0 so the check always returns error
        $check = new DiskSpaceInodeCheck(path: '/', warningThreshold: 0, errorThreshold: 0);
        $result = $check->run();
 
        $this->assertEquals(HealthCheckStatus::ERROR->value, $result->status);
    }
    
    // -------------------------------------------------------------------------
    // Non-existent path to a disk
    // -------------------------------------------------------------------------
    public function test_returns_error_for_non_existent_path(): void
    {
        $check = new DiskSpaceInodeCheck('/this/path/does/absolutely/not/exist');
        $result = $check->run();

        $this->assertEquals(HealthCheckStatus::ERROR->value, $result->status);
        $this->assertNull($result->value);
        $this->assertEquals('Path /this/path/does/absolutely/not/exist not found.', $result->description);
    }
}
