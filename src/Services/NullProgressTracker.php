<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Services;

use IzAhmad\TurboSeeder\Contracts\ProgressTrackerInterface;
use IzAhmad\TurboSeeder\Enums\SeederStrategy;

/**
 * Null Object implementation of ProgressTrackerInterface.
 */
final class NullProgressTracker implements ProgressTrackerInterface
{
    private int $current = 0;

    private int $total = 0;

    public function writeHeader(int $total, SeederStrategy $strategy, string $table): void {}

    public function start(int $total, SeederStrategy $strategy = SeederStrategy::DEFAULT, string $table = ''): void
    {
        $this->total = $total;
        $this->current = 0;
    }

    public function advance(int $step = 1): void
    {
        $this->current += $step;
    }

    public function finish(int $recordsInserted = 0): void
    {
        $this->current = $this->total;
    }

    public function setMessage(string $message): void {}

    public function warn(string $message): void {}

    public function notice(string $message): void {}

    public function getPercentage(): float
    {
        if ($this->total === 0) {
            return 0.0;
        }

        return ($this->current / $this->total) * 100;
    }
}
