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
        ?\Throwable $previous = null
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
     * Get user-friendly error message with instructions.
     */
    public function getUserMessage(): string
    {
        return $this->message;
    }
}
