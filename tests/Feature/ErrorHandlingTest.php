<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

test('handles invalid table name gracefully', function () {
    expect(fn () => TurboSeeder::create('nonexistent_table')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(10)
        ->run())
        ->toThrow(\RuntimeException::class);
});

test('handles generator returning wrong columns', function () {
    expect(fn () => TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}"])
        ->count(10)
        ->run())
        ->toThrow(\RuntimeException::class);
});

test('handles empty generator result', function () {
    expect(fn () => TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => [])
        ->count(10)
        ->run())
        ->toThrow(\RuntimeException::class);
});

test('handles null values in generator', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email', 'age'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
            'age' => null,
        ])
        ->count(10)
        ->run();

    expect($result->success)->toBeTrue()
        ->and(DB::table('test_users')->whereNull('age')->count())->toBe(10);
});

test('handles very large count values', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(10000)
        ->chunkSize(1000)
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(10000);
});
