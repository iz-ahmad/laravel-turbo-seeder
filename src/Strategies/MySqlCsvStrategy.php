<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies;

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;
use IzAhmad\TurboSeeder\Exceptions\CsvImportFailedException;

final class MySqlCsvStrategy extends AbstractCsvStrategy
{
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
        $this->ensureLocalInfileEnabled();

        $pdo = DB::connection($this->dbConnection->name)->getPdo();
        $filepath = trim(
            $pdo->quote($this->getAbsoluteFilePath()),
            "'"
        );

        $columnNames = implode(',', array_map(fn ($col) => "`{$col}`", $columns));

        $sql = "
            LOAD DATA LOCAL INFILE '{$filepath}'
            INTO TABLE `{$table}`
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            ESCAPED BY '\\\\'
            LINES TERMINATED BY '\\n'
            ({$columnNames})
        ";

        try {
            DB::connection($this->dbConnection->name)->statement($sql);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            if ($this->isLocalInfileError($errorMessage)) {
                $shouldFallback = config('turbo-seeder.csv_strategy.fallback_to_default_strategy_on_config_error', true);

                throw new CsvImportFailedException(
                    $this->getLocalInfileErrorMessage($errorMessage),
                    $shouldFallback,
                    $e
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
     * Ensure LOCAL INFILE is enabled for MySQL.
     */
    private function ensureLocalInfileEnabled(): void
    {
        $result = DB::connection($this->dbConnection->name)
            ->select("SHOW VARIABLES LIKE 'local_infile'");

        if (empty($result) || $result[0]->Value !== 'ON') {
            try {
                DB::connection($this->dbConnection->name)
                    ->statement('SET GLOBAL local_infile = 1');
            } catch (\Exception) {
                // letting the actual import attempt fail and trigger fallback
            }
        }
    }

    /**
     * Check if error is related to LOCAL_INFILE configuration.
     */
    private function isLocalInfileError(string $errorMessage): bool
    {
        $localInfilePatterns = [
            'LOAD DATA LOCAL INFILE is forbidden',
            'local_infile',
            'MYSQL_ATTR_LOCAL_INFILE',
            'mysqli.allow_local_infile',
            'mysqli.local_infile_directory',
            'PDO::MYSQL_ATTR_LOCAL_INFILE',
            'PDO::MYSQL_ATTR_LOCAL_INFILE_DIRECTORY',
            'LOCAL INFILE',
        ];

        foreach ($localInfilePatterns as $pattern) {
            if (stripos($errorMessage, $pattern) !== false) {
                return true;
            }
        }

        return false;
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

    protected function insertChunk(string $table, array $columns, array $records): void
    {
        // not used in CSV strategy, but required by abstract parent. may refactor later.
    }
}
