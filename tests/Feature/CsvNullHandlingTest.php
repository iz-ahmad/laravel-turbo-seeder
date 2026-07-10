<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

/**
 * NULL handling on the native CSV import paths (MySQL LOAD DATA, PostgreSQL COPY).
 *
 * These run only under the MySQL/PostgreSQL CI jobs; they are the regression
 * guard for the silent NULL-corruption bug fixed on the MySQL path.
 */
function nullHandlingDriver(): string
{
    return config('database.connections.'.config('database.default').'.driver');
}

test('mysql csv import stores null markers as real NULLs', function () {
    $result = TurboSeeder::forTable('test_users')
        ->columns(['name', 'email', 'age'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
            'age' => $i % 2 === 0 ? null : 42,
        ])
        ->count(20)
        ->useCsvStrategy()
        ->run();

    expect($result->success)->toBeTrue()
        ->and(DB::table('test_users')->whereNull('age')->count())->toBe(10)
        ->and(DB::table('test_users')->where('age', 42)->count())->toBe(10);
})->skip(fn () => nullHandlingDriver() !== 'mysql', 'MySQL-specific test');

test('postgresql csv import stores null markers as real NULLs', function () {
    $result = TurboSeeder::forTable('test_users')
        ->columns(['name', 'email', 'age'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
            'age' => $i % 2 === 0 ? null : 42,
        ])
        ->count(20)
        ->useCsvStrategy()
        ->run();

    expect($result->success)->toBeTrue()
        ->and(DB::table('test_users')->whereNull('age')->count())->toBe(10)
        ->and(DB::table('test_users')->where('age', 42)->count())->toBe(10);
})->skip(fn () => nullHandlingDriver() !== 'pgsql', 'PostgreSQL-specific test');
