<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Actions;

final class CleanupCsvAction
{
    /**
     * Clean up temporary CSV file.
     */
    public function __invoke(string $filepath): void
    {
        if (file_exists($filepath)) {
            if (! unlink($filepath)) {
                throw new \RuntimeException("Failed to delete CSV file: {$filepath}");
            }
        }
    }

    /**
     * Clean up multiple CSV files.
     *
     * @param  array<int, string>  $filepaths
     */
    public function cleanupMultiple(array $filepaths): void
    {
        foreach ($filepaths as $filepath) {
            $this->__invoke($filepath);
        }
    }

    /**
     * Clean up all CSV files in a directory.
     */
    public function cleanupDirectory(string $directory, string $pattern = '*.csv'): int
    {
        if (! is_dir($directory)) {
            return 0;
        }

        $files = glob($directory.'/'.$pattern);
        $deleted = 0;

        if ($files === false) {
            return 0;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
