<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Contracts;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Extended progress tracker interface for trackers that'll support
 * output access and reset functionality.
 */
interface ResettableOutputAwareProgressTracker extends ProgressTrackerInterface
{
    /**
     * Get the console output instance if available.
     */
    public function getOutput(): ?OutputInterface;

    /**
     * Reset the progress tracker state to initial values.
     */
    public function reset(): void;
}
