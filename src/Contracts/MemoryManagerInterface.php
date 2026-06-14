<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Contracts;

use IzAhmad\TurboSeeder\Enums\MemoryThreshold;

interface MemoryManagerInterface
{
    public function getCurrentMemoryUsage(): int;

    public function getMemoryUsagePercentage(): float;

    public function getThresholdStatus(): MemoryThreshold;

    public function shouldGarbageCollect(): bool;

    public function maybeCleanup(): void;

    public function getMemoryLimit(): int;

    public function getPeakMemoryUsage(): int;
}
