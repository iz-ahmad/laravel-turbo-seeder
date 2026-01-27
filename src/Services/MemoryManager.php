<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Services;

use IzAhmad\TurboSeeder\Contracts\MemoryManagerInterface;
use IzAhmad\TurboSeeder\Enums\MemoryThreshold;

final class MemoryManager implements MemoryManagerInterface
{
    private int $memoryLimit;

    private int $gcThresholdPercent;

    private int $gcCounter = 0;

    private int $forceGcAfterChunks;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        $memoryConfig = $config['memory'] ?? [];

        $this->memoryLimit = ($memoryConfig['limit_mb'] ?? 256) * 1024 * 1024;
        $this->gcThresholdPercent = $memoryConfig['gc_threshold_percent'] ?? 80;
        $this->forceGcAfterChunks = $memoryConfig['force_gc_after_chunks'] ?? 10;
    }

    public function getCurrentMemoryUsage(): int
    {
        return memory_get_usage(true);
    }

    public function getMemoryUsagePercentage(): float
    {
        return ($this->getCurrentMemoryUsage() / $this->memoryLimit) * 100;
    }

    public function getThresholdStatus(): MemoryThreshold
    {
        return MemoryThreshold::fromPercentage($this->getMemoryUsagePercentage());
    }

    public function shouldGarbageCollect(): bool
    {
        $this->gcCounter++;

        if ($this->gcCounter >= $this->forceGcAfterChunks) {
            return true;
        }

        return $this->getMemoryUsagePercentage() >= $this->gcThresholdPercent;
    }

    public function forceCleanup(): void
    {
        if ($this->shouldGarbageCollect()) {
            gc_collect_cycles();
            $this->gcCounter = 0;
        }
    }

    public function getMemoryLimit(): int
    {
        return $this->memoryLimit;
    }

    public function getPeakMemoryUsage(): int
    {
        return memory_get_peak_usage(true);
    }
}
