<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Services;

use IzAhmad\TurboSeeder\Contracts\ProgressTrackerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

final class ConsoleProgressTracker implements ProgressTrackerInterface
{
    private ?ProgressBar $progressBar = null;

    private int $current = 0;

    private int $total = 0;

    public function __construct(
        private readonly ?OutputInterface $output = null
    ) {}

    public function start(int $total): void
    {
        $this->total = $total;
        $this->current = 0;

        if ($this->output === null) {
            return;
        }

        $this->progressBar = new ProgressBar($this->output, $total);

        $this->progressBar->setFormat(
            " %current%/%max% [%bar%] %percent:3s%%\n".
            " ⏱  %elapsed:6s% | 💾 %memory:6s% | ⚡ %rate% records/s | ⏳ ~%estimated:-6s%"
        );

        $this->progressBar->setBarCharacter('<fg=green>●</>');
        $this->progressBar->setEmptyBarCharacter('<fg=red>○</>');
        $this->progressBar->setProgressCharacter('<fg=green>▶</>');

        $this->progressBar->start();
    }

    public function advance(int $step = 1): void
    {
        $this->current += $step;

        if ($this->progressBar) {
            $this->progressBar->advance($step);

            $rate = $this->calculateRate();
            $this->progressBar->setMessage((string) $rate, 'rate');
        }
    }

    public function finish(): void
    {
        if ($this->progressBar) {
            $this->progressBar->finish();
            $this->output?->writeln('');
            $this->output?->writeln('');
        }

        $this->current = $this->total;
    }

    public function setMessage(string $message): void
    {
        $this->progressBar?->setMessage($message);
        $this->progressBar?->display();
    }

    public function getPercentage(): float
    {
        if ($this->total === 0) {
            return 0.0;
        }

        return ($this->current / $this->total) * 100;
    }

    /**
     * Calculate the rate of records processed per second.
     */
    private function calculateRate(): int
    {
        if (! $this->progressBar) {
            return 0;
        }

        $elapsed = microtime(true) - $this->progressBar->getStartTime();

        if ($elapsed <= 0) {
            return 0;
        }

        return (int) round($this->current / $elapsed);
    }
}
