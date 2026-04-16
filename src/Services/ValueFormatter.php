<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Services;

use Illuminate\Support\Collection;

final class ValueFormatter
{
    /** @var array<class-string, callable(mixed): mixed> */
    private static array $customFormatters = [];

    /**
     * Register a custom type formatter.
     * The callable receives the value and must return a scalar or null.
     *
     * @param  class-string  $type
     * @param  callable(mixed): mixed  $formatter
     */
    public static function extend(string $type, callable $formatter): void
    {
        self::$customFormatters[$type] = $formatter;
    }

    /**
     * Format a value for PDO binding (preserves PHP scalar types).
     * Returns null for null, int for bool, string for datetime/enum/json, scalar otherwise.
     */
    public static function format(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        foreach (self::$customFormatters as $type => $formatter) {
            if ($value instanceof $type) {
                return $formatter($value);
            }
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if ($value instanceof Collection) {
            return self::encodeJson($value->toArray());
        }

        if (is_bool($value)) {
            return (int) $value;
        }

        if (is_array($value) || is_object($value)) {
            return self::encodeJson($value);
        }

        return $value;
    }

    /**
     * Format a value as a CSV string.
     * Null values are returned as the null marker (default: \N).
     */
    public static function formatForCsv(mixed $value, string $nullMarker = '\\N'): string
    {
        if ($value === null) {
            return $nullMarker;
        }

        $formatted = self::format($value);

        if ($formatted === null) {
            return $nullMarker;
        }

        return (string) $formatted;
    }

    private static function encodeJson(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }
}
