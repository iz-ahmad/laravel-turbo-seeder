<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies\Concerns;

use Illuminate\Support\Facades\Log;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\Exceptions\CsvImportFailedException;
use IzAhmad\TurboSeeder\Services\ConsoleProgressTrackerAdapter;
use IzAhmad\TurboSeeder\Services\StrategyResolver;

/**
 * Trait to handle fallback for CSV strategies.
 */
trait HandlesCsvFallback
{
    /**
     * Fall back to default strategy when CSV import fails.
     */
    protected function fallbackToDefaultStrategy(
        SeederConfigurationDTO $config,
        CsvImportFailedException $exception
    ): int {
        Log::warning('TurboSeeder CSV strategy failed, falling back to default strategy', [
            'error' => $exception->getMessage(),
            'table' => $config->table,
            'driver' => $this->dbConnection->driver->value,
        ]);

        $this->displayFallbackWarning($exception);
        $this->cleanupCsvEnvironment();

        return $this->executeDefaultStrategy($config);
    }

    /**
     * Clean up CSV strategy environment.
     */
    protected function cleanupCsvEnvironment(): void
    {
        if ($this->environmentPrepared) {
            $this->cleanup();
        }
    }

    /**
     * Execute default strategy as fallback.
     */
    protected function executeDefaultStrategy(SeederConfigurationDTO $config): int
    {
        $this->resetProgressTracker();

        $strategyResolver = app(StrategyResolver::class);
        $defaultStrategy = $strategyResolver->resolveDefault($this->dbConnection, $config);

        try {
            $defaultStrategy->prepareEnvironment();
            $recordsInserted = $defaultStrategy->seed($config);

            $defaultStrategy->cleanup();

            return $recordsInserted;
        } catch (\Throwable $e) {
            Log::error('Failed to execute default strategy', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'table' => $config->table,
                'driver' => $this->dbConnection->driver->value,
            ]);

            $defaultStrategy->cleanup(fromException: true);

            throw new \RuntimeException('Failed to execute default strategy', previous: $e);
        }
    }

    /**
     * Get fallback warning message based on database driver.
     */
    protected function getFallbackWarningMessage(): string
    {
        return match ($this->dbConnection->driver->value) {
            'mysql' => '⚠️  CSV strategy failed (MySQL LOAD DATA LOCAL INFILE not available). Falling back to default strategy.',
            'pgsql' => '⚠️  CSV strategy failed (PostgreSQL COPY command not available). Falling back to default strategy.',
            default => '⚠️  CSV strategy failed. Falling back to default strategy.',
        };
    }

    /**
     * Reset progress tracker to 0.
     */
    protected function resetProgressTracker(): void
    {
        /** @var ConsoleProgressTrackerAdapter $adapter */
        $adapter = app(ConsoleProgressTrackerAdapter::class);

        $adapter->reset($this->progressTracker);
    }
}
