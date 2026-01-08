<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies;

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;

final class PostgreSqlCsvStrategy extends AbstractCsvStrategy
{
    public function supports(DatabaseDriver $driver): bool
    {
        return $driver === DatabaseDriver::PGSQL;
    }

    /**
     * Import data from a CSV file into the database.
     *
     * @param  array<int, string>  $columns
     */
    protected function importFromCsv(string $table, array $columns): void
    {
        $pdo = DB::connection($this->dbConnection->name)->getPdo();
        $filepath = trim(
            $pdo->quote($this->getAbsoluteFilePath()),
            "'"
        );

        $columnNames = implode(',', array_map(fn ($col) => "\"{$col}\"", $columns));

        $sql = "
            COPY \"{$table}\" ({$columnNames})
            FROM '{$filepath}'
            WITH (
                FORMAT csv,
                DELIMITER ',',
                QUOTE '\"',
                ESCAPE '\\',
                NULL '\\N'
            )
        ";

        try {
            DB::connection($this->dbConnection->name)->statement($sql);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                'PostgreSQL COPY command failed. Ensure that the DB server has access to the CSV file and the database user has COPY privileges. '.
                'Error: '.$e->getMessage(),
                0,
                $e
            );
        }
    }

    protected function determineOptimalChunkSize(): int
    {
        return config('turbo-seeder.chunk_sizes.pgsql', 3000);
    }

    protected function insertChunk(string $table, array $columns, array $records): void
    {
        // not used in CSV strategy, but required by abstract parent. will refactor later.
    }
}
