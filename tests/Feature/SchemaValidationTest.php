<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

test('seeding throws when a declared column does not exist on the table', function () {
    expect(fn () => TurboSeeder::create('test_users')
        ->columns(['name', 'email', 'nonexistent_column'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@col.test", 'nonexistent_column' => 'x'])
        ->count(5)
        ->run())
        ->toThrow(RuntimeException::class, 'nonexistent_column');
});

test('schema validation error message lists available columns', function () {
    $errorMessage = null;

    try {
        TurboSeeder::create('test_users')
            ->columns(['name', 'missing_col'])
            ->generate(fn ($i) => ['name' => "User {$i}", 'missing_col' => 'x'])
            ->count(1)
            ->run();
    } catch (RuntimeException $e) {
        $errorMessage = $e->getMessage();
    }

    expect($errorMessage)->toContain('missing_col')
        ->and($errorMessage)->toContain('test_users');
});

test('withoutColumnValidation skips schema check and fails at the database level instead', function () {
    expect(fn () => TurboSeeder::create('test_users')
        ->columns(['name', 'email', 'nonexistent_column'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@skip.test", 'nonexistent_column' => 'x'])
        ->count(3)
        ->withoutColumnValidation()
        ->run())
        ->toThrow(RuntimeException::class);

    // Row count stays 0 because the DB error caused a rollback
    expect(DB::table('test_users')->count())->toBe(0);
});

test('schema validation passes for all valid columns on test_users', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email', 'age'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@valid.test", 'age' => 25])
        ->count(5)
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(5);
});

test('column names with invalid characters are rejected', function () {
    expect(fn () => TurboSeeder::create('test_users')
        ->columns(['name', 'bad-column'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'bad-column' => 'x']))
        ->toThrow(InvalidArgumentException::class, 'bad-column');
});

test('column names with backticks are rejected', function () {
    expect(fn () => TurboSeeder::create('test_users')
        ->columns(['name', '`injection`'])
        ->generate(fn ($i) => ['name' => "User {$i}", '`injection`' => 'x']))
        ->toThrow(InvalidArgumentException::class);
});
