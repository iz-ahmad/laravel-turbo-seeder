<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

test('can disable foreign key checks', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(10)
        ->disableForeignKeyChecks()
        ->run();

    expect($result->success)->toBeTrue();
});

test('can enable foreign key checks', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(10)
        ->enableForeignKeyChecks()
        ->run();

    expect($result->success)->toBeTrue();
});

test('can disable query log', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(10)
        ->disableQueryLog()
        ->run();

    expect($result->success)->toBeTrue();
});

test('can enable query log', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(10)
        ->enableQueryLog()
        ->run();

    expect($result->success)->toBeTrue();
});

test('can disable transactions', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(10)
        ->withoutTransactions()
        ->run();

    expect($result->success)->toBeTrue();
});

test('can use custom connection', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(10)
        ->connection('testing')
        ->run();

    expect($result->success)->toBeTrue();
});

test('can set custom options', function () {
    $builder = TurboSeeder::create('test_users')
        ->columns(['name'])
        ->generate(fn ($i) => ['name' => "User {$i}"])
        ->count(10)
        ->options(['custom_option' => 'value']);

    expect($builder->getOptions())->toHaveKey('custom_option')
        ->and($builder->getOptions()['custom_option'])->toBe('value');
});

test('can set single option', function () {
    $builder = TurboSeeder::create('test_users')
        ->columns(['name'])
        ->generate(fn ($i) => ['name' => "User {$i}"])
        ->count(10)
        ->option('test_key', 'test_value');

    expect($builder->getOptions())->toHaveKey('test_key')
        ->and($builder->getOptions()['test_key'])->toBe('test_value');
});
