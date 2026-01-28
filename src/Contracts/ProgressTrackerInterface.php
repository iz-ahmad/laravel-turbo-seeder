<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Contracts;

use IzAhmad\TurboSeeder\Enums\SeederStrategy;

interface ProgressTrackerInterface
{
    /**
     * Start tracking progress for the given total.
     */
    public function start(int $total, SeederStrategy $strategy = SeederStrategy::DEFAULT): void;

    /**
     * Advance the progress by the given step.
     */
    public function advance(int $step = 1): void;

    /**
     * Mark the progress as finished.
     */
    public function finish(): void;

    /**
     * Set a custom message for the progress tracker.
     */
    public function setMessage(string $message): void;

    /**
     * Get the current progress percentage.
     */
    public function getPercentage(): float;
}
