<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Examples;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

/**
 * Demonstrates the TurboSeeder API usage with real-life examples.
 *
 * See the documentation (README.md) for more details.
 *
 * @see TurboData
 */
class ExampleSeeder extends Seeder
{
    public function run(): void
    {
        // ── Example 1: Basic fluent API ───────────────────────────────────────
        //
        // uniqueEmail(), uniqueUsername() return closures that produce
        // collision-free values across millions of records; no Faker needed.
        // hashedPassword() hashes once and reuses the result.
        // Calling bcrypt() inside the generator would hash once per record (will be slower).
        // nowOnce() calls now() a single time for the whole run.

        $uniqueEmail = TurboData::uniqueEmail();
        $uniqueUsername = TurboData::uniqueUsername('usr');

        TurboSeeder::create('users')
            ->columns(['name', 'username', 'email', 'password', 'created_at'])
            ->generate(fn ($index) => [
                'name' => "User {$index}",
                'username' => $uniqueUsername($index),
                'email' => $uniqueEmail($index),
                'password' => TurboData::hashedPassword(),
                'created_at' => TurboData::nowOnce(),
            ])
            ->count(10000)
            ->run();

        // ── Example 2: Seeding categories ───────────────────────────────────────

        TurboSeeder::create('categories')
            ->columns(['name', 'slug', 'created_at'])
            ->generate(fn ($index) => [
                'name' => "Category {$index}",
                'slug' => "category-{$index}",
                'created_at' => TurboData::nowOnce(),
            ])
            ->count(500)
            ->run();

        // ── Example 3: CSV strategy for maximum speed ─────────────────────────
        //
        // useCsvStrategy() uses LOAD DATA (MySQL) or COPY (PostgreSQL) - the
        // fastest possible insert path. For SQLite, use the default strategy.
        // fromTable() plucks IDs once from the already-seeded users table,
        // then cycles or randomly picks with zero extra DB queries.
        // weightedFrom() produces realistic non-uniform distributions.

        $userIds = TurboData::fromTable('users'); // cycle (default)

        TurboSeeder::create('posts')
            ->columns(['user_id', 'title', 'status', 'content', 'created_at'])
            ->generate(fn ($index) => [
                'user_id' => $userIds($index),
                'title' => "Post Title {$index}",
                'status' => TurboData::weightedFrom(['published' => 60, 'draft' => 30, 'archived' => 10]),
                'content' => "Content for post {$index}",
                'created_at' => TurboData::nowOnce(),
            ])
            ->count(100000)
            ->useCsvStrategy()
            ->run();

        // ── Example 4: Relational seeding with fromTable() ────────────────────
        //
        // Two fromTable() closures: one cycles user IDs deterministically,
        // the other picks category IDs at random.
        // chunkSize(), withProgressTracking(), disableForeignKeyChecks() are
        // the most commonly needed configuration methods.

        $categoryIds = TurboData::fromTable('categories', 'id', 'random');

        TurboSeeder::create('orders')
            ->columns(['user_id', 'category_id', 'total', 'status', 'payment_method', 'created_at'])
            ->generate(fn ($index) => [
                'user_id' => $userIds($index),
                'category_id' => $categoryIds($index),
                'total' => TurboData::randomFloat(2, 10.00, 999.99),
                'status' => TurboData::weightedFrom(['pending' => 50, 'completed' => 40, 'cancelled' => 10]),
                'payment_method' => TurboData::randomFrom(['paypal', 'bank_transfer', 'credit_card']),
                'created_at' => TurboData::dateRange('2023-01-01', '2024-12-31'),
            ])
            ->count(50000)
            ->chunkSize(2000)
            ->withProgressTracking()
            ->disableForeignKeyChecks()
            ->run();

        // ── Example 5: Unique slugs/UUIDs, nullable values, and when() ────────
        //
        // uniqueSlug() produces URL-safe slugs guaranteed unique per index.
        // uniqueUuid() produces a UUID with an optional prefix per call.
        // nullable() returns null with a given probability (5% here → soft-deleted).
        // when() conditionally chains options without breaking the fluent API.

        $productSlug = TurboData::uniqueSlug('product');
        $productSku = TurboData::uniqueUuid('SKU-');

        TurboSeeder::create('products')
            ->columns(['sku', 'slug', 'name', 'price', 'stock', 'deleted_at', 'created_at'])
            ->generate(fn ($index) => [
                'sku' => $productSku(),
                'slug' => $productSlug($index),
                'name' => "Product {$index}",
                'price' => TurboData::randomFloat(2, 1.00, 9999.99),
                'stock' => TurboData::randomInt(0, 1000),
                'deleted_at' => TurboData::nullable(0.05, TurboData::nowOnce()),
                'created_at' => TurboData::nowOnce(),
            ])
            ->count(5000)
            ->when(
                config('app.env') === 'production',
                fn ($builder) => $builder->withoutProgressTracking()
            )
            ->run();

        // ── Example 6: fromQuery() for custom filtered FK pools ───────────────────────
        //
        // Use fromQuery() when fromTable() isn't enough - for custom filters, joins,
        // or specific ordering. The loader runs once; all subsequent calls are
        // O(1) array lookups, same as fromTable().

        $activeUserIds = TurboData::fromQuery(
            fn () => DB::table('users')->where('active', 1)->orderBy('id')->pluck('id')->toArray()
        );

        TurboSeeder::create('reviews')
            ->columns(['user_id', 'product_id', 'rating', 'created_at'])
            ->generate(fn ($index) => [
                'user_id' => $activeUserIds($index),
                'product_id' => TurboData::randomInt(1, 5000),
                'rating' => TurboData::randomInt(1, 5),
                'created_at' => TurboData::dateRange('2023-01-01', '2024-12-31'),
            ])
            ->count(20000)
            ->run();

        // ── Example 7: Sequential dates for time-series data ──────────────────
        //
        // sequentialDate() increments by a fixed step per index.
        // $step is a bare unit word: 'second', 'minute', 'hour', 'day', etc.
        // cycleFrom() round-robins through the array by index.

        $eventType = TurboData::cycleFrom(['page_view', 'click', 'signup']);

        TurboSeeder::create('events')
            ->columns(['name', 'occurred_at'])
            ->generate(fn ($index) => [
                'name' => $eventType($index),
                'occurred_at' => TurboData::sequentialDate('2024-01-01', 'hour', $index),
            ])
            ->count(8760) // one year of hourly data
            ->run();

        // ── Example 8: Automatic type handling via ValueFormatter ─────────────
        //
        // No manual type conversion needed - TurboSeeder handles it:
        //   bool                        → 1 / 0
        //   DateTime / Carbon           → Y-m-d H:i:s
        //   BackedEnum / UnitEnum       → value / name
        //   array / Collection / object → JSON string
        //   JSON string                 → stored as-is (no double-encoding)
        // So you can pass all types of values in the generator function as needed.

        $themeSelector = TurboData::cycleFrom(['dark', 'light']);

        TurboSeeder::create('settings')
            ->columns(['user_id', 'preferences', 'is_dark', 'updated_at', 'metadata'])
            ->generate(fn ($index) => [
                'user_id' => TurboData::randomInt(1, 10000),
                'preferences' => [                                        // array → JSON
                    'theme' => $themeSelector($index),
                    'notifications' => TurboData::randomBool(0.8),
                    'language' => TurboData::randomFrom(['en', 'es', 'fr']),
                ],
                'is_dark' => TurboData::randomBool(0.5),              // bool → 1/0
                'updated_at' => TurboData::dateRange('2024-01-01', '2024-12-31'), // Carbon → string
                'metadata' => '{"status":"synced","priority":3}',      // JSON string → as-is
            ])
            ->count(5000)
            ->run();
    }
}
