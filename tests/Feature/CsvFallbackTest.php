<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Exceptions\CsvImportFailedException;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

test('csv import failed exception has correct properties', function () {
    $exception = new CsvImportFailedException(
        'Test error message',
        true
    );

    expect($exception->shouldFallback())->toBeTrue()
        ->and($exception->getMessage())->toBe('Test error message')
        ->and($exception->getUserMessage())->toBe('Test error message');
});

test('csv import failed exception can disable fallback', function () {
    $exception = new CsvImportFailedException(
        'Critical error',
        false
    );

    expect($exception->shouldFallback())->toBeFalse();
});

test('csv import failed exception preserves original exception', function () {
    $originalException = new \RuntimeException('Original error');

    $exception = new CsvImportFailedException(
        'Wrapped error',
        true,
        $originalException
    );

    expect($exception->getPrevious())->toBe($originalException)
        ->and($exception->getMessage())->toBe('Wrapped error');
});

test('csv strategy works or falls back gracefully on mysql', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
        ])
        ->count(50)
        ->useCsvStrategy()
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(50)
        ->and(DB::table('test_users')->count())->toBe(50);
})->skip(fn () => getDatabaseDriver() !== 'mysql', 'MySQL-specific test');

test('csv strategy handles large datasets on mysql', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email', 'age'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
            'age' => random_int(18, 65),
        ])
        ->count(1000)
        ->useCsvStrategy()
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(1000)
        ->and(DB::table('test_users')->count())->toBe(1000);
})->skip(fn () => getDatabaseDriver() !== 'mysql', 'MySQL-specific test');

test('csv strategy handles timestamps on mysql', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email', 'created_at', 'updated_at'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
            'created_at' => now(),
            'updated_at' => now(),
        ])
        ->count(100)
        ->useCsvStrategy()
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(100);

    $user = DB::table('test_users')->first();
    expect($user->created_at)->not->toBeNull()
        ->and($user->updated_at)->not->toBeNull();
})->skip(fn () => getDatabaseDriver() !== 'mysql', 'MySQL-specific test');

test('csv strategy works or falls back gracefully on postgresql', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
        ])
        ->count(50)
        ->useCsvStrategy()
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(50)
        ->and(DB::table('test_users')->count())->toBe(50);
})->skip(fn () => getDatabaseDriver() !== 'pgsql', 'PostgreSQL-specific test');

test('csv strategy handles large datasets on postgresql', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email', 'age'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
            'age' => random_int(18, 65),
        ])
        ->count(1000)
        ->useCsvStrategy()
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(1000)
        ->and(DB::table('test_users')->count())->toBe(1000);
})->skip(fn () => getDatabaseDriver() !== 'pgsql', 'PostgreSQL-specific test');

test('csv strategy handles timestamps on postgresql', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email', 'created_at', 'updated_at'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
            'created_at' => now(),
            'updated_at' => now(),
        ])
        ->count(100)
        ->useCsvStrategy()
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(100);

    $user = DB::table('test_users')->first();
    expect($user->created_at)->not->toBeNull()
        ->and($user->updated_at)->not->toBeNull();
})->skip(fn () => getDatabaseDriver() !== 'pgsql', 'PostgreSQL-specific test');

test('csv strategy tracks performance metrics', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
        ])
        ->count(500)
        ->useCsvStrategy()
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->durationSeconds)->toBeGreaterThanOrEqual(0)
        ->and($result->peakMemoryBytes)->toBeGreaterThanOrEqual(0)
        ->and($result->getPeakMemoryInMB())->toBeGreaterThanOrEqual(0);
})->skip(fn () => ! in_array(getDatabaseDriver(), ['mysql', 'pgsql']), 'Requires MySQL or PostgreSQL');

/**
 * Get the current database driver.
 */
function getDatabaseDriver(): string
{
    return config('database.connections.'.config('database.default').'.driver');
}
