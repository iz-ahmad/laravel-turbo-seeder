<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies;

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;
use IzAhmad\TurboSeeder\Services\CsvReader;

final class SqliteCsvStrategy extends AbstractCsvStrategy
{
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

        foreach ($reader->readChunks($chunkSize) as $chunk) {
            $records = [];

            foreach ($chunk as $row) {
                if (count($row) !== count($columns)) {
                    continue;
                }

                $record = [];
                foreach ($columns as $index => $column) {
                    $record[$column] = $this->parseValue($row[$index] ?? null);
                }

                $records[] = $record;
            }

            if (! empty($records)) {
                $this->insertChunkFromCsv($table, $columns, $records);
            }

            unset($records);

            $this->memoryManager->forceCleanup();
        }

        $reader->close();
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
        $recordCount = count($records);

        $columnNames = implode(',', array_map(fn ($col) => "\"{$col}\"", $columns));

        $singleRowPlaceholders = '('.str_repeat('?,', $columnCount - 1).'?)';
        $allPlaceholders = implode(',', array_fill(0, $recordCount, $singleRowPlaceholders));

        $sql = "INSERT INTO \"{$table}\" ({$columnNames}) VALUES {$allPlaceholders}";

        $bindings = [];
        foreach ($records as $record) {
            foreach ($columns as $column) {
                $bindings[] = $record[$column] ?? null;
            }
        }

        try {
            DB::connection($this->dbConnection->name)->statement($sql, $bindings);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                'Failed to insert chunk into SQLite database.'.
                'Error: '.$e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Parse CSV value back to appropriate type.
     */
    private function parseValue(?string $value): mixed
    {
        if ($value === null || $value === '\\N') {
            return null;
        }

        if ($value === '0' || $value === '1') {
            return (int) $value;
        }

        return $value;
    }

    protected function determineOptimalChunkSize(): int
    {
        return config('turbo-seeder.chunk_sizes.sqlite', 2000);
    }

    protected function insertChunk(string $table, array $columns, array $records): void
    {
        // not used in CSV strategy, but required by abstract parent. will refactor later.
    }
}
