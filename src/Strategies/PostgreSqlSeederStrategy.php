<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies;

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;

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
     * PostgreSQL handles this efficiently with its native insert optimizations.
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

        DB::connection($this->dbConnection->name)->statement($sql, $bindings);
    }

    /**
     * Format value for database insertion.
     */
    protected function formatValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return $value;
    }

    protected function determineOptimalChunkSize(): int
    {
        $configuredSize = $this->config->getChunkSize();

        if ($configuredSize !== 3000) {
            return $configuredSize;
        }

        return config('turbo-seeder.chunk_sizes.pgsql', 3000);
    }
}
