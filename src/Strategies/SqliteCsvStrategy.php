<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies;

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;
use IzAhmad\TurboSeeder\Services\CsvReader;
use IzAhmad\TurboSeeder\Services\SqlIdentifier;
use IzAhmad\TurboSeeder\Strategies\Concerns\ResolvesSqliteVariableLimit;

final class SqliteCsvStrategy extends AbstractCsvStrategy
{
    use ResolvesSqliteVariableLimit;

    public function supports(DatabaseDriver $driver): bool
    {
        return $driver === DatabaseDriver::SQLITE;
    }

    protected function importFromCsv(string $table, array $columns): void
    {
        $csvConfig = config('turbo-seeder.csv_strategy', []);
        $filepath = $this->getAbsoluteFilePath();

        $reader = new CsvReader($filepath, $csvConfig);
        $reader->open();

        $chunkSize = $csvConfig['reader_chunk_size_for_sqlite'] ?? 500;
        $nullMarker = $csvConfig['null_marker'] ?? '\\N';

        try {
            foreach ($reader->readChunks($chunkSize) as $chunk) {
                $records = [];

                foreach ($chunk as $row) {
                    if (count($row) !== count($columns)) {
                        continue;
                    }

                    $record = [];
                    foreach ($columns as $index => $column) {
                        $record[$column] = $this->parseValue($row[$index] ?? null, $nullMarker);
                    }

                    $records[] = $record;
                }

                if (! empty($records)) {
                    $this->insertChunkFromCsv($table, $columns, $records);
                }

                unset($records);

                $this->memoryManager->maybeCleanup();
            }
        } finally {
            $reader->close();
        }
    }

    /**
     * Insert a chunk of records to the database from CSV data.
     *
     * @param  array<int, string>  $columns
     * @param  array<int, array<string, mixed>>  $records
     */
    private function insertChunkFromCsv(string $table, array $columns, array $records): void
    {
        $columnCount = count($columns);
        $columnNames = implode(',', array_map(fn ($col) => "\"{$col}\"", $columns));
        $singleRowPlaceholders = '('.str_repeat('?,', $columnCount - 1).'?)';
        $quotedTable = SqlIdentifier::quoteTable($table, DatabaseDriver::SQLITE);

        $maxRowsPerBatch = $this->sqliteMaxRowsPerBatch($columnCount);

        foreach (array_chunk($records, $maxRowsPerBatch) as $batch) {
            $allPlaceholders = implode(',', array_fill(0, count($batch), $singleRowPlaceholders));
            $sql = "INSERT INTO {$quotedTable} ({$columnNames}) VALUES {$allPlaceholders}";

            $bindings = [];
            foreach ($batch as $record) {
                foreach ($columns as $column) {
                    $bindings[] = $record[$column] ?? null;
                }
            }

            try {
                DB::connection($this->dbConnection->name)->statement($sql, $bindings);
            } catch (\Exception $e) {
                throw new \RuntimeException(
                    'Failed to insert chunk into SQLite database. '.
                    'Error: '.$e->getMessage(),
                    0,
                    $e
                );
            }
        }
    }

    /**
     * Parse CSV value back to appropriate type.
     */
    private function parseValue(?string $value, string $nullMarker = '\\N'): mixed
    {
        if ($value === null || $value === $nullMarker) {
            return null;
        }

        return $value;
    }

    protected function determineOptimalChunkSize(): int
    {
        return config('turbo-seeder.chunk_sizes.sqlite', config('turbo-seeder.default_chunk_size', 500));
    }
}
