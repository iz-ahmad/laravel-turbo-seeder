<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\DTOs;

use IzAhmad\TurboSeeder\Enums\SeederStrategy;

final readonly class SeederConfigurationDTO
{
    /**
     * @param  array<int, string>  $columns
     * @param  array<string, mixed>  $options
     */
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
    public function getChunkSize(): ?int
    {
        return $this->options['chunk_size'] ?? null;
    }

    /**
     * Check if progress tracking is enabled.
     *
     * Falls back to the published config (progress.enabled) before the
     * hardcoded default so a user's config/turbo-seeder.php is respected.
     */
    public function hasProgressTracking(): bool
    {
        return $this->options['progress_tracking']
            ?? config('turbo-seeder.progress.enabled', true);
    }

    /**
     * Check if foreign key checks should be disabled.
     *
     * Falls back to config (performance.disable_foreign_keys) when not set
     * explicitly on the builder.
     */
    public function shouldDisableForeignKeyChecks(): bool
    {
        return $this->options['disable_foreign_keys']
            ?? config('turbo-seeder.performance.disable_foreign_keys', true);
    }

    /**
     * Check if query log should be disabled.
     *
     * Falls back to config (performance.disable_query_log) when not set
     * explicitly on the builder.
     */
    public function shouldDisableQueryLog(): bool
    {
        return $this->options['disable_query_log']
            ?? config('turbo-seeder.performance.disable_query_log', true);
    }

    /**
     * Check if this is a dry-run (no rows committed).
     */
    public function isDryRun(): bool
    {
        return $this->options['dry_run'] ?? false;
    }

    /**
     * Check if upsert mode is enabled.
     */
    public function isUpsert(): bool
    {
        return ! empty($this->options['upsert_keys']);
    }

    /**
     * Get the unique key columns for upsert operations.
     *
     * @return array<int, string>
     */
    public function getUpsertKeys(): array
    {
        return $this->options['upsert_keys'] ?? [];
    }

    /**
     * Get the maximum number of retry attempts on lock/deadlock failures.
     */
    public function getRetryAttempts(): int
    {
        return max(1, (int) ($this->options['retry_attempts'] ?? 3));
    }

    /**
     * Check if schema column validation is enabled.
     */
    public function shouldValidateColumns(): bool
    {
        return $this->options['validate_columns'] ?? true;
    }

    /**
     * Check if database transactions should be used during seeding.
     *
     * Falls back to config (performance.use_transactions) when not set
     * explicitly on the builder.
     */
    public function shouldUseTransactions(): bool
    {
        return $this->options['use_transactions']
            ?? config('turbo-seeder.performance.use_transactions', true);
    }
}
