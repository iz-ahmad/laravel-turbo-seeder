<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies\Concerns;

use IzAhmad\TurboSeeder\Actions\CleanupCsvAction;

/**
 * Trait to manage temporary CSV files for CSV strategies.
 */
trait ManagesCsvTempFiles
{
    /**
     * Generate temporary file path for CSV.
     */
    protected function generateTempFilePath(string $table): string
    {
        $configuredPath = config('turbo-seeder.csv_strategy.temp_path', storage_path('app/turbo-seeder'));
        $tempDir = rtrim((string) $configuredPath, '/\\');

        $filename = sprintf(
            '%s_%s.csv',
            preg_replace('/[^a-zA-Z0-9_\-]/', '_', $table),
            bin2hex(random_bytes(16))
        );

        return $tempDir.'/'.$filename;
    }

    /**
     * Clean up temporary CSV file.
     */
    protected function cleanupTempFile(): void
    {
        if (! $this->tempFilePath || ! file_exists($this->tempFilePath)) {
            return;
        }

        $cleanupAction = app(CleanupCsvAction::class);
        $cleanupAction($this->tempFilePath);
    }

    /**
     * Get the absolute path to the temporary CSV file.
     */
    protected function getAbsoluteFilePath(): string
    {
        if (! $this->tempFilePath) {
            throw new \RuntimeException('Temp file path not set');
        }

        if (! file_exists($this->tempFilePath)) {
            throw new \RuntimeException('Temp file does not exist: '.$this->tempFilePath);
        }

        return realpath($this->tempFilePath) ?: $this->tempFilePath;
    }
}
