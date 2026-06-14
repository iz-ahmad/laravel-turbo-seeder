<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies;

use IzAhmad\TurboSeeder\Actions\CleanupEnvironmentAction;
use IzAhmad\TurboSeeder\Actions\GenerateCsvAction;
use IzAhmad\TurboSeeder\Actions\PrepareEnvironmentAction;
use IzAhmad\TurboSeeder\Contracts\MemoryManagerInterface;
use IzAhmad\TurboSeeder\Contracts\ProgressTrackerInterface;
use IzAhmad\TurboSeeder\Contracts\SeederStrategyInterface;
use IzAhmad\TurboSeeder\DTOs\DatabaseConnectionDTO;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;
use IzAhmad\TurboSeeder\Exceptions\CsvImportFailedException;
use IzAhmad\TurboSeeder\Strategies\Concerns\HandlesCsvConsoleOutput;
use IzAhmad\TurboSeeder\Strategies\Concerns\HandlesCsvFallback;
use IzAhmad\TurboSeeder\Strategies\Concerns\ManagesCsvTempFiles;
use IzAhmad\TurboSeeder\Strategies\Concerns\ManagesEnvironment;

abstract class AbstractCsvStrategy implements SeederStrategyInterface
{
    use HandlesCsvConsoleOutput;
    use HandlesCsvFallback;
    use ManagesCsvTempFiles;
    use ManagesEnvironment;

    protected ?string $tempFilePath = null;

    protected int $chunkSize;

    public function __construct(
        protected readonly DatabaseConnectionDTO $dbConnection,
        protected readonly SeederConfigurationDTO $config,
        protected readonly MemoryManagerInterface $memoryManager,
        protected readonly ProgressTrackerInterface $progressTracker,
        protected readonly PrepareEnvironmentAction $prepareAction,
        protected readonly CleanupEnvironmentAction $cleanupAction,
        protected readonly GenerateCsvAction $generateCsvAction,
    ) {
        $this->chunkSize = $this->determineOptimalChunkSize();
    }

    /**
     * Seed the database using a CSV file.
     */
    public function seed(SeederConfigurationDTO $config): int
    {
        $this->tempFilePath = $this->generateTempFilePath($config->table);

        try {
            // Check import capability BEFORE generating the file so a misconfigured
            // connection falls back immediately instead of wasting minutes writing a
            // CSV that can never be imported.
            $this->preflightImportCapability($config);

            $this->displayStep1Message();
            $this->generateCsvFile($config);

            if ($config->hasProgressTracking()) {
                $this->progressTracker->finish();
            }

            $this->displayStep2Message();

            return $this->performCsvImport($config);
        } catch (CsvImportFailedException $e) {
            if ($e->shouldFallback()) {
                return $this->fallbackToDefaultStrategy($config, $e);
            }

            throw $e;
        } finally {
            $this->cleanupTempFile();
        }
    }

    /**
     * Check whether the native CSV import can run before generating the file.
     *
     * Default: no preflight (drivers without special requirements). Drivers with
     * configuration prerequisites (e.g. MySQL LOCAL INFILE) override this and
     * throw a CsvImportFailedException(shouldFallback: true) when unavailable.
     */
    protected function preflightImportCapability(SeederConfigurationDTO $config): void
    {
        // No-op by default.
    }

    /**
     * Generate CSV file from data generator.
     */
    protected function generateCsvFile(SeederConfigurationDTO $config): void
    {
        $filepath = $this->tempFilePath ?? throw new \RuntimeException('Temp file path not set');

        ($this->generateCsvAction)(
            $filepath,
            $config->columns,
            $config->generator,
            $config->count,
            $config
        );
    }

    /**
     * Perform CSV import into database.
     */
    protected function performCsvImport(SeederConfigurationDTO $config): int
    {
        try {
            $this->importFromCsv($config->table, $config->columns);
        } finally {
            $this->hideLoadingIndicator();
        }

        $this->displayImportSuccessMessage();

        return $config->count;
    }

    /**
     * Import data from CSV file into database.
     *
     * @param  array<int, string>  $columns
     */
    abstract protected function importFromCsv(string $table, array $columns): void;

    /**
     * Check if this strategy supports the given database driver.
     */
    abstract public function supports(DatabaseDriver $driver): bool;

    /**
     * Get the optimal chunk size for this strategy.
     */
    public function getOptimalChunkSize(): int
    {
        return $this->chunkSize;
    }

    /**
     * Determine the optimal chunk size for this strategy.
     */
    abstract protected function determineOptimalChunkSize(): int;
}
