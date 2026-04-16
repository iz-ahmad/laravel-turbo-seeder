<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies;

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;
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

        $this->insertUsingMultiRowStatement($table, $columns, $records);
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

        $columnNames = implode(',', array_map(fn ($col) => "\"{$col}\"", $columns));

        $singleRowPlaceholders = '('.str_repeat('?,', $columnCount - 1).'?)';
        $allPlaceholders = implode(',', array_fill(0, $recordCount, $singleRowPlaceholders));

        $sql = "INSERT INTO \"{$table}\" ({$columnNames}) VALUES {$allPlaceholders}";

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

        return $configuredSize ?? $defaultSize;
    }
}
