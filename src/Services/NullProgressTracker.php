<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Services;

use IzAhmad\TurboSeeder\Contracts\ProgressTrackerInterface;

/**
 * Null Object implementation of ProgressTrackerInterface.
 * Performs no tracking and produces no output.
 */
final class NullProgressTracker implements ProgressTrackerInterface
{
    private int $current = 0;

    private int $total = 0;

    public function start(int $total): void
    {
        $this->total = $total;
        $this->current = 0;
    }

    public function advance(int $step = 1): void
    {
        $this->current += $step;
    }

    public function finish(): void
    {
        $this->current = $this->total;
    }

    public function setMessage(string $message): void
    {
        // no-op here, but have usage in seeder strategies
    }

    public function getPercentage(): float
    {
        if ($this->total === 0) {
            return 0.0;
        }

        return ($this->current / $this->total) * 100;
    }
}
