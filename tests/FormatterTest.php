<?php

namespace JTKalkman\LaravelHealth\Tests\Support;

use JTKalkman\LaravelHealth\Support\Formatter;
use PHPUnit\Framework\TestCase;

class FormatterTest extends TestCase
{
    // -------------------------------------------------------------------------
    // number()
    // -------------------------------------------------------------------------

    public function test_number_strips_trailing_zeros(): void
    {
        $this->assertEquals('9', Formatter::number(9.0));
    }

    public function test_number_keeps_single_decimal(): void
    {
        $this->assertEquals('19.6', Formatter::number(19.6));
    }

    public function test_number_keeps_two_decimals(): void
    {
        $this->assertEquals('1.42', Formatter::number(1.42));
    }

    public function test_number_rounds_down_to_two_decimals(): void
    {
        $this->assertEquals('1.42', Formatter::number(1.42333984375));
    }

    public function test_number_rounds_up_to_two_decimals(): void
    {
        $this->assertEquals('1.43', Formatter::number(1.42533984375));
    }

    public function test_number_handles_zero(): void
    {
        $this->assertEquals('0', Formatter::number(0.0));
    }

    public function test_number_handles_whole_number(): void
    {
        $this->assertEquals('100', Formatter::number(100.0));
    }

    // -------------------------------------------------------------------------
    // percentage()
    // -------------------------------------------------------------------------

    public function test_percentage_strips_trailing_zeros(): void
    {
        $this->assertEquals('9%', Formatter::percentage(9.0));
    }

    public function test_percentage_keeps_single_decimal(): void
    {
        $this->assertEquals('19.6%', Formatter::percentage(19.6));
    }

    public function test_percentage_handles_zero(): void
    {
        $this->assertEquals('0%', Formatter::percentage(0.0));
    }

    public function test_percentage_handles_100(): void
    {
        $this->assertEquals('100%', Formatter::percentage(100.0));
    }

    public function test_percentage_rounds_to_two_decimals(): void
    {
        $this->assertEquals('6.5%', Formatter::percentage(6.50));
    }

    // -------------------------------------------------------------------------
    // seconds()
    // -------------------------------------------------------------------------

    public function test_seconds_appends_unit(): void
    {
        $this->assertEquals('0.001s', Formatter::seconds(0.001));
    }

    public function test_seconds_strips_trailing_zeros(): void
    {
        $this->assertEquals('1.5s', Formatter::seconds(1.5));
    }

    public function test_seconds_handles_zero(): void
    {
        $this->assertEquals('0s', Formatter::seconds(0.0));
    }

    public function test_seconds_keeps_three_decimals(): void
    {
        $this->assertEquals('0.002s', Formatter::seconds(0.002));
    }
}
