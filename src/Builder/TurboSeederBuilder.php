<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Builder;

use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\DTOs\SeederResultDTO;
use IzAhmad\TurboSeeder\Enums\SeederStrategy;
use IzAhmad\TurboSeeder\Services\SeederOrchestrator;

final class TurboSeederBuilder
{
    private ?string $table = null;

    /**
     * @var array<int, string>
     */
    private array $columns = [];

    private ?\Closure $generator = null;

    private int $count = 1000;

    private ?string $connection = null;

    private SeederStrategy $strategy = SeederStrategy::DEFAULT;

    /**
     * @var array<string, mixed>
     */
    private array $options = [];

    public function __construct(
        private readonly SeederOrchestrator $orchestrator
    ) {}

    /**
     * Set the table name.
     */
    public function table(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Set the columns to be seeded.
     *
     * @param  array<int, string>  $columns
     */
    public function columns(array $columns): self
    {
        $this->columns = array_values($columns);

        return $this;
    }

    /**
     * Set the data generator closure.
     */
    public function generate(\Closure $generator): self
    {
        $this->generator = $generator;

        return $this;
    }

    /**
     * Set the number of records to seed.
     */
    public function count(int $count): self
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Set the database connection.
     */
    public function connection(?string $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Use the CSV based seeding strategy.
     */
    public function useCsvStrategy(): self
    {
        $this->strategy = SeederStrategy::CSV;

        return $this;
    }

    /**
     * Use the default (bulk insert based) seeding strategy.
     */
    public function useDefaultStrategy(): self
    {
        $this->strategy = SeederStrategy::DEFAULT;

        return $this;
    }

    /**
     * Set a specific seeding strategy using the strategy enum.
     */
    public function strategy(SeederStrategy $strategy): self
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * Set custom chunk size.
     */
    public function chunkSize(int $size): self
    {
        if ($size < 1) {
            throw new \InvalidArgumentException('Chunk size cannot be less than 1!');
        }

        $this->options['chunk_size'] = $size;

        return $this;
    }

    /**
     * Enable or disable progress tracking for seeding operation.
     */
    public function withProgressTracking(bool $enabled = true): self
    {
        $this->options['progress_tracking'] = $enabled;

        return $this;
    }

    /**
     * Disable progress tracking for seeding operation.
     */
    public function withoutProgressTracking(): self
    {
        return $this->withProgressTracking(false);
    }

    /**
     * Enable or disable foreign key checks when seeding.
     */
    public function disableForeignKeyChecks(bool $disabled = true): self
    {
        $this->options['disable_foreign_keys'] = $disabled;

        return $this;
    }

    /**
     * Enable foreign key checks when seeding.
     */
    public function enableForeignKeyChecks(): self
    {
        return $this->disableForeignKeyChecks(false);
    }

    /**
     * Enable or disable query log when seeding.
     */
    public function disableQueryLog(bool $disabled = true): self
    {
        $this->options['disable_query_log'] = $disabled;

        return $this;
    }

    /**
     * Enable query log when seeding.
     */
    public function enableQueryLog(): self
    {
        return $this->disableQueryLog(false);
    }

    /**
     * Enable or disable db transactions when seeding.
     */
    public function useTransactions(bool $use = true): self
    {
        $this->options['use_transactions'] = $use;

        return $this;
    }

    /**
     * Disable transactions.
     */
    public function withoutTransactions(): self
    {
        return $this->useTransactions(false);
    }

    /**
     * Set custom options.
     *
     * @param  array<string, mixed>  $options
     */
    public function options(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Set a single option.
     */
    public function option(string $key, mixed $value): self
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * conditionally apply callback on the builder.
     */
    public function when(bool|callable $condition, callable $callback, ?callable $default = null): self
    {
        $conditionResult = is_callable($condition) ? $condition($this) : $condition;

        if ($conditionResult) {
            $callback($this);
        } elseif ($default) {
            $default($this);
        }

        return $this;
    }

    /**
     * Apply callback unless condition is true.
     */
    public function unless(bool|callable $condition, callable $callback, ?callable $default = null): self
    {
        $conditionResult = is_callable($condition) ? $condition($this) : $condition;

        return $this->when(! $conditionResult, $callback, $default);
    }

    /**
     * Execute the seeding operation.
     */
    public function run(): SeederResultDTO
    {
        $this->validate();

        $config = new SeederConfigurationDTO(
            table: $this->table,
            columns: $this->columns,
            generator: $this->generator,
            count: $this->count,
            connection: $this->connection ?? config('database.default'),
            strategy: $this->strategy,
            options: $this->options
        );

        return $this->orchestrator->execute($config);
    }

    /**
     * get the current configuration as DTO without executing the seeding operation.
     */
    public function toConfiguration(): SeederConfigurationDTO
    {
        $this->validate();

        return new SeederConfigurationDTO(
            table: $this->table,
            columns: $this->columns,
            generator: $this->generator,
            count: $this->count,
            connection: $this->connection ?? config('database.default'),
            strategy: $this->strategy,
            options: $this->options
        );
    }

    /**
     * Validate the builder state.
     */
    private function validate(): void
    {
        if ($this->table === null || $this->table === '') {
            throw new \InvalidArgumentException('Table name is required for seeding. Use table() method.');
        }

        if (empty($this->columns)) {
            throw new \InvalidArgumentException('Columns are required for seeding. Use columns() method.');
        }

        if ($this->generator === null) {
            throw new \InvalidArgumentException('Data generator is required for seeding. Use generate() method.');
        }

        if ($this->count < 1) {
            throw new \InvalidArgumentException('Count must be at least 1 for seeding. Use count() method.');
        }
    }

    /**
     * Get the current table name for seeding.
     */
    public function getTable(): ?string
    {
        return $this->table;
    }

    /**
     * Get the current columns for seeding.
     *
     * @return array<int, string>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Get the current count of records to seed.
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Get the current seeder strategy.
     */
    public function getStrategy(): SeederStrategy
    {
        return $this->strategy;
    }

    /**
     * Get all configured options of the builder.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
