<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use IzAhmad\TurboSeeder\Enums\FromTableMode;

/**
 * Fast, Faker-free data generation helpers for use inside ->generate() closures.
 *
 * All static methods are safe to call 1M+ times without performance issues.
 *
 * Randomness: randomInt() uses random_int() (CSPRNG). randomFloat(), randomBool(),
 * and weightedFrom() use mt_rand() for bulk-generation speed — not suitable for
 * security-sensitive values.
 */
final class TurboData
{
    /**
     * Pool size above which fromTable()/fromQuery() log a memory warning and
     * suggest the streaming variant.
     */
    public const POOL_WARNING_THRESHOLD = 500_000;

    private static ?string $cachedNow = null;

    /** @var array<string, string> */
    private static array $cachedHashes = [];

    /** @var array<string, int> Parsed dateRange() bounds, keyed by date string. */
    private static array $cachedDateBounds = [];

    /** @var array<string, Carbon> Parsed sequentialDate() start points. */
    private static array $cachedSequentialStarts = [];

    /**
     * Return a value by cycling through the array in round-robin order.
     *
     * @param  array<int, mixed>  $values
     */
    public static function cycleFrom(array $values): \Closure
    {
        $count = count($values);

        if ($count === 0) {
            throw new \InvalidArgumentException('cycleFrom() requires at least one value.');
        }

        return static fn (int $index) => $values[$index % $count];
    }

    /**
     * Return a random value with weighted probability.
     * Weights are relative - they don't need to sum to 100.
     *
     * Example: ['active' => 70, 'inactive' => 20, 'banned' => 10]
     *
     * @param  array<string|int, int|float>  $weights  [value => weight]
     */
    public static function weightedFrom(array $weights): mixed
    {
        foreach ($weights as $value => $weight) {
            if ($weight < 0) {
                throw new \InvalidArgumentException("weightedFrom(): weight for '{$value}' must be non-negative.");
            }
        }

        $total = array_sum($weights);

        if ($total <= 0) {
            throw new \InvalidArgumentException('weightedFrom() weights must sum to a positive number.');
        }

        $rand = (float) mt_rand() / mt_getrandmax() * $total;
        $cumulative = 0.0;

        foreach ($weights as $value => $weight) {
            if ($weight <= 0) {
                continue;
            }

            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $value;
            }
        }

