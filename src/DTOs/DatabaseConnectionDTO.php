<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\DTOs;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;

final readonly class DatabaseConnectionDTO
{
    public function __construct(
        public string $name,
        public DatabaseDriver $driver,
        public Connection $connection,
    ) {}

    public static function fromName(string $name): self
    {
        $connection = DB::connection($name);
        $driverName = $connection->getDriverName();

        $driver = DatabaseDriver::fromString($driverName);

        return new self($name, $driver, $connection);
    }

    public static function default(): self
    {
        $name = config('database.default');

        return self::fromName($name);
    }

    public function getPdo(): \PDO
    {
        return $this->connection->getPdo();
    }

    public function getDatabaseName(): string
    {
        return $this->connection->getDatabaseName();
    }
}
