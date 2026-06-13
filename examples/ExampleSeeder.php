<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

/**
 * Real-world TurboSeeder examples.
 *
 * This file is a copy-paste reference (it is intentionally NOT autoloaded by the
 * package). It shows both data-generation paths and the main options.
 *
 * Two ways to generate rows:
 *   1. fromFactory()  - reuse your existing model factory (convenience tier).
 *   2. generate()     - a raw closure + TurboData helpers (maximum speed).
 */
class ExampleSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1) Factory path: reuse an existing factory ───────────────────────
        //
        // Reuses the factory definition, states and Faker as the single source
        // of truth. Timestamps are filled automatically when the model uses them.
        // Model events/observers/accessors are skipped for speed.
        //
        // TurboSeeder::fromFactory(\App\Models\User::factory()->unverified())
        //     ->count(100_000)
        //     ->run();

        // ── 2) Speed path: raw generator + TurboData ─────────────────────────
        //
        // No Faker, no Eloquent — the fastest way to seed millions of rows.
        // uniqueEmail()/uniqueUsername() return closures you call with $index.
        // hashedPassword()/nowOnce() are computed once and reused.

        $uniqueEmail = TurboData::uniqueEmail();
        $uniqueUsername = TurboData::uniqueUsername('usr');

        TurboSeeder::create('users')
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
            ->truncate()              // wipe the table first (committed, not part of a dry run)
            ->run();

        // ── 3) Let TurboSeeder fill timestamps for you ───────────────────────
        //
        // withTimestamps() injects created_at/updated_at once per run, so you do
        // not have to add them to the generator yourself.

        TurboSeeder::create('categories')
            ->columns(['name', 'slug'])
            ->generate(fn ($index) => [
                'name' => "Category {$index}",
                'slug' => "category-{$index}",
            ])
            ->withTimestamps()
            ->count(500)
            ->run();

        // ── 4) CSV strategy + foreign keys via fromTable() ───────────────────
        //
        // useCsvStrategy() uses LOAD DATA (MySQL) or client-side COPY (PostgreSQL).
        // fromTable() loads reference IDs once and cycles/randomly picks them.

        $userIds = TurboData::fromTable('users');                 // cycle (default)
        $categoryIds = TurboData::fromTable('categories', 'id', 'random');

        TurboSeeder::create('posts')
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

        // ── 5) Huge reference tables: fromTableStream() ──────────────────────
        //
        // Memory-bounded alternative to fromTable() — IDs are streamed a page at
        // a time instead of being materialised in memory.

        $bigPoolUserIds = TurboData::fromTableStream('users', 'id', pageSize: 10_000);

        TurboSeeder::create('events')
            ->columns(['user_id', 'name', 'occurred_at'])
            ->generate(fn ($index) => [
                'user_id' => $bigPoolUserIds($index),
                'name' => TurboData::cycleFrom(['page_view', 'click', 'signup'])($index),
                'occurred_at' => TurboData::sequentialDate('2024-01-01', 'hour', $index),
            ])
            ->count(8_760)
            ->run();

        // ── 6) Very large default-strategy seeds: commitEvery() ──────────────
        //
        // Commit every N chunks instead of wrapping the whole run in one
        // transaction (keeps the redo log / WAL small on huge seeds).

        TurboSeeder::create('logs')
            ->columns(['message', 'level', 'created_at'])
            ->generate(fn ($index) => [
                'message' => "Log entry {$index}",
                'level' => TurboData::randomFrom(['info', 'warning', 'error']),
                'created_at' => TurboData::nowOnce(),
            ])
            ->count(2_000_000)
            ->chunkSize(5_000)
            ->commitEvery(20)
            ->run();

        // ── 7) Upsert (keys must be backed by a unique index) ────────────────

        TurboSeeder::create('settings')
            ->columns(['key', 'value'])
            ->generate(fn ($index) => [
                'key' => "setting_{$index}",
                'value' => "value_{$index}",
            ])
            ->upsert(['key'])      // 'key' must have a unique constraint
            ->count(1_000)
            ->run();

        // ── 8) Dry run: validate generation without writing rows ─────────────

        $result = TurboSeeder::create('orders')
            ->columns(['user_id', 'total', 'created_at'])
            ->generate(fn ($index) => [
                'user_id' => TurboData::randomInt(1, 50_000),
                'total' => TurboData::randomFloat(2, 10, 999.99),
                'created_at' => TurboData::nowOnce(),
            ])
            ->count(10_000)
            ->dryRun()
            ->run();

        // $result->isDryRun === true; no rows were committed.
    }
}
