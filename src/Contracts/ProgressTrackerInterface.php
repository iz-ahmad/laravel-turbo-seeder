<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Contracts;

use IzAhmad\TurboSeeder\Enums\SeederStrategy;

interface ProgressTrackerInterface
{
    public function start(int $total, SeederStrategy $strategy = SeederStrategy::DEFAULT): void;

    public function advance(int $step = 1): void;

    public function finish(): void;

    public function setMessage(string $message): void;

    public function getPercentage(): float;
}
