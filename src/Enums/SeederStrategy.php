<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Enums;

enum SeederStrategy: string
{
    case DEFAULT = 'default';
    case CSV = 'csv';

    public function getDescription(): string
    {
        return match ($this) {
            self::DEFAULT => 'Standard bulk insert strategy using database-specific optimizations',
            self::CSV => 'High-performance CSV-based import strategy for maximum speed',
        };
    }

    public function getDisplayName(): string
    {
        return match ($this) {
            self::DEFAULT => 'Default',
            self::CSV => 'CSV',
        };
    }

    public function isFileBasedStrategy(): bool
    {
        return $this === self::CSV;
    }
}
