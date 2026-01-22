<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Builder\TurboSeederBuilder;
use IzAhmad\TurboSeeder\Enums\SeederStrategy;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

test('builder validates required fields', function () {
    TurboSeeder::create()->run();
})->throws(InvalidArgumentException::class, 'Table name is required');

test('builder validates columns', function () {
    TurboSeeder::create('test_users')->run();
})->throws(InvalidArgumentException::class, 'Columns are required');

test('builder validates generator', function () {
    TurboSeeder::create('test_users')
        ->columns(['name'])
        ->run();
})->throws(InvalidArgumentException::class, 'Data generator is required');

test('builder validates count', function () {
    TurboSeeder::create('test_users')
        ->columns(['name'])
        ->generate(fn ($i) => ['name' => "User {$i}"])
        ->count(0)
        ->run();
})->throws(InvalidArgumentException::class, 'Count must be at least 1');

test('can chain methods fluently', function () {
    $builder = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(10)
        ->chunkSize(5)
        ->withoutProgressTracking();

    expect($builder)->toBeInstanceOf(TurboSeederBuilder::class);
});

test('can use when condition', function () {
    $useCsv = false;

    $builder = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(10)
        ->when($useCsv, fn ($b) => $b->useCsvStrategy());

    expect($builder->getStrategy())->toBe(SeederStrategy::DEFAULT);
});

test('can use unless condition', function () {
    $isProduction = false;

    $builder = TurboSeeder::create('test_users')
        ->columns(['name'])
        ->generate(fn ($i) => ['name' => "User {$i}"])
        ->count(10)
        ->unless($isProduction, fn ($b) => $b->withProgressTracking());

    expect($builder->getOptions())->toHaveKey('progress_tracking');
});

test('can get configuration without executing', function () {
    $config = TurboSeeder::create('test_users')
        ->columns(['name'])
        ->generate(fn ($i) => ['name' => "User {$i}"])
        ->count(100)
        ->toConfiguration();

    expect($config->table)->toBe('test_users')
        ->and($config->count)->toBe(100);
});

test('builder getters return correct values', function () {
    $builder = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(500)
        ->chunkSize(100);

    expect($builder->getTable())->toBe('test_users')
        ->and($builder->getColumns())->toBe(['name', 'email'])
        ->and($builder->getCount())->toBe(500)
        ->and($builder->getStrategy())->toBe(SeederStrategy::DEFAULT)
        ->and($builder->getOptions())->toHaveKey('chunk_size')
        ->and($builder->getOptions()['chunk_size'])->toBe(100);
});
