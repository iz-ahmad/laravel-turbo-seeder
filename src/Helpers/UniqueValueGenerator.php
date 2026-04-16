<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Helpers;

use Illuminate\Support\Str;

/**
 * Generates unique values for seeded columns.
 *
 * @deprecated Use TurboData::uniqueEmail(), TurboData::uniqueUsername(), or TurboData::uniqueUuid() instead.
 */
final class UniqueValueGenerator
{
    /**
     * Generate a unique email address.
     */
    public static function uniqueEmail(?string $prefix = null): \Closure
    {
        $prefix = $prefix ?? 'user';
        $timestamp = time();
        $randomStr = Str::random(4);

        return fn ($index) => "{$prefix}{$index}_{$timestamp}_{$randomStr}@test.com";
    }

    /**
     * Generate a unique value for any column.
     */
    public static function uniqueValue(?string $prefix = null): \Closure
    {
        $prefix = $prefix ?? 'unique';
        $timestamp = time();
        $randomStr = Str::random(4);

        return fn ($index) => "{$prefix}_{$index}_{$timestamp}_{$randomStr}";
    }

    /**
     * Generate a unique UUID-based value.
     */
    public static function uniqueUuid(string $prefix = ''): \Closure
    {
        return fn () => $prefix.Str::uuid()->toString();
    }
}
