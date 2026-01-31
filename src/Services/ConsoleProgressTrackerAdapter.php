<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Services;

use IzAhmad\TurboSeeder\Contracts\ProgressTrackerInterface;
use IzAhmad\TurboSeeder\Contracts\ResettableOutputAwareProgressTracker;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Adapter to access extended functionality of progress trackers
 * that support output access and reset operations.
 */
final class ConsoleProgressTrackerAdapter
{
    /**
     * Get the console output instance from the progress tracker or console.
     */
    public function getOutput(ProgressTrackerInterface $tracker): ?OutputInterface
    {
        try {
            if ($tracker instanceof ResettableOutputAwareProgressTracker) {
                return $tracker->getOutput();
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
        if ($tracker instanceof ResettableOutputAwareProgressTracker) {
            $tracker->reset();
        }
    }
}
