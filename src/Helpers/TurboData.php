<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Fast, Faker-free data generation helpers for use inside ->generate() closures.
 *
 * All static methods are safe to call 1M+ times without performance issues.
 */
final class TurboData
{
    private static ?string $cachedNow = null;

    // -------------------------------------------------------------------------
    // Value selection
    // -------------------------------------------------------------------------

    /**
     * Return a value by cycling through the array in round-robin order.
     * Replaces the fragile $values[$index % count($values)] pattern.
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
     * Weights are relative — they don't need to sum to 100.
     *
     * Example: ['active' => 70, 'inactive' => 20, 'banned' => 10]
     *
     * @param  array<string|int, int|float>  $weights  [value => weight]
     */
    public static function weightedFrom(array $weights): mixed
    {
        $total = array_sum($weights);

        if ($total <= 0) {
            throw new \InvalidArgumentException('weightedFrom() weights must sum to a positive number.');
        }

        $rand = (float) mt_rand() / mt_getrandmax() * $total;
        $cumulative = 0.0;

        foreach ($weights as $value => $weight) {
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

    // -------------------------------------------------------------------------
    // Scalars
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // Null / optional
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // Dates
    // -------------------------------------------------------------------------

    /**
     * Return a random Carbon date between two date strings.
     * Parses $from/$to once — safe to call 1M times.
     */
    public static function dateRange(string $from, string $to): Carbon
    {
        static $parsed = [];

        if (! isset($parsed[$from])) {
            $parsed[$from] = Carbon::parse($from)->timestamp;
        }

        if (! isset($parsed[$to])) {
            $parsed[$to] = Carbon::parse($to)->timestamp;
        }

        $timestamp = random_int($parsed[$from], $parsed[$to]);

        return Carbon::createFromTimestamp($timestamp);
    }

    /**
     * Return a Carbon date offset by $step * $index from $start.
     * Useful for sequential timestamps.
     *
     * @param  string  $step  Any valid Carbon modifier: '1 second', '1 minute', '1 hour', '1 day'
     */
    public static function sequentialDate(string $start, string $step, int $index): Carbon
    {
        static $startParsed = [];

        if (! isset($startParsed[$start])) {
            $startParsed[$start] = Carbon::parse($start);
        }

        return $startParsed[$start]->copy()->modify("+{$index} {$step}");
    }

    /**
     * Return the current timestamp as a string, computed only once per process.
     * Use this instead of now() inside generate() to avoid 1M identical now() calls
     * that differ only by microseconds.
     *
     * For records where all rows should share the same insert timestamp:
     *   'created_at' => TurboData::nowOnce()
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

    // -------------------------------------------------------------------------
    // Foreign key pool
    // -------------------------------------------------------------------------

    /**
     * Load values once via $loader callable, then cycle through them using the index.
     * Solves the fragile ($index % N) + 1 pattern for foreign key assignment.
     *
     * The loader is called once per unique pool key. Subsequent calls with the same
     * loader identity reuse the cached pool.
     *
     * Example:
     *   $userIds = TurboData::fromPool(fn() => DB::table('users')->pluck('id')->toArray());
     *   ->generate(fn($i) => ['user_id' => $userIds($i)])
     *
     * @param  callable(): array<int, mixed>  $loader
     * @return \Closure(int): mixed
     */
    public static function fromPool(callable $loader): \Closure
    {
        $poolKey = spl_object_id((object) []);
        $pool = null;

        return static function (int $index) use ($loader, &$pool): mixed {
            if ($pool === null) {
                $pool = array_values($loader());

                if (empty($pool)) {
                    throw new \RuntimeException('TurboData::fromPool() loader returned an empty array.');
                }
            }

            return $pool[$index % count($pool)];
        };
    }

    // -------------------------------------------------------------------------
    // Unique values
    // -------------------------------------------------------------------------

    /**
     * Generate a unique email address with realistic format.
     * Example output: u_a3f9b2c1@turbo.test
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
