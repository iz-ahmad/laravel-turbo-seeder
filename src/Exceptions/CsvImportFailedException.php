<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Exceptions;

use RuntimeException;

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

    public function shouldFallback(): bool
    {
        return $this->shouldFallback;
    }

    public function getDriver(): ?string
    {
        return $this->driver;
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function getFilepath(): ?string
    {
        return $this->filepath;
    }

    public function getUserMessage(): string
    {
        return $this->message;
    }
}
