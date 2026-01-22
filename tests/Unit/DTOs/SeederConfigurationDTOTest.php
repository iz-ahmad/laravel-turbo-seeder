<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\Enums\SeederStrategy;

test('can create seeder configuration DTO', function () {
    $config = new SeederConfigurationDTO(
        table: 'users',
        columns: ['name', 'email'],
        generator: fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"],
        count: 1000,
        connection: 'mysql',
        strategy: SeederStrategy::DEFAULT,
        options: ['chunk_size' => 500]
    );

    expect($config->table)->toBe('users')
        ->and($config->columns)->toBe(['name', 'email'])
        ->and($config->count)->toBe(1000)
        ->and($config->connection)->toBe('mysql')
        ->and($config->strategy)->toBe(SeederStrategy::DEFAULT)
        ->and($config->options)->toBe(['chunk_size' => 500]);
});

test('validates empty table name', function () {
    new SeederConfigurationDTO(
        table: '',
        columns: ['name'],
        generator: fn ($i) => ['name' => "User {$i}"],
        count: 100,
        connection: 'mysql'
    );
})->throws(InvalidArgumentException::class, 'Table name cannot be empty');

test('validates empty columns', function () {
    new SeederConfigurationDTO(
        table: 'users',
        columns: [],
        generator: fn ($i) => ['name' => "User {$i}"],
        count: 100,
        connection: 'mysql'
    );
})->throws(InvalidArgumentException::class, 'Columns array cannot be empty');

test('validates minimum count', function () {
    new SeederConfigurationDTO(
        table: 'users',
        columns: ['name'],
        generator: fn ($i) => ['name' => "User {$i}"],
        count: 0,
        connection: 'mysql'
    );
})->throws(InvalidArgumentException::class, 'Count must be at least 1');

test('validates empty connection', function () {
    new SeederConfigurationDTO(
        table: 'users',
        columns: ['name'],
        generator: fn ($i) => ['name' => "User {$i}"],
        count: 100,
        connection: ''
    );
})->throws(InvalidArgumentException::class, 'Connection name cannot be empty');

test('returns correct chunk size', function () {
    $config = new SeederConfigurationDTO(
        table: 'users',
        columns: ['name'],
        generator: fn ($i) => ['name' => "User {$i}"],
        count: 100,
        connection: 'mysql',
        options: ['chunk_size' => 300]
    );

    expect($config->getChunkSize())->toBe(300);
});

test('returns default chunk size when not set', function () {
    $config = new SeederConfigurationDTO(
        table: 'users',
        columns: ['name'],
        generator: fn ($i) => ['name' => "User {$i}"],
        count: 100,
        connection: 'mysql'
    );

    expect($config->getChunkSize())->toBe(4000);
});

test('checks progress tracking setting', function () {
    $configWithProgress = new SeederConfigurationDTO(
        table: 'users',
        columns: ['name'],
        generator: fn ($i) => ['name' => "User {$i}"],
        count: 100,
        connection: 'mysql',
        options: ['progress' => true]
    );

    $configWithoutProgress = new SeederConfigurationDTO(
        table: 'users',
        columns: ['name'],
        generator: fn ($i) => ['name' => "User {$i}"],
        count: 100,
        connection: 'mysql',
        options: ['progress' => false]
    );

    expect($configWithProgress->hasProgressTracking())->toBeTrue()
        ->and($configWithoutProgress->hasProgressTracking())->toBeFalse();
});
