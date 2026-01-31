<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Services;

use IzAhmad\TurboSeeder\Contracts\ProgressTrackerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

final class ConsoleProgressTrackerAdapter
{
    /**
     * Get the console output instance from the progress tracker or console.
     */
    public function getOutput(ProgressTrackerInterface $tracker): ?OutputInterface
    {
        try {
            if ($tracker instanceof ConsoleProgressTracker) {
                $reflection = new \ReflectionClass($tracker);

                if ($reflection->hasProperty('output')) {
                    $property = $reflection->getProperty('output');
                    $property->setAccessible(true);
                    $output = $property->getValue($tracker);

                    if ($output instanceof OutputInterface) {
                        return $output;
                    }
                }
            }

            if (app()->bound('Illuminate\Console\OutputStyle')) {
                $outputStyle = app('Illuminate\Console\OutputStyle');
                return $outputStyle->getOutput();
            }
        } catch (\Throwable) {
            // failing silently, as console output is optional.
        }

        return null;
    }

    /**
     * Reset the underlying progress tracker state.
     */
    public function reset(ProgressTrackerInterface $tracker): void
    {
        if (! $tracker instanceof ConsoleProgressTracker) {
            return;
        }

        $reflection = new \ReflectionClass($tracker);

        if ($reflection->hasProperty('current')) {
            $currentProperty = $reflection->getProperty('current');
            $currentProperty->setAccessible(true);
            $currentProperty->setValue($tracker, 0);
        }

        if ($reflection->hasProperty('progressBar')) {
            $progressBarProperty = $reflection->getProperty('progressBar');
            $progressBarProperty->setAccessible(true);
            $progressBar = $progressBarProperty->getValue($tracker);

            if ($progressBar instanceof ProgressBar) {
                $progressBar->setProgress(0);
            }
        }
    }
}
