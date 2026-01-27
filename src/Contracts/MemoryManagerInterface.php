<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Contracts;

use IzAhmad\TurboSeeder\Enums\MemoryThreshold;

interface MemoryManagerInterface
{
    /**
     * Get current memory usage in bytes.
     */
    public function getCurrentMemoryUsage(): int;

    /**
     * Get current memory usage as percentage of limit.
     */
    public function getMemoryUsagePercentage(): float;

    /**
     * Get the memory threshold status.
     */
    public function getThresholdStatus(): MemoryThreshold;

    /**
     * Check if garbage collection should be performed.
     */
    public function shouldGarbageCollect(): bool;

    /**
     * Force garbage collection and memory cleanup.
     */
    public function forceCleanup(): void;

    /**
     * Get the configured memory limit in bytes.
     */
    public function getMemoryLimit(): int;

    /**
     * Get peak memory usage in bytes.
     */
    public function getPeakMemoryUsage(): int;
}
