<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

test('can seed basic records using fluent api', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email', 'age'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
            'age' => 25,
        ])
        ->count(100)
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(100)
        ->and(DB::table('test_users')->count())->toBe(100);
});

test('can seed with timestamps', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email', 'age', 'created_at', 'updated_at'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
            'age' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ])
        ->count(50)
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(50);

    $user = DB::table('test_users')->first();
    expect($user->created_at)->not->toBeNull();
});

test('can seed with custom chunk size', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
        ])
        ->count(200)
        ->chunkSize(25)
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(200)
        ->and(DB::table('test_users')->count())->toBe(200);
});

test('seeding tracks performance metrics', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
        ])
        ->count(500)
        ->run();

    expect($result->durationSeconds)->toBeGreaterThanOrEqual(0)
        ->and($result->peakMemoryBytes)->toBeGreaterThanOrEqual(0)
        ->and($result->getPeakMemoryInMB())->toBeMemoryInMB();
});

test('can seed with foreign key relationships', function () {
    TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
        ])
        ->count(10)
        ->run();

    $result = TurboSeeder::create('test_posts')
        ->columns(['user_id', 'title', 'content'])
        ->generate(fn ($i) => [
            'user_id' => ($i % 10) + 1,
            'title' => "Post {$i}",
            'content' => "Content for post {$i}",
        ])
        ->count(50)
        ->run();

    expect($result->success)->toBeTrue()
        ->and(DB::table('test_posts')->count())->toBe(50);
});

test('can seed large datasets efficiently', function () {
    $startTime = microtime(true);

    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email', 'age'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
            'age' => random_int(18, 65),
        ])
        ->count(5000)
        ->run();

    $duration = microtime(true) - $startTime;

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(5000)
        ->and($duration)->toBeLessThan(10)
        ->and(DB::table('test_users')->count())->toBe(5000);
});

test('handles empty table gracefully', function () {
    DB::table('test_users')->truncate();

    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com"
        ])
        ->count(10)
        ->run();

    expect($result->success)->toBeTrue()
        ->and(DB::table('test_users')->count())->toBe(10);
});
