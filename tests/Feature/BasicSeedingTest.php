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
    test()->truncateTable('test_users');

    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
        ])
        ->count(10)
        ->run();

    expect($result->success)->toBeTrue()
        ->and(DB::table('test_users')->count())->toBe(10);
});

// Edge cases

test('can seed exactly 1 record', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => [
            'name' => 'Only User',
            'email' => 'only@test.com',
        ])
        ->count(1)
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(1)
        ->and(DB::table('test_users')->count())->toBe(1);
});

test('chunk size larger than total count seeds correctly', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
        ])
        ->count(3)
        ->chunkSize(1000)
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(3)
        ->and(DB::table('test_users')->count())->toBe(3);
});

test('generator returning extra columns only inserts declared columns', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
            'extra_field' => 'ignored', // not in columns list
        ])
        ->count(5)
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(5);
});

test('seeding handles BackedEnum values in generator', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email', 'age'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
            'age' => SeedingTestAge::Adult, // int BackedEnum — value is 18
        ])
        ->count(5)
        ->run();

    expect($result->success)->toBeTrue();
    $user = DB::table('test_users')->first();
    expect((int) $user->age)->toBe(18);
});

test('when with callable condition is evaluated correctly', function () {
    $useCsv = false;

    $builder = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(5)
        ->when(fn () => $useCsv, fn ($b) => $b->useCsvStrategy());

    expect($builder->getStrategy()->value)->toBe('default');
});

test('unless with callable condition is evaluated correctly', function () {
    $isProduction = false;

    $builder = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(5)
        ->unless(fn () => $isProduction, fn ($b) => $b->disableForeignKeyChecks());

    expect($builder->getOptions())->toHaveKey('disable_foreign_keys')
        ->and($builder->getOptions()['disable_foreign_keys'])->toBeTrue();
});

enum SeedingTestAge: int
{
    case Adult = 18;
    case Senior = 65;
}
