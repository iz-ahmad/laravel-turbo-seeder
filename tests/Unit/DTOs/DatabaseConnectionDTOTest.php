<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\DTOs\DatabaseConnectionDTO;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;

test('can create database connection from name', function () {
    $connection = DatabaseConnectionDTO::fromName('testing');

    expect($connection->name)->toBe('testing')
        ->and($connection->driver)->toBeInstanceOf(DatabaseDriver::class);
});

test('can create default database connection', function () {
    $connection = DatabaseConnectionDTO::default();

    expect($connection->name)->toBeString()
        ->and($connection->driver)->toBeInstanceOf(DatabaseDriver::class);
});

test('can get pdo connection', function () {
    $connection = DatabaseConnectionDTO::fromName('testing');

    expect($connection->getPdo())->toBeInstanceOf(\PDO::class);
});

test('can get database name', function () {
    $connection = DatabaseConnectionDTO::fromName('testing');

    expect($connection->getDatabaseName())->toBeString();
});
