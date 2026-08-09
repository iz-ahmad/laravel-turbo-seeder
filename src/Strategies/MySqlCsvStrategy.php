<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies;

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;
use IzAhmad\TurboSeeder\Exceptions\CsvImportFailedException;
use IzAhmad\TurboSeeder\Services\MySqlPdoAttributes;
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
     * Fall back before generating the CSV when LOCAL INFILE is unavailable —
     * either the PDO client option is off or the server's local_infile is
     * disabled. Avoids writing a large file that could never be imported.
     */
    protected function preflightImportCapability(SeederConfigurationDTO $config): void
    {
        $connection = DB::connection($this->dbConnection->name);

        $options = $connection->getConfig('options') ?? [];
        $clientEnabled = ! empty($options[MySqlPdoAttributes::localInfileAttribute()]);

        $serverEnabled = true;

        try {
            $row = $connection->selectOne('SELECT @@GLOBAL.local_infile AS enabled');
            // The value may come back as an int (1/0) or a string ('ON'/'OFF')
            $value = strtolower((string) ($row->enabled ?? ''));
            $serverEnabled = $row !== null && in_array($value, ['1', 'on', 'true'], true);
        } catch (\Throwable) {
            // Cannot determine the server setting
            $serverEnabled = true;
        }

        if ($clientEnabled && $serverEnabled) {
            return;
        }

        $reason = ! $clientEnabled
            ? 'MYSQL ATTR_LOCAL_INFILE is not enabled on the connection'
            : 'local_infile is disabled on the MySQL server';

        throw new CsvImportFailedException(
            "MySQL CSV import is unavailable ({$reason}); falling back without generating the CSV. See README for configuration guideline.",
            config('turbo-seeder.csv_strategy.fallback_to_default_strategy_on_config_error', true),
            null,
            'mysql',
            $config->table,
            null,
        );
    }

    /**
     * Import data from a CSV file into the database.
     *
     * @param  array<int, string>  $columns
     */
    protected function importFromCsv(string $table, array $columns): void
    {
        $pdo = DB::connection($this->dbConnection->name)->getPdo();
        // Normalise to forward slashes: MySQL treats backslashes in the LOAD DATA path as escape characters
        $filepath = str_replace('\\', '/', $this->assertSafeCsvPath($this->getAbsoluteFilePath()));

        $quotedTable = SqlIdentifier::quoteTable($table, DatabaseDriver::MYSQL);

        $nullMarker = config('turbo-seeder.csv_strategy.null_marker', '\\N');
        $quotedNullMarker = $pdo->quote($nullMarker);

        // Each column is read into a user variable, then assigned via NULLIF so the null marker becomes a real NULL
        $userVars = [];
        $setClauses = [];
        foreach ($columns as $i => $col) {
            $var = "@ts_col_{$i}";
            $userVars[] = $var;
            $setClauses[] = SqlIdentifier::quoteColumn($col, DatabaseDriver::MYSQL)." = NULLIF({$var}, {$quotedNullMarker})";
        }

        $columnVarList = implode(',', $userVars);
        $setClause = implode(', ', $setClauses);

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
        return in_array($this->driverErrno($e), [1148, 3948, 2068], true);
    }

    /**
     * Get user-friendly error message for LOCAL_INFILE errors.
     */
    private function getLocalInfileErrorMessage(string $originalError): string
    {
        return sprintf(
            'MySQL LOAD DATA LOCAL INFILE not available. The PDO connection must have `PDO::MYSQL_ATTR_LOCAL_INFILE` or `Pdo\Mysql::ATTR_LOCAL_INFILE` enabled for CSV strategy. See README.md for detailed configuration instructions.'.
            'Original error: %s',
            $originalError
        );
    }

    protected function determineOptimalChunkSize(): int
    {
        return config('turbo-seeder.chunk_sizes.mysql', config('turbo-seeder.default_chunk_size', 500));
    }
}
