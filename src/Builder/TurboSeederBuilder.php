<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Builder;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\DTOs\SeederResultDTO;
use IzAhmad\TurboSeeder\Enums\SeederStrategy;
use IzAhmad\TurboSeeder\Services\SeederOrchestrator;
use IzAhmad\TurboSeeder\Support\FactoryDataGenerator;

/**
 * Fluent builder for configuring and executing TurboSeeder operations.
 *
 * Provides a chainable interface for setting up seeding operations with
 * various configuration options. Supports method chaining for intuitive
 * and readable configuration.
 */
final class TurboSeederBuilder
{
    private ?string $table = null;

    /**
     * @var array<int, string>
     */
    private array $columns = [];

    private ?\Closure $generator = null;

    private ?FactoryDataGenerator $factoryGenerator = null;

    private ?bool $withTimestamps = null;

    private bool $timestampsApplied = false;

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
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $table)) {
            throw new \InvalidArgumentException(
                "Invalid table name [{$table}]. Table names must start with a letter or underscore, contain only letters, digits, and underscores, and may include one schema prefix (schema.table)."
            );
        }

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
        foreach ($columns as $column) {
            if (! preg_match('/^[a-zA-Z0-9_]+$/', (string) $column)) {
                throw new \InvalidArgumentException(
                    "Invalid column name [{$column}]. Column names must only contain letters, digits, and underscores."
                );
            }
        }

        $this->columns = array_values($columns);

        return $this;
    }

    /**
     * Derive the columns to seed from the table schema (every column except the
     * auto-incrementing key). Opt-in convenience so you don't repeat the column
     * list; the generator must still produce values for the non-nullable ones.
     */
    public function columnsFromSchema(): self
    {
        if ($this->table === null || $this->table === '') {
            throw new \InvalidArgumentException('Call create()/table() before columnsFromSchema().');
        }

        $schema = DB::connection($this->connection ?? config('database.default'))->getSchemaBuilder();

        try {
            $columns = [];

            foreach ($schema->getColumns($this->table) as $column) {
                if ($column['auto_increment'] === true) {
                    continue;
                }

                $columns[] = $column['name'];
            }
        } catch (\Throwable) {
            // Older Laravel / driver without getColumns(): fall back to the full
            // listing (which may include the key column — override if needed).
            $columns = $schema->getColumnListing($this->table);
        }

        if (empty($columns)) {
            throw new \InvalidArgumentException(
                "columnsFromSchema(): no columns found for table [{$this->table}]."
            );
        }

        return $this->columns($columns);
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
     * Generate rows from an existing Laravel model factory.
     *
     * Reuses the factory's definition, states and Faker as the single source of
     * truth for the row shape, then bulk-inserts the raw attributes. Model
     * events, observers and accessors are skipped for speed; timestamps are
     * filled automatically when the model uses them.
     */
    public function fromFactory(Factory $factory): self
    {
        $this->factoryGenerator = new FactoryDataGenerator($factory);

        if ($this->table === null) {
            $this->table($this->factoryGenerator->table());
        }

        $this->generator = $this->factoryGenerator->toGenerator();

        return $this;
    }

    /**
     * Auto-fill created_at/updated_at with a single timestamp for every row.
     *
     * Enabled by default on the factory path when the model uses timestamps.
     * Explicit calls always win.
     */
    public function withTimestamps(bool $enabled = true): self
    {
        $this->withTimestamps = $enabled;

        return $this;
    }

    /**
     * Do not auto-fill timestamps (even on the factory path).
     */
    public function withoutTimestamps(): self
    {
        return $this->withTimestamps(false);
    }

    /**
     * Empty the target table before seeding (a fresh, committed operation).
     *
     * Cannot be combined with dryRun(): the wipe is not rolled back.
     */
    public function truncate(bool $enabled = true): self
    {
        $this->options['truncate'] = $enabled;

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
     * Disable MySQL unique-index checks during seeding (opt-in, MySQL only).
     *
     * Speeds up bulk loads but can let duplicate values into unique secondary
     * indexes — only use it when the data is known to be unique.
     */
    public function disableUniqueChecks(bool $disabled = true): self
    {
        $this->options['disable_unique_checks'] = $disabled;

        return $this;
    }

    /**
     * Enable MySQL unique-index checks when seeding.
     */
    public function enableUniqueChecks(): self
    {
        return $this->disableUniqueChecks(false);
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
     * Commit every N chunks (default strategy only) instead of wrapping the
     * whole run in one transaction. Keeps the redo log / WAL small on very
     * large seeds at the cost of all-or-nothing atomicity.
     */
    public function commitEvery(int $chunks): self
    {
        if ($chunks < 1) {
            throw new \InvalidArgumentException('commitEvery() must be at least 1.');
        }

        $this->options['commit_every'] = $chunks;

        return $this;
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
     * Enable dry-run mode: data is generated and validated but not committed.
     * The result will report how many records would have been inserted.
     *
     * Dry-run discards inserts by rolling back a transaction, so it always
     * runs inside one. Combining dryRun() with withoutTransactions() is a
     * hard error (run() throws InvalidArgumentException) to prevent rows
     * being silently committed.
     */
    public function dryRun(bool $enabled = true): self
    {
        $this->options['dry_run'] = $enabled;

        return $this;
    }

    /**
     * Enable upsert mode using the given unique key columns.
     * On conflict, non-key columns are updated with the new values.
     *
     * @param  array<int, string>  $uniqueBy  Column(s) that identify a unique row
     */
    public function upsert(array $uniqueBy): self
    {
        if (empty($uniqueBy)) {
            throw new \InvalidArgumentException('upsert() requires at least one unique key column.');
        }

        foreach ($uniqueBy as $column) {
            if (! preg_match('/^[a-zA-Z0-9_]+$/', (string) $column)) {
                throw new \InvalidArgumentException(
                    "Invalid upsert key column name [{$column}]. Column names must only contain letters, digits, and underscores."
                );
            }
        }

        $this->options['upsert_keys'] = array_values($uniqueBy);

        return $this;
    }

    /**
     * Set the number of retry attempts on transient lock/deadlock failures.
     */
    public function retryAttempts(int $attempts): self
    {
        if ($attempts < 1) {
            throw new \InvalidArgumentException('retryAttempts() must be at least 1.');
        }

        if ($attempts > 10) {
            throw new \InvalidArgumentException('retryAttempts() cannot exceed 10.');
        }

        $this->options['retry_attempts'] = $attempts;

        return $this;
    }

    /**
     * Disable schema column validation before seeding.
     * By default, declared columns are checked against the actual table schema.
     */
    public function withoutColumnValidation(): self
    {
        $this->options['validate_columns'] = false;

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
        $config = $this->buildConfiguration();

        $result = $this->orchestrator->execute($config);

        if (! $result->success) {
            // Preserve the original failure (class, SQLSTATE, stack trace) as the
            // previous exception so callers and error trackers do not lose it.
            throw new \RuntimeException(
                $result->errorMessage ?? 'Seeding operation failed without error message',
                0,
                $result->exception,
            );
        }

        return $result;
    }

    /**
     * get the current configuration as DTO without executing the seeding operation.
     */
    public function toConfiguration(): SeederConfigurationDTO
    {
        return $this->buildConfiguration();
    }

    /**
     * Validate state and build the configuration DTO.
     * Shared by run() and toConfiguration() to avoid duplicate logic.
     */
    private function buildConfiguration(): SeederConfigurationDTO
    {
        $this->applyTimestamps();
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
     * Whether timestamps should be auto-filled for this run.
     * Explicit withTimestamps()/withoutTimestamps() wins; otherwise the factory
     * path defaults to on when the model uses timestamps.
     */
    private function shouldApplyTimestamps(): bool
    {
        if ($this->withTimestamps !== null) {
            return $this->withTimestamps;
        }

        return $this->factoryGenerator?->usesTimestamps() ?? false;
    }

    /**
     * Wrap the generator so created_at/updated_at are filled once per run.
     * Must run before column inference so the timestamp columns are included.
     */
    private function applyTimestamps(): void
    {
        if ($this->timestampsApplied || $this->generator === null || ! $this->shouldApplyTimestamps()) {
            return;
        }

        $createdAt = $this->factoryGenerator?->createdAtColumn() ?? 'created_at';
        $updatedAt = $this->factoryGenerator?->updatedAtColumn() ?? 'updated_at';

        $timestamp = now()->toDateTimeString();
        $inner = $this->generator;

        $this->generator = static function (int $index) use ($inner, $createdAt, $updatedAt, $timestamp): array {
            $record = $inner($index);

            if (! array_key_exists($createdAt, $record)) {
                $record[$createdAt] = $timestamp;
            }

            if (! array_key_exists($updatedAt, $record)) {
                $record[$updatedAt] = $timestamp;
            }

            return $record;
        };

        if (! empty($this->columns)) {
            foreach ([$createdAt, $updatedAt] as $column) {
                if (! in_array($column, $this->columns, true)) {
                    $this->columns[] = $column;
                }
            }
        }

        $this->timestampsApplied = true;
    }

    /**
     * Validate the builder state.
     * If columns were not set explicitly, they are inferred from the first generator record.
     */
    private function validate(): void
    {
        if ($this->table === null || $this->table === '') {
            throw new \InvalidArgumentException('Table name is required for seeding. Use table() method.');
        }

        if ($this->generator === null) {
            throw new \InvalidArgumentException('Data generator is required for seeding. Use generate() method.');
        }

        if (empty($this->columns)) {
            $firstRecord = ($this->generator)(0);

            if (! is_array($firstRecord) || empty($firstRecord)) {
                throw new \InvalidArgumentException(
                    'Columns could not be inferred from the generator. Use columns() method or ensure generate() returns a non-empty associative array.'
                );
            }

            $inferredColumns = array_keys($firstRecord);

            foreach ($inferredColumns as $column) {
                if (! preg_match('/^[a-zA-Z0-9_]+$/', (string) $column)) {
                    throw new \InvalidArgumentException(
                        "Invalid column name [{$column}] inferred from generator. Column names must only contain letters, digits, and underscores."
                    );
                }
            }

            $this->columns = $inferredColumns;
        }

        if ($this->count < 1) {
            throw new \InvalidArgumentException('Count must be at least 1 for seeding. Use count() method.');
        }

        $upsertKeys = $this->options['upsert_keys'] ?? [];
        if (! empty($upsertKeys)) {
            $invalidKeys = array_diff($upsertKeys, $this->columns);
            if (! empty($invalidKeys)) {
                throw new \InvalidArgumentException(
                    'Upsert key column(s) ['.implode(', ', $invalidKeys).'] are not in the declared columns. Upsert keys must be a subset of the seeded columns.'
                );
            }
        }

        // dryRun() rolls back via a transaction. Without one there is nothing to
        // roll back, so rows would be permanently committed despite isDryRun being
        // true. Refuse the combination loudly instead of silently writing data.
        if (($this->options['dry_run'] ?? false) === true
            && ($this->options['use_transactions'] ?? true) === false) {
            throw new \InvalidArgumentException(
                'dryRun() cannot be combined with withoutTransactions(): a dry run relies on a transaction rollback to discard rows. '
                .'Remove withoutTransactions() (dry runs always run inside a transaction).'
            );
        }

        // truncate() permanently empties the table before seeding and is not part
        // of the dry-run rollback, so refuse the misleading combination.
        if (($this->options['truncate'] ?? false) === true
            && ($this->options['dry_run'] ?? false) === true) {
            throw new \InvalidArgumentException(
                'truncate() cannot be combined with dryRun(): truncation is committed immediately and would not be rolled back.'
            );
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
