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
     * Check if unique-index checks should be disabled (MySQL only).
     *
     * Separate, opt-in flag (default OFF): bulk-loading with unique_checks=0 can
     * let duplicate values slip into unique secondary indexes, so it is never
     * implied by disabling foreign key checks.
     */
    public function shouldDisableUniqueChecks(): bool
    {
        return $this->options['disable_unique_checks']
            ?? config('turbo-seeder.performance.disable_unique_checks', false);
    }

    public function shouldDisableQueryLog(): bool
    {
        return $this->options['disable_query_log']
            ?? config('turbo-seeder.performance.disable_query_log', true);
    }

    public function isDryRun(): bool
    {
        return $this->options['dry_run'] ?? false;
    }

    public function isUpsert(): bool
    {
        return ! empty($this->options['upsert_keys']);
    }

    /**
     * @return array<int, string>
     */
    public function getUpsertKeys(): array
    {
        return $this->options['upsert_keys'] ?? [];
    }

    public function getRetryAttempts(): int
    {
        return max(1, (int) ($this->options['retry_attempts'] ?? 3));
    }

    public function shouldValidateColumns(): bool
    {
        return $this->options['validate_columns'] ?? true;
    }

    /**
     * Check if a single wrapping transaction should be used during seeding.
     *
     * Precedence:
     *  1. Dry-run always needs a transaction to roll back.
     *  2. An explicit useTransactions()/withoutTransactions() wins.
     *  3. commitEvery() replaces the single wrap with periodic commits.
     *  4. The CSV strategy skips the wrap by default — LOAD DATA / COPY are
     *     atomic per statement, and wrapping millions of rows in one
     *     transaction strains the redo log / WAL and stalls replicas.
     *  5. Otherwise fall back to config (performance.use_transactions).
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
