<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Services;

use IzAhmad\TurboSeeder\Enums\DatabaseDriver;

final class SqlIdentifier
{
    public static function assertSafePrefix(string $prefix): void
    {
        if (preg_match('/^[A-Za-z0-9_]*$/D', $prefix) !== 1) {
            throw new \InvalidArgumentException(
                "Invalid table prefix [{$prefix}]. Table prefixes may only contain letters, digits, and underscores."
            );
        }
    }

    public static function quoteTable(string $table, DatabaseDriver $driver, string $prefix = ''): string
    {
        self::assertSafePrefix($prefix);

        $parts = explode('.', $table, 2);
        $last = count($parts) - 1;
        $parts[$last] = $prefix.$parts[$last];

        if ($driver === DatabaseDriver::MYSQL) {
            return implode('.', array_map(static fn (string $p) => '`'.str_replace('`', '``', $p).'`', $parts));
        }

        return implode('.', array_map(static fn (string $p) => '"'.str_replace('"', '""', $p).'"', $parts));
    }

    public static function quoteColumn(string $column, DatabaseDriver $driver): string
    {
        if ($driver === DatabaseDriver::MYSQL) {
            return '`'.str_replace('`', '``', $column).'`';
        }

        return '"'.str_replace('"', '""', $column).'"';
    }
}
