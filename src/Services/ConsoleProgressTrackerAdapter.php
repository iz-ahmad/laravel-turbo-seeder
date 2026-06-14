<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Services;

use Illuminate\Support\Facades\Log;
use IzAhmad\TurboSeeder\Contracts\ProgressTrackerInterface;
use IzAhmad\TurboSeeder\Contracts\ResettableOutputAwareProgressTracker;
use Symfony\Component\Console\Output\OutputInterface;

final class ConsoleProgressTrackerAdapter
{
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
        } catch (\Throwable $e) {
            Log::debug('TurboSeeder: could not resolve console output', ['exception' => $e]);
        }

        return null;
    }

    public function reset(ProgressTrackerInterface $tracker): void
    {
        if ($tracker instanceof ResettableOutputAwareProgressTracker) {
            $tracker->reset();
        }
    }
}
