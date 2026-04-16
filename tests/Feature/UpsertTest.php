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

    // Row count unchanged — no new rows were inserted
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