        return array_key_last($weights);
    }

    /**
     * Return a uniformly random value from the array.
     *
     * @param  array<int, mixed>  $values
     */
    public static function randomFrom(array $values): mixed
    {
        if (empty($values)) {
            throw new \InvalidArgumentException('randomFrom() requires at least one value.');
        }

        return $values[array_rand($values)];
    }

    /**
     * Return a random integer between $min and $max (inclusive).
     */
    public static function randomInt(int $min, int $max): int
    {
        return random_int($min, $max);
    }

    /**
     * Return a random float rounded to $decimals places.
     */
    public static function randomFloat(int $decimals, float $min, float $max): float
    {
        $value = $min + (float) mt_rand() / mt_getrandmax() * ($max - $min);

        return round($value, $decimals);
    }

    /**
     * Return true with the given probability (0.0–1.0).
     * Default 0.5 = 50% chance of true.
     */
    public static function randomBool(float $probability = 0.5): bool
    {
        return ((float) mt_rand() / mt_getrandmax()) < $probability;
    }

    /**
     * Return null with the given probability, otherwise return the value.
     * Accepts a callable so the value is not evaluated when null is returned.
     *
     * Example: TurboData::nullable(0.15, fn() => now())
     *
     * @param  mixed|\Closure(): mixed  $produce
     */
    public static function nullable(float $probability, mixed $produce): mixed
    {
        if (((float) mt_rand() / mt_getrandmax()) < $probability) {
            return null;
        }

        return $produce instanceof \Closure ? ($produce)() : $produce;
    }

    /**
     * Return a random Carbon date between two date strings.
     * Parses $from/$to once - safe to call 1M times.
     */
    public static function dateRange(string $from, string $to): Carbon
    {
        if (! isset(self::$cachedDateBounds[$from])) {
            self::$cachedDateBounds[$from] = Carbon::parse($from)->timestamp;
        }

        if (! isset(self::$cachedDateBounds[$to])) {
            self::$cachedDateBounds[$to] = Carbon::parse($to)->timestamp;
        }

        if (self::$cachedDateBounds[$from] > self::$cachedDateBounds[$to]) {
            throw new \InvalidArgumentException(
                "dateRange() \$from [{$from}] must not be after \$to [{$to}]."
            );
        }

        $timestamp = random_int(self::$cachedDateBounds[$from], self::$cachedDateBounds[$to]);

        return Carbon::createFromTimestamp($timestamp);
    }

    /**
     * Return a Carbon date offset by $step * $index from $start.
     * Useful for sequential timestamps.
     *
     * @param  string  $step  A bare time unit: 'second', 'minute', 'hour', 'day', 'week', 'month'
     */
    public static function sequentialDate(string $start, string $step, int $index): Carbon
    {
        if (! isset(self::$cachedSequentialStarts[$start])) {
            self::$cachedSequentialStarts[$start] = Carbon::parse($start);
        }

        return self::$cachedSequentialStarts[$start]->copy()->modify("+{$index} {$step}");
    }

    /**
     * Return the current timestamp as a string, computed once and cached.
     * Use instead of now() inside generate() - avoids calling now() per record.
     */
    public static function nowOnce(): string
    {
        if (self::$cachedNow === null) {
            self::$cachedNow = now()->toDateTimeString();
        }

        return self::$cachedNow;
    }

    /**
     * Reset the cached nowOnce() value.
     * Useful in tests to prevent state leakage between test cases.
     */
    public static function resetNowOnce(): void
    {
        self::$cachedNow = null;
    }

    /**
     * Hash a password once (bcrypt) and reuse the result across all records.
     * Multiple calls with the same $password return the same cached hash.
     */
    public static function hashedPassword(string $password = 'password'): string
    {
        if (! isset(self::$cachedHashes[$password])) {
            self::$cachedHashes[$password] = password_hash($password, PASSWORD_BCRYPT);
        }

        return self::$cachedHashes[$password];
    }

    /**
     * Reset all cached hashed passwords.
     * Useful in tests to prevent state leakage between test cases.
     */
    public static function resetHashedPasswords(): void
    {
        self::$cachedHashes = [];
    }

    /**
     * Reset every cached value held by TurboData.
     * Useful in tests to prevent state leakage between test cases.
     */
    public static function reset(): void
    {
        self::$cachedNow = null;
        self::$cachedHashes = [];
        self::$cachedDateBounds = [];
        self::$cachedSequentialStarts = [];
    }

    /**
     * Pluck a column from a table once and cycle or randomly pick on every generator call.
     * Loaded lazily on first call; all subsequent calls are O(1) array lookups.
     *
     * @param  FromTableMode|string  $mode  FromTableMode::CYCLE (default) | ::RANDOM, or the
     *                                      legacy strings 'cycle' | 'random'
     * @return \Closure(int): mixed
     */
    public static function fromTable(string $table, string $column = 'id', FromTableMode|string $mode = FromTableMode::CYCLE, ?string $connection = null): \Closure
    {
        if ($table === '') {
            throw new \InvalidArgumentException('fromTable() $table must not be empty.');
        }

        if ($column === '') {
            throw new \InvalidArgumentException('fromTable() $column must not be empty.');
        }

        $mode = FromTableMode::normalize($mode);

        $pool = null;
        $count = 0;

        return static function (int $index) use ($table, $column, $mode, $connection, &$pool, &$count): mixed {
            if ($pool === null) {
                $pool = array_values(
                    DB::connection($connection)->table($table)->pluck($column)->toArray()
                );

                if (empty($pool)) {
                    throw new \RuntimeException(
                        "TurboData::fromTable() - [{$table}.{$column}] returned no rows. Seed the table before referencing it."
                    );
                }

                $count = count($pool);

                self::warnIfPoolTooLarge($count, "fromTable('{$table}', '{$column}')");
            }

            return $mode === FromTableMode::RANDOM
                ? $pool[array_rand($pool)]
                : $pool[$index % $count];
        };
    }

    /**
     * Load values once via a callable, then cycle through them by index.
     * Use when fromTable() isn't enough - for filters, joins, custom ordering etc.
     *
     * @param  callable(): array<int, mixed>  $loader
     * @return \Closure(int): mixed
     */
    public static function fromQuery(callable $loader): \Closure
    {
        $pool = null;
        $count = 0;

        return static function (int $index) use ($loader, &$pool, &$count): mixed {
            if ($pool === null) {
                $pool = array_values($loader());

                if (empty($pool)) {
                    throw new \RuntimeException('TurboData::fromQuery() loader returned an empty array.');
                }

                $count = count($pool);

                self::warnIfPoolTooLarge($count, 'fromQuery()');
            }

            return $pool[$index % $count];
        };
    }

    /**
     * Memory-bounded alternative to fromTable() for very large reference tables.
     *
     * Instead of loading every value into memory, IDs are streamed one page at a
     * time and cycled sequentially (wrapping around at the end). Use this when the
     * referenced table is too large to materialise within the memory budget.
     *
     * Uses keyset (cursor) pagination on $column, which must be unique and
     * orderable (e.g. a primary key) — this keeps each page O(pageSize) instead
     * of the O(N) cost of OFFSET on deep pages.
     *
     * @return \Closure(int): mixed
     */
    public static function fromTableStream(string $table, string $column = 'id', int $pageSize = 10000, ?string $connection = null): \Closure
    {
        if ($table === '') {
            throw new \InvalidArgumentException('fromTableStream() $table must not be empty.');
        }

        if ($column === '') {
            throw new \InvalidArgumentException('fromTableStream() $column must not be empty.');
        }

        if ($pageSize < 1) {
            throw new \InvalidArgumentException('fromTableStream() $pageSize must be at least 1.');
        }

        /** @var array<int, mixed> $buffer */
        $buffer = [];
        $position = 0;
        $lastValue = null;

        $loadPage = static function (mixed $after) use ($table, $column, $pageSize, $connection): array {
            $query = DB::connection($connection)->table($table)
                ->orderBy($column)
                ->limit($pageSize);

            if ($after !== null) {
                $query->where($column, '>', $after);
            }

            return array_values($query->pluck($column)->toArray());
        };

        return static function (int $index) use ($table, $column, $loadPage, &$buffer, &$position, &$lastValue): mixed {
            if ($position >= count($buffer)) {
                $buffer = $loadPage($lastValue);

                if (empty($buffer)) {
                    // Reached the end: wrap back to the first page.
                    $lastValue = null;
                    $buffer = $loadPage(null);

                    if (empty($buffer)) {
                        throw new \RuntimeException(
                            "TurboData::fromTableStream() - [{$table}.{$column}] returned no rows. Seed the table before referencing it."
                        );
                    }
                }

                $lastValue = end($buffer);
                $position = 0;
            }

            return $buffer[$position++];
        };
    }

    /**
     * Log a one-time warning when an in-memory reference pool is large enough to
     * threaten the memory budget, pointing users at fromTableStream().
     */
    private static function warnIfPoolTooLarge(int $count, string $source): void
    {
        if ($count <= self::POOL_WARNING_THRESHOLD) {
            return;
        }

        Log::warning(sprintf(
            'TurboData::%s loaded %s values into memory (over %s). Consider TurboData::fromTableStream() to avoid high memory use.',
            $source,
            number_format($count),
            number_format(self::POOL_WARNING_THRESHOLD),
        ));
    }

    /**
     * Generate a unique email address with realistic format.
     * Example output: u_a3f9b2c1_0@turbo.test
     */
    public static function uniqueEmail(string $domain = 'turbo.test'): \Closure
    {
        $token = bin2hex(random_bytes(4));

        return static fn (int $index) => "u_{$token}_{$index}@{$domain}";
    }

    /**
     * Generate a unique username.
     * Example output: usr_a3f9b2c1_0
     */
    public static function uniqueUsername(string $prefix = 'usr'): \Closure
    {
        $token = bin2hex(random_bytes(4));

        return static fn (int $index) => "{$prefix}_{$token}_{$index}";
    }

    /**
     * Generate a unique URL-safe slug from a base string.
     * Example output: my-product-a3f9b2c1-0
     */
    public static function uniqueSlug(string $base): \Closure
    {
        $token = bin2hex(random_bytes(4));
        $slug = Str::slug($base);

        return static fn (int $index) => "{$slug}-{$token}-{$index}";
    }

    /**
     * Generate a unique UUID-based value with optional prefix.
     */
    public static function uniqueUuid(string $prefix = ''): \Closure
    {
        return static fn () => $prefix.Str::uuid()->toString();
    }
}
