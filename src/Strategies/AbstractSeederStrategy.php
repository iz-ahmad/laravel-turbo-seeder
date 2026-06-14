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
use IzAhmad\TurboSeeder\Strategies\Concerns\ClassifiesDatabaseErrors;
use IzAhmad\TurboSeeder\Strategies\Concerns\ManagesEnvironment;

abstract class AbstractSeederStrategy implements SeederStrategyInterface
{
    use ClassifiesDatabaseErrors;
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

    public function seed(SeederConfigurationDTO $config): int
    {
        $totalChunks = (int) ceil($config->count / $this->chunkSize);
        $recordsInserted = 0;

        // commitEvery() trades all-or-nothing atomicity for a small redo log /
        // WAL: instead of one wrapping transaction it commits every N chunks.
        $commitEvery = $config->getCommitEvery();
        $usePeriodicCommits = $commitEvery !== null && ! $config->isDryRun();
        $connection = DB::connection($this->dbConnection->name);

        if ($usePeriodicCommits) {
            $connection->beginTransaction();
        }

        try {
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

                if ($usePeriodicCommits && ($chunkIndex + 1) % $commitEvery === 0) {
                    $connection->commit();
                    $connection->beginTransaction();
                }

                $this->memoryManager->maybeCleanup();
                $this->progressTracker->advance($recordsInChunk);

                unset($records);
            }
        } catch (\Throwable $e) {
            if ($usePeriodicCommits && $connection->transactionLevel() > 0) {
                $connection->rollBack();
            }

            throw $e;
        }

        if ($usePeriodicCommits && $connection->transactionLevel() > 0) {
            $connection->commit();
        }

        return $recordsInserted;
    }

    public function getOptimalChunkSize(): int
    {
        return $this->chunkSize;
    }

    /**
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
                if (! $this->isTransientLockError($e) || $attempt >= $maxAttempts - 1) {
                    throw $e;
                }

                $attempt++;
                // Exponential backoff: 200ms, 400ms, 800ms, …
                usleep(100_000 * (2 ** $attempt));
            }
        }
    }

    /**
     * Clamp a chunk size so a single multi-row INSERT never exceeds the driver's
     * bind-parameter ceiling (chunk rows × column count). PostgreSQL and MySQL
     * both hard-fail past 65,535 placeholders in one statement.
     */
    protected function clampChunkSizeToBindLimit(int $chunkSize, int $maxParameters): int
    {
        $columnCount = max(1, count($this->config->columns));
        $maxRows = max(1, intdiv($maxParameters, $columnCount));

        return min($chunkSize, $maxRows);
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
     * @param  array<int, string>  $columns
     * @param  array<int, array<string, mixed>>  $records
     */
    abstract protected function insertChunk(string $table, array $columns, array $records): void;

    abstract protected function determineOptimalChunkSize(): int;
}
