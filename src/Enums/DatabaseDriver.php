<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Enums;

enum DatabaseDriver: string
{
    case MYSQL = 'mysql';
    case PGSQL = 'pgsql';
    case SQLITE = 'sqlite';

    public static function fromString(string $driver): self
    {
        return match (strtolower($driver)) {
            'mysql' => self::MYSQL,
            'pgsql', 'postgresql' => self::PGSQL,
            'sqlite' => self::SQLITE,
            default => throw new \InvalidArgumentException("Unsupported database driver: {$driver}"),
        };
    }

    public function supportsCsvImport(): bool
    {
        return match ($this) {
            self::MYSQL, self::PGSQL, self::SQLITE => true,
        };
    }

    public function getDisplayName(): string
    {
        return match ($this) {
            self::MYSQL => 'MySQL',
            self::PGSQL => 'PostgreSQL',
            self::SQLITE => 'SQLite',
        };
    }
}
