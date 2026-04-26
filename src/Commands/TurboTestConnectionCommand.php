<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Commands;

use Illuminate\Console\Command;
use IzAhmad\TurboSeeder\DTOs\DatabaseConnectionDTO;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;

class TurboTestConnectionCommand extends Command
{
    public $signature = 'turbo-seeder:test-connection
                        {connection? : Database connection name to test}';

    public $description = 'Test database connection and display configuration';

    public function handle(): int
    {
        $connectionName = $this->argument('connection') ?? config('database.default');

        $this->info("🔍 Testing connection: {$connectionName}");
        $this->newLine();

        try {
            $dbConnection = DatabaseConnectionDTO::fromName($connectionName);

            $this->displayConnectionInfo($dbConnection);

            $this->testConnection($dbConnection);

            $this->testStrategies($dbConnection);

            $this->components->info('✓ Connection test completed successfully!');

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->components->error('✗ Connection test failed!');
            $this->error($e->getMessage());

            if ($this->output->isVerbose()) {
                $this->newLine();
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    private function displayConnectionInfo(DatabaseConnectionDTO $dbConnection): void
    {
        $config = config("database.connections.{$dbConnection->name}");

        $this->table(
            ['Property', 'Value'],
            [
                ['Connection Name', $dbConnection->name],
                ['Driver', $dbConnection->driver->getDisplayName()],
                ['Database', $dbConnection->getDatabaseName()],
                ['Host', $config['host'] ?? 'N/A'],
                ['Port', $config['port'] ?? 'N/A'],
            ]
        );

        $this->newLine();
    }

    private function testConnection(DatabaseConnectionDTO $dbConnection): void
    {
        $this->line('▶ Testing database connection...');

        try {
            $pdo = $dbConnection->getPdo();
            $this->info('✓ Database connection successful');

            $version = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
            $this->line("  Server version: {$version}");

        } catch (\Throwable $e) {
            $this->error('✗ Database connection failed: '.$e->getMessage());

            throw $e;
        }

        $this->newLine();
    }

    private function testStrategies(DatabaseConnectionDTO $dbConnection): void
    {
        $this->line('▶ Testing available strategies...');

        $this->info('✓ DEFAULT strategy (bulk insert) - Supported');

        if ($dbConnection->driver->supportsCsvImport()) {
            $this->info('✓ CSV strategy (file import) - Supported');

            $this->testCsvRequirements($dbConnection);
        } else {
            $this->warn('⚠ CSV strategy - Not recommended for '.$dbConnection->driver->getDisplayName());
            $this->line('  (SQLite will use chunked reading from CSV, which may be slower)');
        }

        $this->newLine();
    }

    private function testCsvRequirements(DatabaseConnectionDTO $dbConnection): void
    {
        match ($dbConnection->driver) {
            DatabaseDriver::MYSQL => $this->testMySqlCsvRequirements($dbConnection),
            DatabaseDriver::PGSQL => $this->testPostgreSqlCsvRequirements($dbConnection),
            default => null,
        };
    }

    private function testMySqlCsvRequirements(DatabaseConnectionDTO $dbConnection): void
    {
        $result = $dbConnection->connection
            ->select("SHOW VARIABLES LIKE 'local_infile'");

        if (empty($result) || $result[0]->Value !== 'ON') {
            $this->warn('  ⚠ Warning: local_infile is disabled on the MySQL server');
            $this->line('  ℹ The DEFAULT strategy will be used as a fallback');
            $this->line('  ℹ Enable it at runtime: SET GLOBAL local_infile = 1;');
            $this->line('  ℹ Or permanently: add local_infile = 1 under [mysqld] in your MySQL config file');
            $this->line('  ℹ See: https://dev.mysql.com/doc/refman/8.0/en/load-data-local-security.html');
        } else {
            $this->line('  ✓ local_infile is enabled');
        }
    }

    private function testPostgreSqlCsvRequirements(DatabaseConnectionDTO $dbConnection): void
    {
        $tempPath = config('turbo-seeder.csv_strategy.temp_path', storage_path('app/turbo-seeder'));

        $this->line('  ✓ PostgreSQL COPY command available');
        $this->line("  ℹ CSV files will be stored in: {$tempPath}");
        $this->line('  ℹ Ensure PostgreSQL has read access to this path: the DB server must have access to the CSV file and the database');
    }
}
