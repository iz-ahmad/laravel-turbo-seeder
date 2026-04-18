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

test('isDryRun returns false by default', function () {
    $config = new SeederConfigurationDTO(
        table: 'users',
        columns: ['name'],
        generator: fn ($i) => ['name' => "User {$i}"],
        count: 1,
        connection: 'mysql',
    );

    expect($config->isDryRun())->toBeFalse();
});

test('isDryRun returns true when dry_run option is set', function () {
    $config = new SeederConfigurationDTO(
        table: 'users',
        columns: ['name'],
        generator: fn ($i) => ['name' => "User {$i}"],
        count: 1,
        connection: 'mysql',
        options: ['dry_run' => true],
    );

    expect($config->isDryRun())->toBeTrue();
});

test('isUpsert returns false when upsert_keys option is absent', function () {
    $config = new SeederConfigurationDTO(
        table: 'users',
        columns: ['name'],
        generator: fn ($i) => ['name' => "User {$i}"],
        count: 1,
        connection: 'mysql',
    );

    expect($config->isUpsert())->toBeFalse()
        ->and($config->getUpsertKeys())->toBe([]);
});

test('isUpsert returns true and getUpsertKeys returns the columns', function () {
    $config = new SeederConfigurationDTO(
        table: 'users',
        columns: ['name', 'email'],
        generator: fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@t.test"],
        count: 1,
        connection: 'mysql',
        options: ['upsert_keys' => ['email']],
    );

    expect($config->isUpsert())->toBeTrue()
        ->and($config->getUpsertKeys())->toBe(['email']);
});

test('getRetryAttempts returns 3 by default', function () {
    $config = new SeederConfigurationDTO(
        table: 'users',
        columns: ['name'],
        generator: fn ($i) => ['name' => "User {$i}"],
        count: 1,
        connection: 'mysql',
    );

    expect($config->getRetryAttempts())->toBe(3);
});

test('getRetryAttempts returns configured value', function () {
    $config = new SeederConfigurationDTO(
        table: 'users',
        columns: ['name'],
        generator: fn ($i) => ['name' => "User {$i}"],
        count: 1,
        connection: 'mysql',
        options: ['retry_attempts' => 5],
    );

    expect($config->getRetryAttempts())->toBe(5);
});

test('shouldValidateColumns returns true by default', function () {
    $config = new SeederConfigurationDTO(
        table: 'users',
        columns: ['name'],
        generator: fn ($i) => ['name' => "User {$i}"],
        count: 1,
        connection: 'mysql',
    );

    expect($config->shouldValidateColumns())->toBeTrue();
});

test('shouldValidateColumns returns false when disabled', function () {
    $config = new SeederConfigurationDTO(
        table: 'users',
        columns: ['name'],
        generator: fn ($i) => ['name' => "User {$i}"],
        count: 1,
        connection: 'mysql',
        options: ['validate_columns' => false],
    );

    expect($config->shouldValidateColumns())->toBeFalse();
});

test('checks progress tracking setting', function () {
    $configWithProgress = new SeederConfigurationDTO(
        table: 'users',
        columns: ['name'],
        generator: fn ($i) => ['name' => "User {$i}"],
        count: 100,
        connection: 'mysql',
        options: ['progress_tracking' => true]
    );

    $configWithoutProgress = new SeederConfigurationDTO(
        table: 'users',
        columns: ['name'],
        generator: fn ($i) => ['name' => "User {$i}"],
        count: 100,
        connection: 'mysql',
        options: ['progress_tracking' => false]
    );

    expect($configWithProgress->hasProgressTracking())->toBeTrue()
        ->and($configWithoutProgress->hasProgressTracking())->toBeFalse();
});

test('shouldUseTransactions returns true by default', function () {
    $config = new SeederConfigurationDTO(
        table: 'users',
        columns: ['name'],
        generator: fn ($i) => ['name' => "User {$i}"],
        count: 1,
        connection: 'mysql',
    );

    expect($config->shouldUseTransactions())->toBeTrue();
});

test('shouldUseTransactions returns false when disabled', function () {
    $config = new SeederConfigurationDTO(
        table: 'users',
        columns: ['name'],
        generator: fn ($i) => ['name' => "User {$i}"],
        count: 1,
        connection: 'mysql',
        options: ['use_transactions' => false],
    );

    expect($config->shouldUseTransactions())->toBeFalse();
});
