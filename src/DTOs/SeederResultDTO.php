<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\DTOs;

final readonly class SeederResultDTO
{
    public function __construct(
        public bool $success,
        public int $recordsInserted,
        public float $durationSeconds = 0.0,
        public int $peakMemoryBytes = 0,
        public ?string $errorMessage = null,
        public bool $isDryRun = false,
        public ?\Throwable $exception = null,
    ) {}

    public function getRecordsPerSecond(): float
    {
        if ($this->durationSeconds <= 0) {
            return 0.0;
        }

        return round($this->recordsInserted / $this->durationSeconds, 2);
    }

    public function getPeakMemoryInMB(): float
    {
        return round($this->peakMemoryBytes / 1024 / 1024, 2);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'records_inserted' => $this->recordsInserted,
            'duration_seconds' => $this->durationSeconds,
            'peak_memory_mb' => $this->getPeakMemoryInMB(),
            'records_per_second' => $this->getRecordsPerSecond(),
            'error_message' => $this->errorMessage,
            'is_dry_run' => $this->isDryRun,
        ];
    }
}
