<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Enums\DatabaseDriver;

test('can create database driver from string', function () {
    expect(DatabaseDriver::fromString('mysql'))->toBe(DatabaseDriver::MYSQL)
        ->and(DatabaseDriver::fromString('pgsql'))->toBe(DatabaseDriver::PGSQL)
        ->and(DatabaseDriver::fromString('postgresql'))->toBe(DatabaseDriver::PGSQL)
        ->and(DatabaseDriver::fromString('sqlite'))->toBe(DatabaseDriver::SQLITE);
});

test('throws exception for unsupported driver', function () {
    DatabaseDriver::fromString('unsupported');
})->throws(InvalidArgumentException::class, 'Unsupported database driver');

test('returns correct optimal chunk size', function () {
    expect(DatabaseDriver::MYSQL->getOptimalChunkSize())->toBe(5000)
        ->and(DatabaseDriver::PGSQL->getOptimalChunkSize())->toBe(3000)
        ->and(DatabaseDriver::SQLITE->getOptimalChunkSize())->toBe(2000);
});

test('supports csv import correctly', function () {
    expect(DatabaseDriver::MYSQL->supportsCsvImport())->toBeTrue()
        ->and(DatabaseDriver::PGSQL->supportsCsvImport())->toBeTrue()
        ->and(DatabaseDriver::SQLITE->supportsCsvImport())->toBeFalse();
});

test('returns correct display name', function () {
    expect(DatabaseDriver::MYSQL->getDisplayName())->toBe('MySQL')
        ->and(DatabaseDriver::PGSQL->getDisplayName())->toBe('PostgreSQL')
        ->and(DatabaseDriver::SQLITE->getDisplayName())->toBe('SQLite');
});
