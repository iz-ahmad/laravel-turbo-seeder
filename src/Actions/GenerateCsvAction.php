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

        $batchSize = $csvConfig['batch_size'] ?? 10000;
        $batches = (int) ceil($count / $batchSize);

        try {
            for ($batch = 0; $batch < $batches; $batch++) {
                $recordsInBatch = min($batchSize, $count - ($batch * $batchSize));

                for ($i = 0; $i < $recordsInBatch; $i++) {
                    $index = ($batch * $batchSize) + $i;
                    $record = $generator($index);

                    $row = [];
                    foreach ($columns as $column) {
                        $raw = $record[$column] ?? null;
                        $formatted = ValueFormatter::formatForCsv($raw, $nullMarker);

                        // A non-null value that serializes to exactly the null marker
                        // would be imported as NULL (silent corruption). Fail loudly
                        // instead and tell the user how to resolve it.
                        if ($raw !== null && $formatted === $nullMarker) {
                            throw new \RuntimeException(sprintf(
                                'CSV null-marker collision: column [%s] produced a value identical to the configured null marker [%s] at row %d. '
                                .'Set turbo-seeder.csv_strategy.null_marker to a string your data never contains.',
                                $column,
                                $nullMarker,
                                $index,
                            ));
                        }

                        $row[] = $formatted;
                    }

                    $writer->writeRow($row);
                }

                if ($config->hasProgressTracking()) {
                    $this->progressTracker->advance($recordsInBatch);
                }

                if ($batch > 0 && ($batch % ($csvConfig['gc_frequency'] ?? 5)) === 0) {
                    $this->memoryManager->maybeCleanup();
                }
            }
        } finally {
            $writer->close();
        }

        return $filepath;
    }
}
