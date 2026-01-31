<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\DTOs;

use Illuminate\Database\Connection;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;

final readonly class DatabaseConnectionDTO
{
    public function __construct(
        public string $name,
        public DatabaseDriver $driver,
        public Connection $connection,
    ) {}

    /**
     * Create instance from connection name.
     */
    public static function fromName(string $name): self
    {
        $connection = \DB::connection($name);
        $driverName = $connection->getDriverName();

        $driver = DatabaseDriver::fromString($driverName);

        return new self($name, $driver, $connection);
    }

    /**
     * Create instance from default connection.
     */
    public static function default(): self
    {
        $name = config('database.default');

        return self::fromName($name);
    }

    /**
     * Get the underlying PDO connection.
     */
    public function getPdo(): \PDO
    {
        return $this->connection->getPdo();
    }

    /**
     * Get database name.
     */
    public function getDatabaseName(): string
    {
        return $this->connection->getDatabaseName();
    }
}
