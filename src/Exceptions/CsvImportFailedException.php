<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Exceptions;

use RuntimeException;

/**
 * Exception thrown when CSV import fails and fallback to default strategy should be attempted.
 */
final class CsvImportFailedException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly bool $shouldFallback = true,
        ?\Throwable $previous = null,
        private readonly ?string $driver = null,
        private readonly ?string $table = null,
        private readonly ?string $filepath = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Check if fallback to default strategy should be attempted.
     */
    public function shouldFallback(): bool
    {
        return $this->shouldFallback;
    }

    /**
     * Get the database driver that triggered the failure.
     */
    public function getDriver(): ?string
    {
        return $this->driver;
    }

    /**
     * Get the table name that was being seeded.
     */
    public function getTable(): ?string
    {
        return $this->table;
    }

    /**
     * Get the CSV filepath that was being imported.
     */
    public function getFilepath(): ?string
    {
        return $this->filepath;
    }

    /**
     * Get user-friendly error message with instructions.
     */
    public function getUserMessage(): string
    {
        return $this->message;
    }
}
