<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Services;

use IzAhmad\TurboSeeder\Enums\DatabaseDriver;

final class SqlIdentifier
{
    /**
     * Quote a table name for the given driver, supporting schema.table notation.
     * Each part is quoted independently so schema.table becomes "schema"."table"
     * or `schema`.`table` for MySQL.
     */
    public static function quoteTable(string $table, DatabaseDriver $driver): string
    {
        $parts = explode('.', $table, 2);

        if ($driver === DatabaseDriver::MYSQL) {
            return implode('.', array_map(static fn (string $p) => "`{$p}`", $parts));
        }

        return implode('.', array_map(static fn (string $p) => "\"{$p}\"", $parts));
    }
}
