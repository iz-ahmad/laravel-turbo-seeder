<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies;

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;
use IzAhmad\TurboSeeder\Exceptions\CsvImportFailedException;

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
            $errorMessage = $e->getMessage();

            if ($this->isCopyCommandError($errorMessage)) {
                $shouldFallback = config('turbo-seeder.csv_strategy.fallback_to_default_strategy_on_config_error', true);

                throw new CsvImportFailedException(
                    $this->getCopyCommandErrorMessage($errorMessage),
                    $shouldFallback,
                    $e
                );
            }

            throw new \RuntimeException(
                'PostgreSQL COPY command failed. '.
                'Error: '.$errorMessage,
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

    /**
     * Check if error is related to COPY command access/permissions.
     */
    private function isCopyCommandError(string $errorMessage): bool
    {
        $copyErrorPatterns = [
            'permission denied',
            'could not open file',
            'must be superuser',
            'COPY',
            'access denied',
            'file not found',
        ];

        foreach ($copyErrorPatterns as $pattern) {
            if (stripos($errorMessage, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get user-friendly error message for COPY command errors.
     */
    private function getCopyCommandErrorMessage(string $originalError): string
    {
        return sprintf(
            'PostgreSQL COPY command failed. The database server must have read access to the CSV file and the user must have COPY privileges. '.
            'Original error: %s',
            $originalError
        );
    }
}
