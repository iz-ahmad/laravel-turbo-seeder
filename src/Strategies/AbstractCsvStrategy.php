<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies;

use IzAhmad\TurboSeeder\Actions\CleanupCsvAction;
use IzAhmad\TurboSeeder\Actions\GenerateCsvAction;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;

abstract class AbstractCsvStrategy extends AbstractSeederStrategy
{
    protected ?string $tempFilePath = null;

    /**
     * Seed the database using a CSV file.
     */
    public function seed(SeederConfigurationDTO $config): int
    {
        $this->tempFilePath = $this->generateTempFilePath($config->table);

        try {
            $this->progressTracker->setMessage('Generating CSV file...');

            $generateAction = app(GenerateCsvAction::class);
            $generateAction(
                $this->tempFilePath,
                $config->columns,
                $config->generator,
                $config->count,
                $config
            );

            $this->progressTracker->setMessage('Importing from CSV...');

            $this->importFromCsv($config->table, $config->columns);

            return $config->count;

        } finally {
            $this->cleanupTempFile();
        }
    }

    /**
     * Import data from CSV file into database.
     *
     * @param  array<int, string>  $columns
     */
    abstract protected function importFromCsv(string $table, array $columns): void;

    /**
     * Generate temporary file path for CSV.
     */
    protected function generateTempFilePath(string $table): string
    {
        $tempDir = config('turbo-seeder.csv_strategy.temp_path', storage_path('app/turbo-seeder'));
        $filename = sprintf(
            '%s_%s_%s.csv',
            $table,
            uniqid('', true),
            time()
        );

        return $tempDir.'/'.$filename;
    }

    /**
     * Clean up temporary CSV file.
     */
    protected function cleanupTempFile(): void
    {
        if ($this->tempFilePath && file_exists($this->tempFilePath)) {
            $cleanupAction = app(CleanupCsvAction::class);
            $cleanupAction($this->tempFilePath);
        }
    }

    /**
     * Get the absolute path to the temporary CSV file.
     */
    protected function getAbsoluteFilePath(): string
    {
        if (! $this->tempFilePath) {
            throw new \RuntimeException('Temp file path not set');
        }

        return realpath($this->tempFilePath) ?: $this->tempFilePath;
    }
}
