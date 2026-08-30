<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Contracts;

use IzAhmad\TurboSeeder\Enums\SeederStrategy;

interface ProgressTrackerInterface
{
    /**
     * Write the seeder info header — called unconditionally, independent of progress tracking.
     */
    public function writeHeader(int $total, SeederStrategy $strategy, string $table): void;

    public function start(int $total, SeederStrategy $strategy = SeederStrategy::DEFAULT, string $table = ''): void;

    public function advance(int $step = 1): void;

    public function finish(int $recordsInserted = 0): void;

    public function setMessage(string $message): void;

    public function getPercentage(): float;

    public function warn(string $message): void;

    public function notice(string $message): void;
}
