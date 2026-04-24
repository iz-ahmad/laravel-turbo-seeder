<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies;

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;
use IzAhmad\TurboSeeder\Services\SqlIdentifier;
use IzAhmad\TurboSeeder\Services\ValueFormatter;

final class SqliteSeederStrategy extends AbstractSeederStrategy
{
    public function supports(DatabaseDriver $driver): bool
    {
        return $driver === DatabaseDriver::SQLITE;
    }

    /**
     * Insert a chunk of records into the database.
     *
     * @param  array<int, string>  $columns
     * @param  array<int, array<string, mixed>>  $records
     */
    protected function insertChunk(string $table, array $columns, array $records): void
    {
        if (empty($records)) {
            return;
        }

        if ($this->config->isUpsert()) {
            $this->upsertUsingMultiRowStatement($table, $columns, $records, $this->config->getUpsertKeys());
        } else {
            $this->insertUsingMultiRowStatement($table, $columns, $records);
        }
    }

    /**
     * Upsert records using INSERT ... ON CONFLICT (...) DO UPDATE SET / DO NOTHING.
     * Requires SQLite 3.24+ (released 2018). Avoids the destructive DELETE+INSERT of INSERT OR REPLACE.
     *
     * @param  array<int, string>  $columns
     * @param  array<int, array<string, mixed>>  $records
     * @param  array<int, string>  $upsertKeys
     */
    protected function upsertUsingMultiRowStatement(string $table, array $columns, array $records, array $upsertKeys): void
    {
        $columnCount = count($columns);
        $columnNames = implode(',', array_map(fn ($col) => "\"{$col}\"", $columns));
        $singleRowPlaceholders = $this->buildSingleRowPlaceholder($columnCount);

        $conflictTarget = implode(', ', array_map(fn ($col) => "\"{$col}\"", $upsertKeys));
        $updateColumns = array_diff($columns, $upsertKeys);

        if (empty($updateColumns)) {
            $this->insertUsingMultiRowStatement($table, $columns, $records);

            return;
        }

        $updateClause = implode(', ', array_map(
            fn ($col) => "\"{$col}\" = EXCLUDED.\"{$col}\"",
            $updateColumns,
        ));

        // SQLITE_MAX_VARIABLE_NUMBER is 32766 on PHP 8.0+ bundled SQLite (≥ 3.32).
        $maxRowsPerBatch = max(1, (int) floor(32766 / $columnCount));

        foreach (array_chunk($records, $maxRowsPerBatch) as $batch) {
            $allPlaceholders = implode(',', array_fill(0, count($batch), $singleRowPlaceholders));
            $sql = "INSERT INTO ".SqlIdentifier::quoteTable($table, DatabaseDriver::SQLITE)." ({$columnNames}) VALUES {$allPlaceholders} ON CONFLICT ({$conflictTarget}) DO UPDATE SET {$updateClause}";

            $bindings = [];
            foreach ($batch as $record) {
                foreach ($columns as $column) {
                    $bindings[] = $this->formatValue($record[$column] ?? null);
                }
            }

            try {
                DB::connection($this->dbConnection->name)->statement($sql, $bindings);
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    'Failed to upsert records into SQLite database. '.
                    'Error: '.$e->getMessage(),
                    0,
                    $e
                );
            }
        }
    }

    /**
     * Insert records using multi-row INSERT statement.
     *
     * @param  array<int, string>  $columns
     * @param  array<int, array<string, mixed>>  $records
     */
    protected function insertUsingMultiRowStatement(string $table, array $columns, array $records): void
    {
        $columnCount = count($columns);
        $columnNames = implode(',', array_map(fn ($col) => "\"{$col}\"", $columns));
        $singleRowPlaceholders = $this->buildSingleRowPlaceholder($columnCount);

        // SQLITE_MAX_VARIABLE_NUMBER is 32766 on PHP 8.0+ bundled SQLite (≥ 3.32).
        $maxRowsPerBatch = max(1, (int) floor(32766 / $columnCount));

        foreach (array_chunk($records, $maxRowsPerBatch) as $batch) {
            $allPlaceholders = implode(',', array_fill(0, count($batch), $singleRowPlaceholders));
            $sql = "INSERT INTO ".SqlIdentifier::quoteTable($table, DatabaseDriver::SQLITE)." ({$columnNames}) VALUES {$allPlaceholders}";

            $bindings = [];
            foreach ($batch as $record) {
                foreach ($columns as $column) {
                    $bindings[] = $this->formatValue($record[$column] ?? null);
                }
            }

            try {
                DB::connection($this->dbConnection->name)->statement($sql, $bindings);
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    'Failed to insert records into SQLite database. '.
                    'Error: '.$e->getMessage(),
                    0,
                    $e
                );
            }
        }
    }

    protected function formatValue(mixed $value): mixed
    {
        return ValueFormatter::format($value);
    }

    protected function determineOptimalChunkSize(): int
    {
        $configuredSize = $this->config->getChunkSize();
        $defaultSize = config('turbo-seeder.chunk_sizes.sqlite', config('turbo-seeder.default_chunk_size', 500));

        return $configuredSize ?? $defaultSize;
    }
}
