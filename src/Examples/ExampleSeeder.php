<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Examples;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;
use IzAhmad\TurboSeeder\Traits\UsesTurboSeeder;

/**
 * Example seeder class demonstrating various ways to use TurboSeeder.
 *
 * @see UsesTurboSeeder
 * @see TurboData
 */
class ExampleSeeder extends Seeder
{
    use UsesTurboSeeder;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Example 1: Basic fluent API with TurboData helpers
        //
        // TurboData::uniqueEmail() generates realistic unique emails without Faker.
        // TurboData::nowOnce() calls now() once for the whole seeding run — all
        // records share the same "imported at" timestamp, which is realistic and fast.

        $uniqueEmail = TurboData::uniqueEmail();
        $hashedPassword = bcrypt('password'); // hashed once, reused across all records

        TurboSeeder::create('users')
            ->columns(['name', 'email', 'password', 'created_at'])
            ->generate(fn ($index) => [
                'name' => "User {$index}",
                'email' => $uniqueEmail($index),
                'password' => $hashedPassword,
                'created_at' => TurboData::nowOnce(),
            ])
            ->count(10000)
            ->run();

        // Example 2: CSV strategy for maximum speed with weighted distribution
        //
        // TurboData::weightedFrom() generates realistic non-uniform distributions.
        // ->useCsvStrategy() uses database-native CSV import (LOAD DATA for MySQL, COPY for PostgreSQL).
        // For MySQL/PostgreSQL with 5M+ records, CSV strategy is significantly faster.
        // For SQLite: use default strategy instead; CSV may not provide performance benefits.

        $now = TurboData::nowOnce();

        TurboSeeder::create('posts')
            ->columns(['user_id', 'title', 'status', 'content', 'created_at'])
            ->generate(fn ($index) => [
                'user_id' => TurboData::randomInt(1, 10000),
                'title' => "Post Title {$index}",
                'status' => TurboData::weightedFrom(['published' => 60, 'draft' => 30, 'archived' => 10]),
                'content' => "This is the content for post {$index}",
                'created_at' => $now,
            ])
            ->count(100000)
            // Uncomment for MySQL/PostgreSQL; use default strategy for SQLite
            // ->useCsvStrategy()
            ->run();

        // Example 3: Using a pool for correct FK assignment
        //
        // TurboData::fromPool() loads existing IDs once from the DB, then cycles
        // through them. This is safe with gaps, UUIDs, and soft-deleted records.

        $userIds = TurboData::fromPool(fn () => DB::table('users')->pluck('id')->toArray());

        TurboSeeder::create('orders')
            ->columns(['user_id', 'total', 'status', 'payment_method', 'created_at'])
            ->generate(fn ($index) => [
                'user_id' => $userIds($index),
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

        // Example 4: Conditional execution with nullable values
        //
        // TurboData::nullable() returns null with a given probability.

        TurboSeeder::create('products')
            ->columns(['name', 'price', 'stock', 'description', 'deleted_at', 'created_at'])
            ->generate(fn ($index) => [
                'name' => "Product {$index}",
                'price' => TurboData::randomFloat(2, 1.00, 9999.99),
                'stock' => TurboData::randomInt(0, 1000),
                'description' => "Description for product {$index}",
                'deleted_at' => TurboData::nullable(0.05, TurboData::nowOnce()),
                'created_at' => TurboData::nowOnce(),
            ])
            ->count(5000)
            ->when(
                config('app.env') === 'production',
                fn ($builder) => $builder->withoutProgressTracking()
            )
            ->run();

        // Example 5: Using the trait shorthand
        //
        // quickSeed() is a convenience wrapper around TurboSeeder::create().

        $this->quickSeed(
            'categories',
            ['name', 'slug', 'description', 'created_at'],
            fn ($index) => [
                'name' => "Category {$index}",
                'slug' => "category-{$index}",
                'description' => "Description for category {$index}",
                'created_at' => TurboData::nowOnce(),
            ],
            1000
        );

        // Example 6: Sequential dates for realistic time-series data
        //
        // TurboData::sequentialDate() increments by a fixed step per index,
        // producing realistic time-series data for analytics seeding.

        TurboSeeder::create('events')
            ->columns(['name', 'occurred_at'])
            ->generate(fn ($index) => [
                'name' => "Event {$index}",
                'occurred_at' => TurboData::sequentialDate('2024-01-01', '1 hour', $index),
            ])
            ->count(1000)
            ->run();

        // Example 7: this explains automatic type handling with ValueFormatter
        //
        // ValueFormatter automatically handles type conversions BTS (behind-the-scenes):
        // - PHP arrays → JSON encoded (e.g., ['key' => 'val'] → '{"key":"val"}')
        // - Booleans → integers (true → 1, false → 0)
        // - DateTime/Carbon → formatted strings (Y-m-d H:i:s)
        // - Enums → their values or names
        // - Collections → JSON arrays
        // NO manual formatting needed — just provide natural PHP values!

        $themeSelector = TurboData::cycleFrom(['dark', 'light']); // alternates dark/light

        TurboSeeder::create('settings')
            ->columns(['user_id', 'preferences', 'is_active', 'updated_at', 'metadata'])
            ->generate(fn ($index) => [
                'user_id' => TurboData::randomInt(1, 10000),
                // PHP array automatically encoded to JSON by ValueFormatter
                'preferences' => [
                    'theme' => $themeSelector($index),
                    'notifications' => TurboData::randomBool(0.8), // 80% true
                    'language' => TurboData::randomFrom(['en', 'es', 'fr']),
                ],
                // Boolean automatically converted to int (true → 1, false → 0)
                'is_active' => TurboData::randomBool(0.9), // 90% true
                // Carbon automatically formatted to Y-m-d H:i:s
                'updated_at' => TurboData::dateRange('2024-01-01', '2024-12-31'),
                // JSON string passed as-is (no double-encoding)
                'metadata' => ['source' => 'import', 'version' => 1],
            ])
            ->count(5000)
            ->run();
    }
}
