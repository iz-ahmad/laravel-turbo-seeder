<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies;

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;
use IzAhmad\TurboSeeder\Exceptions\CsvImportFailedException;
use IzAhmad\TurboSeeder\Services\SqlIdentifier;
use IzAhmad\TurboSeeder\Strategies\Concerns\ClassifiesDatabaseErrors;

final class MySqlCsvStrategy extends AbstractCsvStrategy
{
    use ClassifiesDatabaseErrors;

    public function supports(DatabaseDriver $driver): bool
    {
        return $driver === DatabaseDriver::MYSQL;
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

        $quotedTable = SqlIdentifier::quoteTable($table, DatabaseDriver::MYSQL);

        $nullMarker = config('turbo-seeder.csv_strategy.null_marker', '\\N');
        $quotedNullMarker = $pdo->quote($nullMarker);

        // Each column is read into a user variable, then assigned via NULLIF so the
        // null marker becomes a real NULL. Without this, `ESCAPED BY ''` disables
        // MySQL's native \N interpretation and every NULL would import as the literal
        // marker string (silent corruption).
        $userVars = [];
        $setClauses = [];
        foreach ($columns as $i => $col) {
            $var = "@ts_col_{$i}";
            $userVars[] = $var;
            $setClauses[] = "`{$col}` = NULLIF({$var}, {$quotedNullMarker})";
        }

        $columnVarList = implode(',', $userVars);
        $setClause = implode(', ', $setClauses);

        // PDO::MYSQL_ATTR_LOCAL_INFILE must be enabled on the connection; if not,
        // the import will fail and trigger an automatic fallback to the default strategy.
        $sql = "
            LOAD DATA LOCAL INFILE '{$filepath}'
            INTO TABLE {$quotedTable}
            FIELDS TERMINATED BY ','
            OPTIONALLY ENCLOSED BY '\"'
            ESCAPED BY ''
            LINES TERMINATED BY '\\n'
            ({$columnVarList})
            SET {$setClause}
        ";

        try {
            DB::connection($this->dbConnection->name)->statement($sql);
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();

            if ($this->isLocalInfileError($e)) {
                $shouldFallback = config('turbo-seeder.csv_strategy.fallback_to_default_strategy_on_config_error', true);

                throw new CsvImportFailedException(
                    $this->getLocalInfileErrorMessage($errorMessage),
                    $shouldFallback,
                    $e,
                    'mysql',
                    $table,
                    $filepath,
                );
            }

            throw new \RuntimeException(
                'MySQL LOAD DATA LOCAL INFILE command failed. '.
                'Error: '.$errorMessage,
                0,
                $e
            );
        }
    }

    /**
     * Whether the failure is a LOCAL INFILE capability error worth falling back
     * to the default strategy for. Classified by MySQL error number rather than
     * by matching English error text.
     */
    private function isLocalInfileError(\Throwable $e): bool
    {
        // 1148 ER_NOT_ALLOWED_COMMAND, 3948 ER_LOAD_DATA_LOCAL_INFILE_DISABLED,
        // 2068 CR_LOAD_DATA_LOCAL_INFILE_REJECTED (client-side rejection).
        return in_array($this->driverErrno($e), [1148, 3948, 2068], true);
    }

    /**
     * Get user-friendly error message for LOCAL_INFILE errors.
     */
    private function getLocalInfileErrorMessage(string $originalError): string
    {
        return sprintf(
            'MySQL LOAD DATA LOCAL INFILE not available. The PDO connection must have `PDO::MYSQL_ATTR_LOCAL_INFILE` enabled for CSV strategy. See README.md for detailed configuration instructions.'.
            'Original error: %s',
            $originalError
        );
    }

    protected function determineOptimalChunkSize(): int
    {
        return config('turbo-seeder.chunk_sizes.mysql', config('turbo-seeder.default_chunk_size', 500));
    }
}
