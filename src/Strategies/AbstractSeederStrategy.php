<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies;

use IzAhmad\TurboSeeder\Actions\CleanupEnvironmentAction;
use IzAhmad\TurboSeeder\Actions\PrepareEnvironmentAction;
use IzAhmad\TurboSeeder\Contracts\MemoryManagerInterface;
use IzAhmad\TurboSeeder\Contracts\ProgressTrackerInterface;
use IzAhmad\TurboSeeder\Contracts\SeederStrategyInterface;
use IzAhmad\TurboSeeder\DTOs\DatabaseConnectionDTO;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\Strategies\Concerns\ManagesEnvironment;

abstract class AbstractSeederStrategy implements SeederStrategyInterface
{
    use ManagesEnvironment;

    protected int $chunkSize;

    private ?string $singleRowPlaceholderCache = null;

    public function __construct(
        protected readonly DatabaseConnectionDTO $dbConnection,
        protected readonly SeederConfigurationDTO $config,
        protected readonly MemoryManagerInterface $memoryManager,
        protected readonly ProgressTrackerInterface $progressTracker,
        protected readonly PrepareEnvironmentAction $prepareAction,
        protected readonly CleanupEnvironmentAction $cleanupAction,
    ) {
        $this->chunkSize = $this->determineOptimalChunkSize();
    }

    /**
     * Seed the database with the given configuration.
     */
    public function seed(SeederConfigurationDTO $config): int
    {
        $totalChunks = (int) ceil($config->count / $this->chunkSize);
        $recordsInserted = 0;

        for ($chunkIndex = 0; $chunkIndex < $totalChunks; $chunkIndex++) {
            $recordsInChunk = min(
                $this->chunkSize,
                $config->count - ($chunkIndex * $this->chunkSize)
            );

            $records = $this->generateChunk(
                $config->generator,
                $config->columns,
                $chunkIndex,
                $recordsInChunk
            );

            $this->insertChunkWithRetry($config->table, $config->columns, $records, $config->getRetryAttempts());

            $recordsInserted += $recordsInChunk;

            $this->memoryManager->forceCleanup();
            $this->progressTracker->advance($recordsInChunk);

            unset($records);
        }

        return $recordsInserted;
    }

    public function getOptimalChunkSize(): int
    {
        return $this->chunkSize;
    }

    /**
     * Generate a chunk of records.
     *
     * @param  array<int, string>  $columns
     * @return array<int, array<string, mixed>>
     */
    protected function generateChunk(
        \Closure $generator,
        array $columns,
        int $chunkIndex,
        int $count
    ): array {
        $records = [];
        $startIndex = $chunkIndex * $this->chunkSize;

        for ($i = 0; $i < $count; $i++) {
            $record = $generator($startIndex + $i);

            $filteredRecord = [];
            foreach ($columns as $column) {
                $filteredRecord[$column] = $record[$column] ?? null;
            }

            $records[] = $filteredRecord;
        }

        return $records;
    }

    /**
     * Insert a chunk of records, retrying on transient lock/deadlock failures.
     *
     * @param  array<int, string>  $columns
     * @param  array<int, array<string, mixed>>  $records
     */
    private function insertChunkWithRetry(string $table, array $columns, array $records, int $maxAttempts): void
    {
        $attempt = 0;

        while (true) {
            try {
                $this->insertChunk($table, $columns, $records);

                return;
            } catch (\Throwable $e) {
                if (! $this->isRetryableException($e) || $attempt >= $maxAttempts - 1) {
                    throw $e;
                }

                $attempt++;
                // Exponential backoff: 200ms, 400ms, 800ms, …
                usleep(100_000 * (2 ** $attempt));
            }
        }
    }

    /**
     * Determine whether an exception is a transient lock/deadlock error worth retrying.
     */
    private function isRetryableException(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        // SQLSTATE 40001 = serialization failure / deadlock detected (MySQL + PostgreSQL)
        // MySQL error 1205 = lock wait timeout exceeded
        return str_contains($message, 'deadlock')
            || str_contains($message, 'lock wait timeout')
            || (string) $e->getCode() === '40001'
            || $e->getCode() === 1205;
    }

    /**
     * Build (and cache) the single-row placeholder string, e.g. "(?,?,?)".
     * Column count is fixed per seeding operation, so this is safe to cache.
     */
    protected function buildSingleRowPlaceholder(int $columnCount): string
    {
        if ($this->singleRowPlaceholderCache === null) {
            $this->singleRowPlaceholderCache = '('.str_repeat('?,', $columnCount - 1).'?)';
        }

        return $this->singleRowPlaceholderCache;
    }

    /**
     * Insert a chunk of records into the database.
     *
     * @param  array<int, string>  $columns
     * @param  array<int, array<string, mixed>>  $records
     */
    abstract protected function insertChunk(string $table, array $columns, array $records): void;

    /**
     * Determine the optimal chunk size for this strategy.
     */
    abstract protected function determineOptimalChunkSize(): int;
}
