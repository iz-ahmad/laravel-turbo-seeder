<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Contracts;

use Symfony\Component\Console\Output\OutputInterface;

interface ResettableOutputAwareProgressTracker extends ProgressTrackerInterface
{
    public function getOutput(): ?OutputInterface;

    public function reset(): void;
}
