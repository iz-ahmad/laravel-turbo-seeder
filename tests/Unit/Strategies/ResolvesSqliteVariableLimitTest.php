<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\DTOs\DatabaseConnectionDTO;
use IzAhmad\TurboSeeder\Strategies\Concerns\ResolvesSqliteVariableLimit;

function sqliteLimitResolver(DatabaseConnectionDTO $connection): object
{
    return new class($connection)
    {
        use ResolvesSqliteVariableLimit;

        public function __construct(public DatabaseConnectionDTO $dbConnection) {}

        public function limit(): int
        {
            return $this->sqliteVariableLimit();
        }

        public function maxRows(int $columns): int
        {
            return $this->sqliteMaxRowsPerBatch($columns);
        }
    };
}

test('detects the modern variable limit on SQLite 3.32+', function () {
    $resolver = sqliteLimitResolver(DatabaseConnectionDTO::fromName('testing'));

    expect($resolver->limit())->toBe(32766);
})->skip(fn () => config('database.connections.testing.driver') !== 'sqlite', 'SQLite-specific test');

test('computes max rows per batch from the variable limit', function () {
    $resolver = sqliteLimitResolver(DatabaseConnectionDTO::fromName('testing'));

    expect($resolver->maxRows(10))->toBe(intdiv(32766, 10))
        ->and($resolver->maxRows(0))->toBeGreaterThanOrEqual(1);
})->skip(fn () => config('database.connections.testing.driver') !== 'sqlite', 'SQLite-specific test');
