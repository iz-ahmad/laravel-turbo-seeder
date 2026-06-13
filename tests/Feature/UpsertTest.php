<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

test('upsert inserts new rows when no conflict exists', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@upsert.test"])
        ->count(5)
        ->upsert(['email'])
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(5)
        ->and(DB::table('test_users')->count())->toBe(5);
});

test('upsert updates existing rows on unique key conflict', function () {
    // Seed initial data
    TurboSeeder::create('test_users')
        ->columns(['name', 'email', 'age'])
        ->generate(fn ($i) => ['name' => "Original {$i}", 'email' => "user{$i}@conflict.test", 'age' => 20])
        ->count(3)
        ->run();

    expect(DB::table('test_users')->count())->toBe(3);

    // Upsert with same emails but updated name and age
    TurboSeeder::create('test_users')
        ->columns(['name', 'email', 'age'])
        ->generate(fn ($i) => ['name' => "Updated {$i}", 'email' => "user{$i}@conflict.test", 'age' => 99])
        ->count(3)
        ->upsert(['email'])
        ->run();

    // Row count unchanged - no new rows were inserted
    expect(DB::table('test_users')->count())->toBe(3);

    // The updated values are persisted
    $user = DB::table('test_users')->where('email', 'user0@conflict.test')->first();
    expect($user->name)->toBe('Updated 0')
        ->and((int) $user->age)->toBe(99);
});

test('upsert builder method requires at least one key column', function () {
    expect(fn () => TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@t.test"])
        ->count(1)
        ->upsert([]))
        ->toThrow(InvalidArgumentException::class);
});

test('upsert keys that are not in declared columns are rejected', function () {
    expect(fn () => TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@t.test"])
        ->count(1)
        ->upsert(['email', 'nonexistent_key'])
        ->run())
        ->toThrow(InvalidArgumentException::class, 'nonexistent_key');
});

test('upsert rejects invalid column names to prevent sql injection', function () {
    expect(fn () => TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@t.test"])
        ->count(1)
        ->upsert(['email; DROP TABLE test_users--']))
        ->toThrow(InvalidArgumentException::class, 'Invalid upsert key column name');
});

test('upsert keys not backed by a unique index are rejected', function () {
    // (name, email) is not a unique constraint on test_users.
    expect(fn () => TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@noindex.test"])
        ->count(3)
        ->upsert(['name', 'email'])
        ->run())
        ->toThrow(RuntimeException::class, 'unique or primary index');
});

test('upsert where all seeded columns are keys does nothing on conflict', function () {
    // test_counters.slug is unique; seeding only the key column means there is
    // nothing to update, so a re-seed must DO NOTHING rather than error.
    $seed = fn () => TurboSeeder::create('test_counters')
        ->columns(['slug'])
        ->generate(fn ($i) => ['slug' => "slug-{$i}"])
        ->count(3)
        ->upsert(['slug'])
        ->run();

    $seed();
    $result = $seed(); // same slugs again → conflicts ignored

    expect($result->success)->toBeTrue()
        ->and(DB::table('test_counters')->count())->toBe(3);
});
