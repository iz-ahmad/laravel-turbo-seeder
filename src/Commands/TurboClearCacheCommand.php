<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Commands;

use Illuminate\Console\Command;
use IzAhmad\TurboSeeder\Actions\CleanupCsvAction;

class TurboClearCacheCommand extends Command
{
    public $signature = 'turbo-seeder:clear-cache
                        {--all : Clear all temporary files including subdirectories}';

    public $description = 'Clear TurboSeeder temporary CSV files and cache';

    public function handle(CleanupCsvAction $cleanupAction): int
    {
        $tempPath = config('turbo-seeder.csv_strategy.temp_path', storage_path('app/turbo-seeder'));

        $this->info('🧹 Clearing TurboSeeder cache...');
        $this->line("Path: {$tempPath}");
        $this->newLine();

        if (! is_dir($tempPath)) {
            $this->warn('⚠ Temp directory does not exist. Nothing to clear.');

            return self::SUCCESS;
        }

        try {
            $deleted = $cleanupAction->cleanupDirectory($tempPath, '*.csv');

            if ($deleted > 0) {
                $this->components->info("✓ Cleared {$deleted} temporary CSV file(s)");
            } else {
                $this->info('ℹ No temporary files found');
            }

            if ($this->option('all')) {
                $this->cleanupSubdirectories($tempPath);
            }

            $size = $this->getDirectorySize($tempPath);
            $this->newLine();
            $this->line('Current cache/temporary files size: '.round($size / 1024 / 1024, 2).' MB');

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->components->error('✗ Failed to clear cache!');
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function cleanupSubdirectories(string $path): void
    {
        $subdirs = glob($path.'/*', GLOB_ONLYDIR);

        if ($subdirs === false || empty($subdirs)) {
            return;
        }

        foreach ($subdirs as $subdir) {
            $files = glob($subdir.'/*.csv');

            if ($files === false) {
                continue;
            }

            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            if ($this->isDirectoryEmpty($subdir)) {
                rmdir($subdir);
            }
        }
    }

    private function isDirectoryEmpty(string $dir): bool
    {
        $handle = opendir($dir);

        if ($handle === false) {
            return true;
        }

        while (false !== ($entry = readdir($handle))) {
            if ($entry !== '.' && $entry !== '..') {
                closedir($handle);

                return false;
            }
        }

        closedir($handle);

        return true;
    }

    private function getDirectorySize(string $path): int
    {
        $size = 0;

        if (! is_dir($path)) {
            return 0;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }
}
