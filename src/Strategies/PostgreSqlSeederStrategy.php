<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies;

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;
use IzAhmad\TurboSeeder\Services\SqlIdentifier;
use IzAhmad\TurboSeeder\Services\ValueFormatter;

final class PostgreSqlSeederStrategy extends AbstractSeederStrategy
{
    public function supports(DatabaseDriver $driver): bool
    {
        return $driver === DatabaseDriver::PGSQL;
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
     * Upsert records using INSERT ... ON CONFLICT (...) DO UPDATE SET ...
     *
     * @param  array<int, string>  $columns
     * @param  array<int, array<string, mixed>>  $records
     * @param  array<int, string>  $upsertKeys
     */
    protected function upsertUsingMultiRowStatement(string $table, array $columns, array $records, array $upsertKeys): void
    {
        $columnCount = count($columns);
        $recordCount = count($records);

        $columnNames = implode(',', array_map(fn ($col) => SqlIdentifier::quoteColumn($col, DatabaseDriver::PGSQL), $columns));
        $quotedTable = SqlIdentifier::quoteTable($table, DatabaseDriver::PGSQL);

        $singleRowPlaceholders = $this->buildSingleRowPlaceholder($columnCount);
        $allPlaceholders = implode(',', array_fill(0, $recordCount, $singleRowPlaceholders));

        $conflictTarget = implode(', ', array_map(fn ($col) => SqlIdentifier::quoteColumn($col, DatabaseDriver::PGSQL), $upsertKeys));

        $updateColumns = array_diff($columns, $upsertKeys);

        if (empty($updateColumns)) {
            $sql = "INSERT INTO {$quotedTable} ({$columnNames}) VALUES {$allPlaceholders} ON CONFLICT ({$conflictTarget}) DO NOTHING";
        } else {
            $updateClause = implode(', ', array_map(
                function ($col) {
                    $q = SqlIdentifier::quoteColumn($col, DatabaseDriver::PGSQL);

                    return "{$q} = EXCLUDED.{$q}";
                },
                $updateColumns,
            ));

            $sql = "INSERT INTO {$quotedTable} ({$columnNames}) VALUES {$allPlaceholders} ON CONFLICT ({$conflictTarget}) DO UPDATE SET {$updateClause}";
        }

        $bindings = [];
        foreach ($records as $record) {
            foreach ($columns as $column) {
                $bindings[] = $this->formatValue($record[$column] ?? null);
            }
        }

        try {
            DB::connection($this->dbConnection->name)->statement($sql, $bindings);
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'Failed to upsert records into PostgreSQL database. '.
                'Error: '.$e->getMessage(),
                0,
                $e
            );
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
        $recordCount = count($records);

        $columnNames = implode(',', array_map(fn ($col) => SqlIdentifier::quoteColumn($col, DatabaseDriver::PGSQL), $columns));
        $quotedTable = SqlIdentifier::quoteTable($table, DatabaseDriver::PGSQL);

        $singleRowPlaceholders = $this->buildSingleRowPlaceholder($columnCount);
        $allPlaceholders = implode(',', array_fill(0, $recordCount, $singleRowPlaceholders));

        $sql = "INSERT INTO {$quotedTable} ({$columnNames}) VALUES {$allPlaceholders}";

        $bindings = [];
        foreach ($records as $record) {
            foreach ($columns as $column) {
                $bindings[] = $this->formatValue($record[$column] ?? null);
            }
        }

        try {
            DB::connection($this->dbConnection->name)->statement($sql, $bindings);
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'Failed to insert records into PostgreSQL database. '.
                'Error: '.$e->getMessage(),
                0,
                $e
            );
        }
    }

    protected function formatValue(mixed $value): mixed
    {
        return ValueFormatter::format($value);
    }

    protected function determineOptimalChunkSize(): int
    {
        $configuredSize = $this->config->getChunkSize();
        $defaultSize = config('turbo-seeder.chunk_sizes.pgsql', config('turbo-seeder.default_chunk_size', 500));

        // PostgreSQL rejects a statement with more than 65,535 bind parameters.
        return $this->clampChunkSizeToBindLimit($configuredSize ?? $defaultSize, 65535);
    }
}
