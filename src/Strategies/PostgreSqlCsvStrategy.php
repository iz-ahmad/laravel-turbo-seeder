<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies;

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;
use IzAhmad\TurboSeeder\Exceptions\CsvImportFailedException;
use IzAhmad\TurboSeeder\Services\PostgresCopyWriter;
use IzAhmad\TurboSeeder\Services\SqlIdentifier;
use IzAhmad\TurboSeeder\Strategies\Concerns\ClassifiesDatabaseErrors;
use Pdo\Pgsql;

final class PostgreSqlCsvStrategy extends AbstractCsvStrategy
{
    use ClassifiesDatabaseErrors;

    public function supports(DatabaseDriver $driver): bool
    {
        return $driver === DatabaseDriver::PGSQL;
    }

    /**
     * Generate the import file in PostgreSQL COPY *text* format.
     *
     * PDO's pgsqlCopyFromFile consumes COPY text format.
     */
    protected function generateCsvFile(SeederConfigurationDTO $config): void
    {
        $csvConfig = config('turbo-seeder.csv_strategy', []);

        $writer = new PostgresCopyWriter($this->tempFilePath, $csvConfig);
        $writer->open();

        $batchSize = $csvConfig['batch_size'] ?? 10000;
        $batches = (int) ceil($config->count / $batchSize);

        try {
            for ($batch = 0; $batch < $batches; $batch++) {
                $recordsInBatch = min($batchSize, $config->count - ($batch * $batchSize));

                for ($i = 0; $i < $recordsInBatch; $i++) {
                    $index = ($batch * $batchSize) + $i;
                    $writer->writeRecord(($config->generator)($index), $config->columns);
                }

                if ($config->hasProgressTracking()) {
                    $this->progressTracker->advance($recordsInBatch);
                }

                if ($batch > 0 && ($batch % ($csvConfig['gc_frequency'] ?? 5)) === 0) {
                    $this->memoryManager->maybeCleanup();
                }
            }
        } finally {
            $writer->close();
        }
    }

    /**
     * Import data using client-side COPY ... FROM STDIN.
     *
     * pgsqlCopyFromFile streams the file from the PHP host over the existing
     * connection, so it works on managed/containerised PostgreSQL where the
     * server cannot read the application's filesystem and the user is not a
     * superuser.
     *
     * @param  array<int, string>  $columns
     */
    protected function importFromCsv(string $table, array $columns): void
    {
        $pdo = DB::connection($this->dbConnection->name)->getPdo();
        $quotedTable = SqlIdentifier::quoteTable($table, DatabaseDriver::PGSQL);
        $fieldList = implode(',', array_map(fn ($col) => SqlIdentifier::quoteColumn($col, DatabaseDriver::PGSQL), $columns));

        try {
            $result = $this->copyFromFile($pdo, $quotedTable, $this->getAbsoluteFilePath(), $fieldList);

            if ($result === false) {
                $errorInfo = $pdo->errorInfo();

                throw new \RuntimeException(
                    'PostgreSQL COPY FROM STDIN failed: '.($errorInfo[2] ?? 'unknown error')
                );
            }
        } catch (\Throwable $e) {
            if ($this->isCopyCommandError($e)) {
                $shouldFallback = config('turbo-seeder.csv_strategy.fallback_to_default_strategy_on_config_error', true);

                throw new CsvImportFailedException(
                    $this->getCopyCommandErrorMessage($e->getMessage()),
                    $shouldFallback,
                    $e,
                    'pgsql',
                    $table,
                    $this->getAbsoluteFilePath(),
                );
            }

            throw new \RuntimeException(
                'PostgreSQL COPY command failed. Ensure the database user has INSERT privileges on the target table. '.
                'Error: '.$e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Stream a file into the table via COPY ... FROM STDIN, version-safely.
     *
     * PDO::pgsqlCopyFromFile() is deprecated on the base PDO class in PHP 8.4+
     * (superseded by Pdo\Pgsql::copyFromFile()). Laravel provides a base PDO
     * instance, so the modern method is used when it actually is a Pdo\Pgsql and
     * the still-functional base method is called dynamically otherwise.
     */
    private function copyFromFile(\PDO $pdo, string $table, string $path, string $fieldList): bool
    {
        if (PHP_VERSION_ID >= 80400 && $pdo instanceof Pgsql) {
            return $pdo->copyFromFile(
                $table,
                $path,
                PostgresCopyWriter::DELIMITER,
                PostgresCopyWriter::NULL_MARKER,
                $fieldList,
            );
        }

        $copyFromFile = 'pgsqlCopyFromFile';

        return (bool) $pdo->{$copyFromFile}(
            $table,
            $path,
            PostgresCopyWriter::DELIMITER,
            PostgresCopyWriter::NULL_MARKER,
            $fieldList,
        );
    }

    protected function determineOptimalChunkSize(): int
    {
        return config('turbo-seeder.chunk_sizes.pgsql', config('turbo-seeder.default_chunk_size', 500));
    }

    /**
     * Whether the failure is a COPY capability/permission error worth falling
     * back to the default INSERT strategy for.
     *
     * Classified by SQLSTATE (stable, locale-independent) rather than matching
     * English error text. With client-side COPY FROM STDIN this is essentially
     * limited to the connection lacking INSERT privilege on the table.
     */
    private function isCopyCommandError(\Throwable $e): bool
    {
        // 42501 insufficient_privilege, 42P01 undefined_table, 28000 invalid auth.
        $fallbackStates = ['42501', '42P01', '28000', '28P01'];

        return in_array($this->sqlState($e), $fallbackStates, true);
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
