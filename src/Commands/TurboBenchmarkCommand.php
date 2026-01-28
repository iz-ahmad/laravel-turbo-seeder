<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Commands;

use Illuminate\Console\Command;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

class TurboBenchmarkCommand extends Command
{
    public $signature = 'turbo-seeder:benchmark
                        {--connection= : Database connection name}
                        {--table=benchmark_test : Table name for benchmarking}
                        {--records=10000 : Number of records to seed}';

    public $description = 'Benchmark TurboSeeder performance (default vs CSV strategies)';

    public function handle(): int
    {
        $connection = $this->option('connection') ?? config('database.default');
        $table = $this->option('table');
        $records = (int) $this->option('records');
        $columnCount = 5;

        $this->info('🏁 Starting TurboSeeder Performance Benchmark...');
        $this->info("Connection: {$connection}");
        $this->info("Table: {$table} (containing {$columnCount} columns)");
        $this->info('Records: '.number_format($records));
        $this->newLine();

        try {
            $driver = $this->detectDriver($connection);
            $this->info("Detected Driver: {$driver->getDisplayName()}");
            $this->newLine();

            $this->createBenchmarkTable($table, $connection, $driver);

            $results = [];

            $this->info("<fg=yellow>Please wait while we proceed with the testing... ⏳<fg=cyan>");
            $this->newLine();
            $this->line('🔄 Testing DEFAULT strategy...');
            $results['default'] = $this->benchmarkStrategy($table, $records, false, $connection);

            $this->truncateTable($table, $connection, $driver);

            if ($driver->supportsCsvImport()) {
                $this->line('🔄 Testing CSV strategy...');
                $results['csv'] = $this->benchmarkStrategy($table, $records, true, $connection);

                $this->truncateTable($table, $connection, $driver);
            }

            $this->displayResults($results, $records);

            $this->dropBenchmarkTable($table, $connection, $driver);

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('✗ Benchmark failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function benchmarkStrategy(string $table, int $records, bool $useCsv, string $connection): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $seederBuilder = TurboSeeder::create($table)
            ->columns(['name', 'email', 'value', 'created_at'])
            ->generate(fn ($i) => [
                'name' => "User {$i}",
                'email' => "user{$i}@benchmark.test",
                'value' => random_int(1, 1000),
                'created_at' => now()->toDateTimeString(),
            ])
            ->count($records)
            ->connection($connection)
            ->withoutProgressTracking();

        if ($useCsv) {
            $seederBuilder->useCsvStrategy();
        }

        $seederBuilder->run();

        $duration = microtime(true) - $startTime;
        $memoryUsed = (memory_get_peak_usage(true) - $startMemory) / 1024 / 1024;

        return [
            'duration' => $duration,
            'memory' => $memoryUsed,
            'rate' => round($records / $duration),
        ];
    }

    private function displayResults(array $results, int $records): void
    {
        $this->newLine();
        $this->info('📊 Benchmark Results:');
        $this->newLine();

        $tableData = [];

        if (isset($results['default'])) {
            $tableData[] = [
                'DEFAULT',
                round($results['default']['duration'], 2).' s',
                round($results['default']['memory'], 2).' MB',
                number_format($results['default']['rate']).' rec/s',
            ];
        }

        if (isset($results['csv'])) {
            $tableData[] = [
                'CSV',
                round($results['csv']['duration'], 2).' s',
                round($results['csv']['memory'], 2).' MB',
                number_format($results['csv']['rate']).' rec/s',
            ];
        }

        $this->table(
            ['Strategy', 'Duration', 'Peak Memory', 'Rate'],
            $tableData
        );

        if (isset($results['default'], $results['csv'])) {
            $speedup = $results['default']['duration'] / $results['csv']['duration'];
            $this->newLine();
            $this->info('⚡ CSV is '.round($speedup, 2).'x faster than DEFAULT');
        }
    }

    private function detectDriver(string $connection): DatabaseDriver
    {
        $driver = \DB::connection($connection)->getDriverName();

        return DatabaseDriver::fromString($driver);
    }

    private function createBenchmarkTable(string $table, string $connection, DatabaseDriver $driver): void
    {
        $this->dropBenchmarkTable($table, $connection, $driver);

        match ($driver) {
            DatabaseDriver::MYSQL => $this->createMySqlTable($table, $connection),
            DatabaseDriver::PGSQL => $this->createPostgreSqlTable($table, $connection),
            DatabaseDriver::SQLITE => $this->createSqliteTable($table, $connection),
        };
    }

    private function createMySqlTable(string $table, string $connection): void
    {
        \DB::connection($connection)->statement("
            CREATE TABLE `{$table}` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `email` VARCHAR(255) NOT NULL,
                `value` INT NOT NULL,
                `created_at` TIMESTAMP NOT NULL
            )
        ");
    }

    private function createPostgreSqlTable(string $table, string $connection): void
    {
        \DB::connection($connection)->statement("
            CREATE TABLE \"{$table}\" (
                id BIGSERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                value INTEGER NOT NULL,
                created_at TIMESTAMP NOT NULL
            )
        ");
    }

    private function createSqliteTable(string $table, string $connection): void
    {
        \DB::connection($connection)->statement("
            CREATE TABLE \"{$table}\" (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                value INTEGER NOT NULL,
                created_at TIMESTAMP NOT NULL
            )
        ");
    }

    private function truncateTable(string $table, string $connection, DatabaseDriver $driver): void
    {
        match ($driver) {
            DatabaseDriver::MYSQL => \DB::connection($connection)->statement("
                TRUNCATE TABLE `{$table}`
            "),
            DatabaseDriver::PGSQL => \DB::connection($connection)->statement("
                TRUNCATE TABLE \"{$table}\"
            "),
            DatabaseDriver::SQLITE => \DB::connection($connection)->statement("
                DELETE FROM \"{$table}\"
            "),
        };
    }

    private function dropBenchmarkTable(string $table, string $connection, DatabaseDriver $driver): void
    {
        match ($driver) {
            DatabaseDriver::MYSQL => \DB::connection($connection)->statement("
                DROP TABLE IF EXISTS `{$table}`
            "),
            DatabaseDriver::PGSQL => \DB::connection($connection)->statement("
                DROP TABLE IF EXISTS \"{$table}\"
            "),
            DatabaseDriver::SQLITE => \DB::connection($connection)->statement("
                DROP TABLE IF EXISTS \"{$table}\"
            "),
        };
    }
}
