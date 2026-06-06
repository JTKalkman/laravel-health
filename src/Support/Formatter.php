<?php

namespace JTKalkman\LaravelHealth\Support;

class Formatter
{
    /**
     * Format a float to the specified number of decimal places, 
     * stripping trailing zeros.
     *
     * Examples:
     *   9.0   -> "9"
     *   19.6  -> "19.6"
     *   6.50  -> "6.5"
     *   1.42333984375 -> "1.42"
     */
    public static function number(float $value, int $decimals = 2): string
    {
        return rtrim(rtrim(number_format($value, $decimals, '.', ''), '0'), '.');
    }

    /**
     * Format a float as a percentage, stripping trailing zeros.
     *
     * Examples:
     *   9.0   -> "9%"
     *   19.6  -> "19.6%"
     *   100.0 -> "100%"
     */
    public static function percentage(float $value): string
    {
        return self::number($value) . '%';
    }

    /**
     * Format a duration in seconds to at most 3 decimal places.
     *
     * Examples:
     *   0.001 -> "0.001s"
     *   0.0   -> "0s"
     *   1.5   -> "1.5s"
     */
    public static function seconds(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.') . 's';
    }
}
