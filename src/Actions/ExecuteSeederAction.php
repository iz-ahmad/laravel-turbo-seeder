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
use IzAhmad\TurboSeeder\Events\TurboSeederFailed;
use IzAhmad\TurboSeeder\Events\TurboSeederStarting;

final class ExecuteSeederAction
{
    public function __construct(
        private readonly ProgressTrackerInterface $progressTracker,
    ) {}

    public function __invoke(
        SeederStrategyInterface $strategy,
        SeederConfigurationDTO $config
    ): SeederResultDTO {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        try {
            $this->validateColumns($config);
            $this->validateUpsertKeys($config);

            $this->truncateIfRequested($config);

            Event::dispatch(new TurboSeederStarting(
                $config->table,
                $config->count,
                $config->strategy,
                $config->connection,
            ));

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
            if ($config->hasProgressTracking()) {
                $this->progressTracker->finish();
            }

            $strategy->cleanup(fromException: true);

            Log::error('TurboSeeder: seeding failed', [
                'table' => $config->table,
                'connection' => $config->connection,
                'file' => $e->getFile(),
                'exception' => $e,
            ]);

            Event::dispatch(new TurboSeederFailed($config->table, $config->connection, $e));

            return new SeederResultDTO(
                success: false,
                recordsInserted: 0,
                errorMessage: $e->getMessage(),
                exception: $e,
            );
        }
    }

    /**
     * Fail fast when upsert keys are not backed by a unique/primary index.
     *
     * ON CONFLICT (PostgreSQL/SQLite) and ON DUPLICATE KEY (MySQL) require the
     * conflict target to match a real unique constraint, otherwise the statement
     * errors mid-run. Skipped when column validation is disabled or when the
     * driver's schema builder cannot introspect indexes (older Laravel).
     */
    private function validateUpsertKeys(SeederConfigurationDTO $config): void
    {
        if (! $config->isUpsert() || ! $config->shouldValidateColumns()) {
            return;
        }

        try {
            $indexes = DB::connection($config->connection)->getSchemaBuilder()->getIndexes($config->table);
        } catch (\Throwable) {
            // Index introspection is unavailable on this driver/Laravel version;
            // skip the pre-flight check rather than block seeding.
            return;
        }

        $keys = array_map('strtolower', $config->getUpsertKeys());
        sort($keys);

        foreach ($indexes as $index) {
            if ($index['unique'] !== true && $index['primary'] !== true) {
                continue;
            }

            $columns = array_map('strtolower', array_values(array_filter($index['columns'], 'is_string')));
            sort($columns);

            if ($columns === $keys) {
                return;
            }
        }

        throw new \InvalidArgumentException(sprintf(
            'upsert(): column(s) [%s] are not backed by a unique or primary index on table [%s]. '
            .'Add a matching unique constraint, or use the exact column(s) of an existing one.',
            implode(', ', $config->getUpsertKeys()),
            $config->table,
        ));
    }

    /**
     * On PostgreSQL and SQLite a DELETE is used instead of TRUNCATE: PostgreSQL
     * refuses to TRUNCATE a table referenced by a foreign key (even by an empty
     * child), and DELETE needs no superuser. Empty child tables first if the
     * target still has referencing rows.
     */
    private function truncateIfRequested(SeederConfigurationDTO $config): void
    {
        if (($config->options['truncate'] ?? false) !== true) {
            return;
        }

        $connection = DB::connection($config->connection);

        if ($connection->getDriverName() === 'mysql') {
            $connection->statement('SET FOREIGN_KEY_CHECKS=0');

            try {
                $connection->table($config->table)->truncate();
            } finally {
                $connection->statement('SET FOREIGN_KEY_CHECKS=1');
            }

            return;
        }

        $connection->table($config->table)->delete();
    }

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
            Log::warning('TurboSeeder: skipping column validation; schema introspection returned no columns.', [
                'table' => $config->table,
                'connection' => $config->connection,
            ]);

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
