<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Event;
use App\Models\Order;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

/**
 * Real-world TurboSeeder examples covering all major features.
 *
 * forTable() accepts a table name, a Model class, or a Model instance
 * interchangeably - both forms are used below.
 */
class ExampleSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1) Factory path ───────────────────────────────────────────────────
        // Reuse an existing factory — table, columns, and timestamps are auto-inferred.
        //
        // TurboSeeder::fromFactory(\App\Models\User::factory()->unverified())
        //     ->count(100_000)
        //     ->run();
        //
        // With recycle() — pre-load parents so for() doesn't create one per row:
        // $users = \App\Models\User::all();
        // TurboSeeder::fromFactory(\App\Models\Post::factory()->recycle($users))
        //     ->count(1_000_000)
        //     ->run();

        // ── 2) Speed path: raw generator + TurboData ─────────────────────────
        // No Faker, no Eloquent — the fastest way to seed millions of rows.
        // Closure-factory helpers must be created outside generate() and called inside.

        $uniqueEmail = TurboData::uniqueEmail();
        $uniqueUsername = TurboData::uniqueUsername('usr');

        TurboSeeder::forTable(User::class)
            ->columns(['name', 'username', 'email', 'password', 'created_at', 'updated_at'])
            ->generate(fn ($index) => [
                'name' => "User {$index}",
                'username' => $uniqueUsername($index),
                'email' => $uniqueEmail($index),
                'password' => TurboData::hashedPassword(),
                'created_at' => TurboData::nowOnce(),
                'updated_at' => TurboData::nowOnce(),
            ])
            ->count(50_000)
            ->truncate()
            ->run();

        // ── 3) Auto timestamps + nullable columns ─────────────────────────────

        TurboSeeder::forTable(Category::class)
            ->columns(['name', 'slug', 'description'])
            ->generate(fn ($index) => [
                'name' => "Category {$index}",
                'slug' => "category-{$index}",
                'description' => TurboData::nullable(0.3, fn () => "Description {$index}"),
            ])
            ->withTimestamps()
            ->count(500)
            ->run();

        // ── 4) CSV strategy + foreign keys via fromTable() ────────────────────
        // useCsvStrategy() uses LOAD DATA (MySQL) or COPY FROM STDIN (PostgreSQL).
        // fromTable() loads reference IDs once; all subsequent calls are O(1).

        $userIds = TurboData::fromTable('users');                           // cycle (default)
        $categoryIds = TurboData::fromTable('categories', 'id', 'random');

        TurboSeeder::forTable(Post::class)
            ->columns(['user_id', 'category_id', 'title', 'status', 'created_at'])
            ->generate(fn ($index) => [
                'user_id' => $userIds($index),
                'category_id' => $categoryIds($index),
                'title' => "Post {$index}",
                'status' => TurboData::weightedFrom(['published' => 60, 'draft' => 30, 'archived' => 10]),
                'created_at' => TurboData::dateRange('2023-01-01', '2024-12-31'),
            ])
            ->count(1_000_000)
            ->useCsvStrategy()
            ->run();

        // ── 5) Huge reference tables: fromTableStream() ───────────────────────
        // Memory-bounded alternative to fromTable() — streams one page at a time.

        $bigPoolUserIds = TurboData::fromTableStream('users', 'id', pageSize: 10_000);
        $eventName = TurboData::cycleFrom(['page_view', 'click', 'signup']);

        TurboSeeder::forTable(Event::class)
            ->columns(['user_id', 'name', 'occurred_at'])
            ->generate(fn ($index) => [
                'user_id' => $bigPoolUserIds($index),
                'name' => $eventName($index),
                'occurred_at' => TurboData::sequentialDate('2024-01-01', 'hour', $index),
            ])
            ->count(8_760)
            ->run();

        // ── 6) Very large seeds: commitEvery() + progress tracking ────────────
        // Commit every N chunks — keeps the redo log / WAL small, at the cost of
        // all-or-nothing atomicity. Use truncate() + re-run on failure.

        TurboSeeder::forTable('logs')
            ->columns(['message', 'level', 'created_at'])
            ->generate(fn ($index) => [
                'message' => "Log entry {$index}",
                'level' => TurboData::randomFrom(['info', 'warning', 'error']),
                'created_at' => TurboData::nowOnce(),
            ])
            ->count(2_000_000)
            ->chunkSize(5_000)
            ->commitEvery(20)
            ->withProgressTracking()
            ->run();

        // ── 7) Upsert (conflict key must be backed by a unique index) ─────────
        // A plain table name works just as well as a Model class here.

        TurboSeeder::forTable('settings')
            ->columns(['key', 'value'])
            ->generate(fn ($index) => [
                'key' => "setting_{$index}",
                'value' => "value_{$index}",
            ])
            ->upsert(['key'])
            ->count(1_000)
            ->run();

        // ── 8) Dry run: validate generation without writing rows ──────────────

        $result = TurboSeeder::forTable(Order::class)
            ->columns(['user_id', 'total', 'note', 'created_at'])
            ->generate(fn ($index) => [
                'user_id' => TurboData::randomInt(1, 50_000),
                'total' => TurboData::randomFloat(2, 10, 999.99),
                'note' => TurboData::nullable(0.7, fn () => "Order note {$index}"),
                'created_at' => TurboData::nowOnce(),
            ])
            ->count(10_000)
            ->dryRun()
            ->run();

        // $result->isDryRun === true; $result->recordsInserted shows the would-be count.
    }
}
