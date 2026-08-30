<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\DTOs;

use IzAhmad\TurboSeeder\Enums\SeederStrategy;

final readonly class SeederConfigurationDTO
{
    /**
     * @param  array<int, string>  $columns
     * @param  array<string, mixed>  $options
     * @param  array<int, string>  $pendingWarnings
     */
    public function __construct(
        public string $table,
        public array $columns,
        public \Closure $generator,
        public int $count,
        public string $connection,
        public SeederStrategy $strategy = SeederStrategy::DEFAULT,
        public array $options = [],
        public array $pendingWarnings = [],
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
        return (bool) ($this->options['progress_tracking']
            ?? config('turbo-seeder.progress.enabled', true));
    }

    /**
     * Check if foreign key checks should be disabled.
     *
     * Falls back to config (performance.disable_foreign_keys) when not set
     * explicitly on the builder.
     */
    public function shouldDisableForeignKeyChecks(): bool
    {
        return (bool) ($this->options['disable_foreign_keys']
            ?? config('turbo-seeder.performance.disable_foreign_keys', true));
    }

    /**
     * Check if unique-index checks should be disabled (MySQL only).
     *
     * Separate, opt-in flag (default OFF): bulk-loading with unique_checks=0 can
     * let duplicate values slip into unique secondary indexes, so it is never
     * implied by disabling foreign key checks.
     */
    public function shouldDisableUniqueChecks(): bool
    {
        return (bool) ($this->options['disable_unique_checks']
            ?? config('turbo-seeder.performance.disable_unique_checks', false));
    }

    /**
     * Check if query log should be disabled.
     *
     * Falls back to config (performance.disable_query_log) when not set
     * explicitly on the builder.
     */
    public function shouldDisableQueryLog(): bool
    {
        return (bool) ($this->options['disable_query_log']
            ?? config('turbo-seeder.performance.disable_query_log', true));
    }

    /**
     * Check if this is a dry-run (no rows committed).
     */
    public function isDryRun(): bool
    {
        return (bool) ($this->options['dry_run'] ?? false);
    }

    /**
     * Check if the production-environment guard should be bypassed.
     */
    public function isForced(): bool
    {
        return (bool) ($this->options['force'] ?? false);
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
        return (bool) ($this->options['validate_columns'] ?? true);
    }

    /**
     * Check if a single wrapping transaction should be used during seeding.
     * Precedence: dry-run (always on) → explicit option → commitEvery() (off) → CSV strategy (off) → config.
     */
    public function shouldUseTransactions(): bool
    {
        if ($this->isDryRun()) {
            return true;
        }

        if (array_key_exists('use_transactions', $this->options)) {
            return (bool) $this->options['use_transactions'];
        }

        if ($this->getCommitEvery() !== null) {
            return false;
        }

        if ($this->strategy === SeederStrategy::CSV) {
            return false;
        }

        return config('turbo-seeder.performance.use_transactions', true);
    }

    /**
     * Number of chunks to commit per transaction for the default strategy.
     * Null means a single wrapping transaction (or none) is used instead.
     */
    public function getCommitEvery(): ?int
    {
        $value = $this->options['commit_every'] ?? null;

        return $value === null ? null : max(1, (int) $value);
    }
}
