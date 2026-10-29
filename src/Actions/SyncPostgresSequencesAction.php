<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Actions;

use Illuminate\Support\Facades\Log;
use IzAhmad\TurboSeeder\Contracts\ProgressTrackerInterface;
use IzAhmad\TurboSeeder\DTOs\DatabaseConnectionDTO;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;
use IzAhmad\TurboSeeder\Services\SqlIdentifier;

/**
 * Explicit values bypass nextval(), so a serial or identity column seeded with its own
 * values would leave the sequence behind and make the next normal insert collide.
 */
final class SyncPostgresSequencesAction
{
    public function __construct(
        private readonly ProgressTrackerInterface $progressTracker,
    ) {}

    public function __invoke(DatabaseConnectionDTO $dbConnection, SeederConfigurationDTO $config): void
    {
        if ($config->isDryRun() || $dbConnection->driver !== DatabaseDriver::PGSQL) {
            return;
        }

        try {
            $this->syncSequences($dbConnection, $config);
        } catch (\Throwable $e) {
            // The rows are already committed, so a failed sync must not turn the seed into a failure.
            $message = "Seeded rows were committed, but the sequences of table [{$config->table}] could not be advanced: {$e->getMessage()}";

            Log::warning('TurboSeeder: '.$message, [
                'table' => $config->table,
                'connection' => $config->connection,
                'exception' => $e,
            ]);

            $this->progressTracker->warn($message);
        }
    }

    private function syncSequences(DatabaseConnectionDTO $dbConnection, SeederConfigurationDTO $config): void
    {
        $quotedTable = SqlIdentifier::quoteTable($config->table, DatabaseDriver::PGSQL, $dbConnection->tablePrefix());

        // A transaction (a savepoint when one is already open) keeps a failed statement
        // from aborting a caller's surrounding transaction.
        $dbConnection->connection->transaction(function ($connection) use ($config, $quotedTable) {
            $sequences = $connection->select(
                'SELECT a.attname AS column_name, pg_get_serial_sequence(?, a.attname) AS sequence_name '
                .'FROM pg_attribute a WHERE a.attrelid = ?::regclass AND a.attnum > 0 AND NOT a.attisdropped',
                [$quotedTable, $quotedTable],
            );

            foreach ($sequences as $sequence) {
                if ($sequence->sequence_name === null || ! in_array($sequence->column_name, $config->columns, true)) {
                    continue;
                }

                $quotedColumn = SqlIdentifier::quoteColumn($sequence->column_name, DatabaseDriver::PGSQL);

                // Only ever raises the sequence, so seeding low explicit ids never makes it hand out ids again.
                $connection->statement(
                    "SELECT setval(?, m) FROM (SELECT MAX({$quotedColumn}) AS m FROM {$quotedTable}) AS t "
                    .'WHERE m IS NOT NULL AND m > COALESCE(pg_sequence_last_value(?::regclass), 0)',
                    [$sequence->sequence_name, $sequence->sequence_name],
                );
            }
        });
    }
}
