<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Facades\TurboSeeder;

test('retryAttempts sets the option on the builder', function () {
    $builder = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@retry.test"])
        ->count(1)
        ->retryAttempts(5);

    expect($builder->getOptions())->toHaveKey('retry_attempts')
        ->and($builder->getOptions()['retry_attempts'])->toBe(5);
});

test('retryAttempts cannot exceed 10', function () {
    expect(fn () => TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@r.test"])
        ->count(1)
        ->retryAttempts(11))
        ->toThrow(InvalidArgumentException::class);
});

test('retryAttempts must be at least 1', function () {
    expect(fn () => TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@r.test"])
        ->count(1)
        ->retryAttempts(0))
        ->toThrow(InvalidArgumentException::class);
});

test('seeding succeeds with explicit retryAttempts configured', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@r2.test"])
        ->count(10)
        ->retryAttempts(2)
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(10);
});
