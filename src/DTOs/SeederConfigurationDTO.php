<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\DTOs;

use IzAhmad\TurboSeeder\Enums\SeederStrategy;

final readonly class SeederConfigurationDTO
{
    public function __construct(
        public string $table,
        public array $columns,
        public \Closure $generator,
        public int $count,
        public string $connection,
        public SeederStrategy $strategy = SeederStrategy::DEFAULT,
        public array $options = [],
    ) {
        $this->validate();
    }

    /**
     * validate the configuration.
     */
    private function validate(): void
    {
        if (empty($this->table)) {
            throw new \InvalidArgumentException('Table name cannot be empty');
        }

        if (empty($this->columns)) {
            throw new \InvalidArgumentException('Columns array cannot be empty');
        }

        if ($this->count < 1) {
            throw new \InvalidArgumentException('Count must be at least 1');
        }

        if (empty($this->connection)) {
            throw new \InvalidArgumentException('Connection name cannot be empty');
        }
    }

    /**
     * Get chunk size from options or use default.
     */
    public function getChunkSize(?int $default = null): int
    {
        return $this->options['chunk_size'] ?? $default ?? 5000;
    }

    /**
     * Check if progress tracking is enabled.
     */
    public function hasProgressTracking(): bool
    {
        return $this->options['progress'] ?? true;
    }

    /**
     * Check if foreign key checks should be disabled.
     */
    public function shouldDisableForeignKeyChecks(): bool
    {
        return $this->options['disable_foreign_keys'] ?? true;
    }

    /**
     * Check if query log should be disabled.
     */
    public function shouldDisableQueryLog(): bool
    {
        return $this->options['disable_query_log'] ?? true;
    }
}
