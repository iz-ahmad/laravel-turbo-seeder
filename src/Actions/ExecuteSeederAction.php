<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use IzAhmad\TurboSeeder\Contracts\ProgressTrackerInterface;
use IzAhmad\TurboSeeder\Contracts\SeederStrategyInterface;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\DTOs\SeederResultDTO;
use IzAhmad\TurboSeeder\Events\TurboSeederCompleted;

final class ExecuteSeederAction
{
    public function __construct(
        private readonly ProgressTrackerInterface $progressTracker,
    ) {}

    /**
     * Execute the seeding operation using the provided strategy.
     */
    public function __invoke(
        SeederStrategyInterface $strategy,
        SeederConfigurationDTO $config
    ): SeederResultDTO {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        try {
            $this->validateColumns($config);

            $strategy->prepareEnvironment();

            if ($config->hasProgressTracking()) {
                $this->progressTracker->start($config->count, $config->strategy);
            }

            $recordsInserted = $strategy->seed($config);

            if ($config->hasProgressTracking()) {
                $this->progressTracker->finish();
            }

            $strategy->cleanup();

            $duration = microtime(true) - $startTime;
            $peakMemory = memory_get_peak_usage(true) - $startMemory;

            $result = new SeederResultDTO(
                success: true,
                recordsInserted: $recordsInserted,
                durationSeconds: $duration,
                peakMemoryBytes: $peakMemory,
                isDryRun: $config->isDryRun(),
            );

            Event::dispatch(new TurboSeederCompleted($config->table, $result));

            return $result;

        } catch (\Throwable $e) {
            $strategy->cleanup(fromException: true);

            Log::error('TurboSeeder: seeding failed', [
                'table' => $config->table,
                'connection' => $config->connection,
                'file' => $e->getFile(),
                'exception' => $e,
            ]);

            return new SeederResultDTO(
                success: false,
                recordsInserted: 0,
                errorMessage: $e->getMessage(),
            );
        }
    }

    /**
     * Validate that all declared columns exist on the target table.
     * Skipped when shouldValidateColumns() returns false.
     * Throws when the table itself does not exist.
     * Silently skips column-level checks only when the schema builder cannot
     * introspect the driver (getColumnListing returns empty on an existing table).
     */
    private function validateColumns(SeederConfigurationDTO $config): void
    {
        if (! $config->shouldValidateColumns()) {
            return;
        }

        $schemaBuilder = DB::connection($config->connection)->getSchemaBuilder();

        if (! $schemaBuilder->hasTable($config->table)) {
            throw new \InvalidArgumentException(
                "Table [{$config->table}] does not exist on connection [{$config->connection}]."
            );
        }

        $tableColumns = $schemaBuilder->getColumnListing($config->table);

        if (empty($tableColumns)) {
            return;
        }

        $missingColumns = array_diff($config->columns, $tableColumns);

        if (! empty($missingColumns)) {
            throw new \InvalidArgumentException(sprintf(
                'Column(s) [%s] do not exist on table [%s]. Available columns: [%s].',
                implode(', ', $missingColumns),
                $config->table,
                implode(', ', $tableColumns),
            ));
        }
    }
}
