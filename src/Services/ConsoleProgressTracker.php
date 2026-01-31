<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Services;

use IzAhmad\TurboSeeder\Contracts\ProgressTrackerInterface;
use IzAhmad\TurboSeeder\Contracts\ResettableOutputAwareProgressTracker as ResettableOutputAwareProgressTrackerInterface;
use IzAhmad\TurboSeeder\Enums\SeederStrategy;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

final class ConsoleProgressTracker implements ResettableOutputAwareProgressTrackerInterface
{
    private ?ProgressBar $progressBar = null;

    private int $current = 0;

    private int $total = 0;

    private int $lastRateUpdate = 0;

    private bool $finished = false;

    private int $startMemory = 0;

    public function __construct(
        private readonly ?OutputInterface $output = null
    ) {}

    public function start(int $total, SeederStrategy $strategy = SeederStrategy::DEFAULT): void
    {
        $this->total = $total;
        $this->current = 0;
        $this->finished = false;
        $this->startMemory = memory_get_usage(true);

        if ($this->output === null) {
            return;
        }

        $strategyName = $strategy->getDisplayName();
        $this->output->writeln("<fg=cyan>ℹ️  Using {$strategyName} strategy</fg=cyan>");
        $this->output->writeln('');

        $this->progressBar = new ProgressBar($this->output, $total);

        $this->progressBar->setFormat(
            " %current%/%max% [%bar%] %percent:3s%%\n".
            ' 🕐 %elapsed:6s% | 💾 %memory_used% | ⚡ %rate% records/s | ⏳ ~%eta:6s%'
        );

        $this->progressBar->setBarCharacter('<fg=green>█</>');
        $this->progressBar->setEmptyBarCharacter('<fg=gray>░</>');
        $this->progressBar->setProgressCharacter('<fg=green>█</>');

        $this->progressBar->setMessage('0 MiB', 'memory_used');
        $this->progressBar->setMessage('0', 'rate');
        $this->progressBar->setMessage('calculating..', 'eta');

        if ($this->progressBar->getProgress() > 0) {
            $this->progressBar->start();
        }
    }

    public function advance(int $step = 1): void
    {
        $this->current += $step;

        if ($this->progressBar) {
            $this->progressBar->advance($step);

            $this->updateProgress();
        }
    }

    public function finish(): void
    {
        if ($this->finished) {
            return;
        }

        $this->current = $this->total;

        if ($this->progressBar) {
            $this->updateProgress();

            $this->progressBar->finish();
            $this->output?->writeln('');
            $this->output?->writeln('');
        }

        $this->finished = true;
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

        $rate = (int) round($this->current / $elapsed);

        return $rate > 0 ? $rate : 0;
    }

    private function calculateRemaining(): string
    {
        if (! $this->progressBar || $this->current === 0) {
            return 'calculating...';
        }

        $elapsed = microtime(true) - $this->progressBar->getStartTime();
        $rate = $this->current / $elapsed;

        if ($rate <= 0) {
            return 'calculating...';
        }

        $remainingRecords = $this->total - $this->current;
        $remainingSeconds = (int) ($remainingRecords / $rate);

        return gmdate('i:s', $remainingSeconds);
    }

    private function updateProgress(): void
    {
        $now = time();

        if (
            $this->current % 100 === 0 ||
            $now > $this->lastRateUpdate ||
            $this->current === $this->total
        ) {
            $rate = $this->calculateRate();
            $this->progressBar->setMessage((string) $rate, 'rate');
            $this->lastRateUpdate = $now;

            $remaining = $this->calculateRemaining();
            $this->progressBar->setMessage($remaining, 'eta');

            // relative memory usage
            $currentMemory = memory_get_usage(true);
            $memoryUsed = $currentMemory - $this->startMemory;
            $memoryUsedMB = round($memoryUsed / 1024 / 1024, 1);
            $this->progressBar->setMessage($memoryUsedMB.' MiB', 'memory_used');
        }
    }

    /**
     * Get the console output instance if available.
     */
    public function getOutput(): ?OutputInterface
    {
        return $this->output;
    }

    /**
     * Reset the progress tracker state to initial values.
     */
    public function reset(): void
    {
        $this->current = 0;
        $this->finished = false;

        if ($this->progressBar !== null) {
            $this->progressBar->setProgress(0);
        }
    }
}
