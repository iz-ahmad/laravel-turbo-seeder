<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies;

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;

final class MySqlCsvStrategy extends AbstractCsvStrategy
{
    public function supports(DatabaseDriver $driver): bool
    {
        return $driver === DatabaseDriver::MYSQL;
    }

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

        DB::connection($this->dbConnection->name)->statement($sql);
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
            } catch (\Exception $e) {
                throw new \RuntimeException(
                    'MySQL local_infile is disabled and cannot be enabled. '.
                    'Please enable it in your MySQL configuration or use the `default` seeding strategy.',
                    0,
                    $e
                );
            }
        }
    }

    protected function determineOptimalChunkSize(): int
    {
        return config('turbo-seeder.chunk_sizes.mysql', 4000);
    }

    protected function insertChunk(string $table, array $columns, array $records): void
    {
        // not used in CSV strategy, but required by abstract parent. may refactor later.
    }
}
