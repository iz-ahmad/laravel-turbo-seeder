<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Services;

use IzAhmad\TurboSeeder\Enums\DatabaseDriver;

final class SqlIdentifier
{
    public static function quoteTable(string $table, DatabaseDriver $driver): string
    {
        $parts = explode('.', $table, 2);

        if ($driver === DatabaseDriver::MYSQL) {
            return implode('.', array_map(static fn (string $p) => '`'.str_replace('`', '``', $p).'`', $parts));
        }

        return implode('.', array_map(static fn (string $p) => '"'.str_replace('"', '""', $p).'"', $parts));
    }
}
