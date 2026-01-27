<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies;

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Actions\CleanupEnvironmentAction;
use IzAhmad\TurboSeeder\Actions\PrepareEnvironmentAction;
use IzAhmad\TurboSeeder\Contracts\MemoryManagerInterface;
use IzAhmad\TurboSeeder\Contracts\ProgressTrackerInterface;
use IzAhmad\TurboSeeder\Contracts\SeederStrategyInterface;
use IzAhmad\TurboSeeder\DTOs\DatabaseConnectionDTO;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;

abstract class AbstractSeederStrategy implements SeederStrategyInterface
{
    protected int $chunkSize;

    protected bool $environmentPrepared = false;

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

            $this->insertChunk($config->table, $config->columns, $records);

            $recordsInserted += $recordsInChunk;

            $this->memoryManager->forceCleanup();
            $this->progressTracker->advance($recordsInChunk);

            unset($records);
        }

        return $recordsInserted;
    }

    public function prepareEnvironment(): void
    {
        if ($this->environmentPrepared) {
            return;
        }

        ($this->prepareAction)($this->dbConnection, $this->config);

        if ($this->config->options['use_transactions'] ?? true) {
            DB::connection($this->dbConnection->name)->beginTransaction();
        }

        $this->environmentPrepared = true;
    }

    public function cleanup(): void
    {
        if (! $this->environmentPrepared) {
            return;
        }

        if ($this->config->options['use_transactions'] ?? true) {
            $connection = DB::connection($this->dbConnection->name);

            if ($connection->transactionLevel() > 0) {
                $connection->commit();
            }
        }

        ($this->cleanupAction)($this->dbConnection, $this->config);

        $this->environmentPrepared = false;
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
