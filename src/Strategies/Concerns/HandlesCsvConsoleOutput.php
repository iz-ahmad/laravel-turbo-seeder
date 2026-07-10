<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies\Concerns;

use IzAhmad\TurboSeeder\Exceptions\CsvImportFailedException;
use IzAhmad\TurboSeeder\Services\ConsoleProgressTrackerAdapter;
use Symfony\Component\Console\Output\OutputInterface;

trait HandlesCsvConsoleOutput
{
    protected function displayStep1Message(): void
    {
        $output = $this->getConsoleOutput();

        if (! $output) {
            return;
        }

        $output->writeln('');
        $output->writeln('<comment> ➤ Step 1/2: Generating CSV file...</comment>');
        $output->writeln('');
    }

    protected function displayStep2Message(): void
    {
        $output = $this->getConsoleOutput();

        if (! $output) {
            return;
        }

        $output->writeln('');
        $output->writeln('<info>   ✓ CSV file generated successfully</info>');

        $output->writeln('');
        $output->write('<comment> ➤ Step 2/2: Importing data from CSV. Wait a bit...<fg=cyan>⏳</></comment>');
    }

    protected function displayImportSuccessMessage(): void
    {
        $output = $this->getConsoleOutput();

        if (! $output) {
            return;
        }

        $output->writeln('');
        $output->writeln('');
        $output->writeln('<info>   ✓ Done! Data imported successfully from CSV file</info>');
    }

    protected function displayFallbackWarning(CsvImportFailedException $exception): void
    {
        $output = $this->getConsoleOutput();

        if (! $output) {
            return;
        }

        $warningMessage = $this->getFallbackWarningMessage();

        $output->writeln('<comment>'.$warningMessage.'</comment>');

        $output->writeln('');
        $output->writeln('<info>💡 To enable CSV strategy:</info>');

        $this->displayConfigurationInstructions($output);

        $output->writeln('');
        $output->writeln('   <fg=gray>Run `php artisan turbo-seeder:test-connection` to verify server-side status.</>');
        $output->writeln('');
        $output->writeln('<fg=cyan>→ Falling back to default strategy (bulk insert)...</fg=cyan>');
        $output->writeln('<fg=cyan>→ Seeding will continue from the beginning.</fg=cyan>');
        $output->writeln('');
    }

    protected function displayConfigurationInstructions(OutputInterface $output): void
    {
        $driver = $this->dbConnection->driver->value;

        if ($driver === 'mysql') {
            $this->displayMySqlInstructions($output);
        } elseif ($driver === 'pgsql') {
            $this->displayPostgreSqlInstructions($output);
        } else {
            $output->writeln('   <fg=gray>See the "CSV Strategy Setup" in README.md for configuration instructions</>');
        }
    }

    protected function displayMySqlInstructions(OutputInterface $output): void
    {
        $output->writeln('');
        $output->writeln('   <fg=white>1. Add `PDO::MYSQL_ATTR_LOCAL_INFILE` to mysql connection options in `config/database.php`.</>');
        $output->writeln('   <fg=white>2. Enable `local_infile` at server-side.</>');
        $output->writeln('');
        $output->writeln('   <fg=gray>‼ Security Note: Only enable in trusted environments</>');
        $output->writeln('   <fg=white>  See the "CSV Strategy Setup" in README.md for detailed configuration instructions.</>');
    }

    protected function displayPostgreSqlInstructions(OutputInterface $output): void
    {
        $tempPath = config('turbo-seeder.csv_strategy.temp_path', storage_path('app/turbo-seeder'));

        $output->writeln('   <fg=white>Ensure PostgreSQL server has read access to CSV files and</>');
        $output->writeln('   <fg=white>the database user has COPY privileges.</>');

        $output->writeln('');
        $output->writeln("   <fg=gray>CSV files are stored in: {$tempPath}</>");
        $output->writeln('   <fg=gray>See the "CSV Strategy Setup" in README.md for full configuration details</>');
    }

    protected function hideLoadingIndicator(): void
    {
        $output = $this->getConsoleOutput();

        if (! $output) {
            return;
        }

        // `\033[1D` moves cursor back 1 position
        $output->write("\033[2D ");
    }

    protected function getConsoleOutput(): ?OutputInterface
    {
        /** @var ConsoleProgressTrackerAdapter $adapter */
        $adapter = app(ConsoleProgressTrackerAdapter::class);

        return $adapter->getOutput($this->progressTracker);
    }
}
