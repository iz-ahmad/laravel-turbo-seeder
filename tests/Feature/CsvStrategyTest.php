<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

test('can seed using csv strategy', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email', 'age'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
            'age' => 25,
        ])
        ->count(100)
        ->useCsvStrategy()
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(100)
        ->and(DB::table('test_users')->count())->toBe(100);
});

test('csv strategy handles large datasets', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
        ])
        ->count(1000)
        ->useCsvStrategy()
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(1000)
        ->and(DB::table('test_users')->count())->toBe(1000);
});

test('can switch between default and csv strategies', function () {
    test()->truncateTable('test_users');

    $defaultResult = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(50)
        ->useDefaultStrategy()
        ->run();

    test()->truncateTable('test_users');

    $csvResult = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(50)
        ->useCsvStrategy()
        ->run();

    expect($defaultResult->success)->toBeTrue()
        ->and($csvResult->success)->toBeTrue()
        ->and($defaultResult->recordsInserted)->toBe(50)
        ->and($csvResult->recordsInserted)->toBe(50);
});
