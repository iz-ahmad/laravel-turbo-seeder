<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Actions;

use IzAhmad\TurboSeeder\Contracts\MemoryManagerInterface;
use IzAhmad\TurboSeeder\Contracts\ProgressTrackerInterface;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\Services\CsvWriter;
use IzAhmad\TurboSeeder\Services\ValueFormatter;

final class GenerateCsvAction
{
    public function __construct(
        private readonly MemoryManagerInterface $memoryManager,
        private readonly ProgressTrackerInterface $progressTracker,
    ) {}

    /**
     * Generate CSV file from the data generator.
     *
     * @param  array<int, string>  $columns
     */
    public function __invoke(
        string $filepath,
        array $columns,
        \Closure $generator,
        int $count,
        SeederConfigurationDTO $config
    ): string {
        $csvConfig = config('turbo-seeder.csv_strategy', []);
        $nullMarker = $csvConfig['null_marker'] ?? '\\N';

        $writer = new CsvWriter($filepath, $csvConfig);
        $writer->open();

        try {
            if ($config->useBatchGenerator()) {
                $this->generateFromBatchGenerator($writer, $generator, $columns, $count, $config, $nullMarker, $csvConfig);
            } else {
                $this->generateFromSingleGenerator($writer, $generator, $columns, $count, $config, $nullMarker, $csvConfig);
            }
        } finally {
            $writer->close();
        }

        return $filepath;
    }

    /**
     * Generate CSV using single-record generator.
     *
     * @param  array<int, string>  $columns
     * @param  array<string, mixed>  $csvConfig
     */
    private function generateFromSingleGenerator(
        CsvWriter $writer,
        \Closure $generator,
        array $columns,
        int $count,
        SeederConfigurationDTO $config,
        string $nullMarker,
        array $csvConfig
    ): void {
        $batchSize = $csvConfig['batch_size'] ?? 10000;
        $batches = (int) ceil($count / $batchSize);

        for ($batch = 0; $batch < $batches; $batch++) {
            $recordsInBatch = min($batchSize, $count - ($batch * $batchSize));

            for ($i = 0; $i < $recordsInBatch; $i++) {
                $index = ($batch * $batchSize) + $i;
                $record = $generator($index);

                $row = [];
                foreach ($columns as $column) {
                    $row[] = ValueFormatter::formatForCsv($record[$column] ?? null, $nullMarker);
                }

                $writer->writeRow($row);
            }

            if ($config->hasProgressTracking()) {
                $this->progressTracker->advance($recordsInBatch);
            }

            if ($batch > 0 && ($batch % ($csvConfig['gc_frequency'] ?? 5)) === 0) {
                $this->memoryManager->forceCleanup();
            }
        }
    }

    /**
     * Generate CSV using batch generator.
     *
     * @param  array<int, string>  $columns
     * @param  array<string, mixed>  $csvConfig
     */
    private function generateFromBatchGenerator(
        CsvWriter $writer,
        \Closure $batchGenerator,
        array $columns,
        int $count,
        SeederConfigurationDTO $config,
        string $nullMarker,
        array $csvConfig
    ): void {
        $csvBatchSize = $csvConfig['batch_size'] ?? 10000;
        $recordsGenerated = 0;
        $batchIndex = 0;

        while ($recordsGenerated < $count) {
            $startIndex = $recordsGenerated;
            $remainingRecords = $count - $recordsGenerated;
            $batchSize = min($csvBatchSize, $remainingRecords);

            $records = $batchGenerator($startIndex, $batchSize);

            if (! is_array($records) || empty($records)) {
                break;
            }

            foreach ($records as $record) {
                if (! is_array($record)) {
                    continue;
                }

                $row = [];
                foreach ($columns as $column) {
                    $row[] = ValueFormatter::formatForCsv($record[$column] ?? null, $nullMarker);
                }

                $writer->writeRow($row);
            }

            $batchCount = count($records);
            $recordsGenerated += $batchCount;

            if ($config->hasProgressTracking()) {
                $this->progressTracker->advance($batchCount);
            }

            if ($batchIndex > 0 && ($batchIndex % ($csvConfig['gc_frequency'] ?? 5)) === 0) {
                $this->memoryManager->forceCleanup();
            }

            $batchIndex++;

            if ($batchCount < $batchSize) {
                break;
            }
        }
    }
}
