<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Actions;

use Illuminate\Database\Schema\Builder;
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
use IzAhmad\TurboSeeder\Helpers\TurboData;

final class ExecuteSeederAction
{
    public function __construct(
        private readonly ProgressTrackerInterface $progressTracker,
        private readonly GuardAgainstProductionAction $guardAgainstProduction,
    ) {}

    /**
     * Execute the seeding operation using the provided strategy.
     */
    public function __invoke(
        SeederStrategyInterface $strategy,
        SeederConfigurationDTO $config
    ): SeederResultDTO {
        $startMemory = memory_get_usage(true);

        try {
            ($this->guardAgainstProduction)($config);

            $this->validateColumns($config);
            $this->validateUpsertKeys($config);

            $this->truncateIfRequested($config);

            $startTime = microtime(true);

            Event::dispatch(new TurboSeederStarting(
                $config->table,
                $config->count,
                $config->strategy,
                $config->connection,
            ));

            $this->progressTracker->writeHeader($config->count, $config->strategy, $config->table);

            foreach ($config->pendingWarnings as $warning) {
                Log::warning($warning);
                $this->progressTracker->warn($warning);
            }

            $strategy->prepareEnvironment();

            if ($config->hasProgressTracking()) {
                $this->progressTracker->start($config->count, $config->strategy, $config->table);
            }

            TurboData::markGeneratorActive(true);
            try {
                $recordsInserted = $strategy->seed($config);
            } finally {
                TurboData::markGeneratorActive(false);
            }

            $this->progressTracker->finish($recordsInserted);

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
     * ON CONFLICT / ON DUPLICATE KEY require the conflict target to match a real unique constraint.
     */
    private function validateUpsertKeys(SeederConfigurationDTO $config): void
    {
        if (! $config->isUpsert() || ! $config->shouldValidateColumns()) {
            return;
        }

        try {
            $indexes = DB::connection($config->connection)->getSchemaBuilder()->getIndexes($config->table);
        } catch (\Throwable) {
            // Index introspection is unavailable probably on this driver/Laravel version
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
     * Empty the target table before seeding when truncate() was requested.
     *
     * On MySQL, TRUNCATE resets AUTO_INCREMENT with foreign key checks disabled.
     * On PostgreSQL/SQLite, DELETE is used — PostgreSQL refuses to TRUNCATE a referenced table.
     */
    private function truncateIfRequested(SeederConfigurationDTO $config): void
    {
        if (($config->options['truncate'] ?? false) !== true) {
            return;
        }

        $this->progressTracker->notice("<fg=yellow>⏳ Truncating table [{$config->table}]...</>");

        $connection = DB::connection($config->connection);

        if ($connection->getDriverName() === 'mysql') {
            $connection->statement('SET FOREIGN_KEY_CHECKS=0');

            try {
                $connection->table($config->table)->truncate();
            } finally {
                $connection->statement('SET FOREIGN_KEY_CHECKS=1');
            }
        } else {
            $connection->table($config->table)->delete();
        }

        $this->progressTracker->notice("<fg=green>✓ Truncated table [{$config->table}]</>");
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

        $this->validateNotNullCoverage($config, $schemaBuilder);
    }

    private function validateNotNullCoverage(SeederConfigurationDTO $config, Builder $schemaBuilder): void
    {
        try {
            $schemaColumns = $schemaBuilder->getColumns($config->table);
        } catch (\Throwable) {
            // Driver/Laravel version doesn't support getColumns()
            return;
        }

        $uncovered = [];

        foreach ($schemaColumns as $col) {
            if ($col['auto_increment'] === true) {
                continue;
            }

            if (($col['generation'] ?? null) !== null) {
                continue;
            }

            if ($col['nullable'] === true) {
                continue;
            }

            if ($col['default'] !== null) {
                continue;
            }

            if (! in_array($col['name'], $config->columns, true)) {
                $uncovered[] = $col['name'];
            }
        }

        if (empty($uncovered)) {
            return;
        }

        throw new \InvalidArgumentException(sprintf(
            'NOT NULL column(s) [%s] on table [%s] are missing from the seeded columns. '
            .'Add them to columns() or use withoutColumnValidation() to skip this check.',
            implode(', ', $uncovered),
            $config->table,
        ));
    }
}
