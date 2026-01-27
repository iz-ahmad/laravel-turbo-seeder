<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Helpers;

use Illuminate\Support\Str;

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

        $generator = fn ($index) => "{$prefix}{$index}_{$timestamp}_{$randomStr}@test.com";

        return $generator;
    }

    /**
     * Generate a unique value for any column.
     */
    public static function uniqueValue(?string $prefix = null): \Closure
    {
        $prefix = $prefix ?? 'unique';
        $timestamp = time();
        $randomStr = Str::random(4);

        $generator = fn ($index) => "{$prefix}_{$index}_{$timestamp}_{$randomStr}";

        return $generator;
    }

    /**
     * Generate a unique UUID-based value.
     */
    public static function uniqueUuid(string $prefix = ''): \Closure
    {
        return fn () => $prefix.Str::uuid()->toString();
    }
}
