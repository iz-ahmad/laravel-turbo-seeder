<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Actions;

use IzAhmad\TurboSeeder\Contracts\MemoryManagerInterface;
use IzAhmad\TurboSeeder\Contracts\ProgressTrackerInterface;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\Services\CsvWriter;

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

        $writer = new CsvWriter($filepath, $csvConfig);
        $writer->open();

        $batchSize = $csvConfig['batch_size'] ?? 10000;
        $batches = (int) ceil($count / $batchSize);

        if (empty($columns)) {
            $firstRecord = $generator(0);
            $columns = array_keys($firstRecord);
        }

        for ($batch = 0; $batch < $batches; $batch++) {
            $recordsInBatch = min($batchSize, $count - ($batch * $batchSize));

            for ($i = 0; $i < $recordsInBatch; $i++) {
                $index = ($batch * $batchSize) + $i;
                $record = $generator($index);

                $row = [];
                foreach ($columns as $column) {
                    $row[] = $this->formatValue($record[$column] ?? null);
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

        $writer->close();

        return $filepath;
    }

    /**
     * Format value for CSV output.
     */
    private function formatValue(mixed $value): string
    {
        $nullValue = '\\N';
        if ($value === null) {
            return $nullValue;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }
}
