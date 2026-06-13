<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

function chunkLimitDriver(): string
{
    return config('database.connections.'.config('database.default').'.driver');
}

test('a huge chunk size is clamped and still seeds on mysql', function () {
    // 20000 rows x 4 columns = 80000 > 65535 placeholders without clamping.
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email', 'age'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@chunk.test",
            'age' => 20,
        ])
        ->chunkSize(20000)
        ->count(100)
        ->run();

    expect($result->success)->toBeTrue()
        ->and(DB::table('test_users')->count())->toBe(100);
})->skip(fn () => chunkLimitDriver() !== 'mysql', 'MySQL-specific test');

test('a huge chunk size is clamped and still seeds on postgresql', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email', 'age'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@chunk.test",
            'age' => 20,
        ])
        ->chunkSize(20000)
        ->count(100)
        ->run();

    expect($result->success)->toBeTrue()
        ->and(DB::table('test_users')->count())->toBe(100);
})->skip(fn () => chunkLimitDriver() !== 'pgsql', 'PostgreSQL-specific test');
