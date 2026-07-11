<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

function chunkLimitDriver(): string
{
    return config('database.connections.'.config('database.default').'.driver');
}

// 3 columns x chunkSize(30000) = 90000 placeholders, over the 65,535 driver limit
test('a huge chunk size is clamped and still seeds on mysql', function () {
    $result = TurboSeeder::forTable('test_users')
        ->columns(['name', 'email', 'age'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@chunk.test",
            'age' => 20,
        ])
        ->chunkSize(30000)
        ->count(22000)
        ->run();

    expect($result->success)->toBeTrue()
        ->and(DB::table('test_users')->count())->toBe(22000);
})->skip(fn () => chunkLimitDriver() !== 'mysql', 'MySQL-specific test');

test('a huge chunk size is clamped and still seeds on postgresql', function () {
    $result = TurboSeeder::forTable('test_users')
        ->columns(['name', 'email', 'age'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@chunk.test",
            'age' => 20,
        ])
        ->chunkSize(30000)
        ->count(22000)
        ->run();

    expect($result->success)->toBeTrue()
        ->and(DB::table('test_users')->count())->toBe(22000);
})->skip(fn () => chunkLimitDriver() !== 'pgsql', 'PostgreSQL-specific test');
