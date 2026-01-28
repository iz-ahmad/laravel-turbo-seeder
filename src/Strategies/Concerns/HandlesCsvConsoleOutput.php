<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies\Concerns;

use IzAhmad\TurboSeeder\Services\ConsoleProgressTrackerAdapter;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait to handle console output for CSV strategies.
 */
trait HandlesCsvConsoleOutput
{
    /**
     * Display Step 1 message.
     */
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

    /**
     * Display Step 2 message.
     */
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

    /**
     * Display import success message.
     */
    protected function displayImportSuccessMessage(): void
    {
        $output = $this->getConsoleOutput();

        if (! $output) {
            return;
        }

        $output->writeln('');
        $output->writeln('');
        $output->writeln('<info>   ✓ Done! Data imported successfully from CSV</info>');
    }

    /**
     * Display fallback warning with instructions.
     */
    protected function displayFallbackWarning(\IzAhmad\TurboSeeder\Exceptions\CsvImportFailedException $exception): void
    {
        $output = $this->getConsoleOutput();

        if (! $output) {
            return;
        }

        $warningMessage = $this->getFallbackWarningMessage();

        $output->writeln('');
        $output->writeln('<fg=yellow>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</>');
        $output->writeln('<fg=yellow>⚠  CSV IMPORT FAILED - AUTOMATIC FALLBACK</>');
        $output->writeln('<fg=yellow>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</>');

        $output->writeln('');
        $output->writeln('<comment>'.$warningMessage.'</comment>');

        $output->writeln('');
        $output->writeln('<info>💡 To enable CSV strategy for better performance:</info>');

        $this->displayConfigurationInstructions($output);

        $output->writeln('');
        $output->writeln('<fg=cyan>→ Switching to default strategy (bulk insert)...</fg=cyan>');
        $output->writeln('<fg=cyan>→ Seeding will continue from the beginning...</fg=cyan>');
        $output->writeln('');
    }

    /**
     * Display configuration instructions in console.
     */
    protected function displayConfigurationInstructions(OutputInterface $output): void
    {
        $driver = $this->dbConnection->driver->value;

        if ($driver === 'mysql') {
            $this->displayMySqlInstructions($output);
        } elseif ($driver === 'pgsql') {
            $this->displayPostgreSqlInstructions($output);
        } else {
            $output->writeln('   <fg=gray>See README.md for configuration instructions</>');
        }
    }

    /**
     * Display MySQL configuration instructions.
     */
    protected function displayMySqlInstructions(OutputInterface $output): void
    {
        $output->writeln('   <fg=white>Add this to your config/database.php in the mysql connection:</>');

        $output->writeln('');
        $output->writeln('   <fg=green>\'options\' => [</>');
        $output->writeln('   <fg=green>    PDO::MYSQL_ATTR_LOCAL_INFILE => true,</>');
        $output->writeln('   <fg=green>],</>');

        $output->writeln('');
        $output->writeln('   <fg=gray>⚠  Security Note: Only enable in trusted environments</>');
        $output->writeln('   <fg=gray>   See README.md for full details</>');
    }

    /**
     * Display PostgreSQL configuration instructions.
     */
    protected function displayPostgreSqlInstructions(OutputInterface $output): void
    {
        $tempPath = config('turbo-seeder.csv_strategy.temp_path', storage_path('app/turbo-seeder'));

        $output->writeln('   <fg=white>Ensure PostgreSQL server has read access to CSV files and</>');
        $output->writeln('   <fg=white>the database user has COPY privileges.</>');

        $output->writeln('');
        $output->writeln('   <fg=gray>CSV files are stored in: '.$tempPath.'/</>');
        $output->writeln('   <fg=gray>See README.md for full configuration details</>');
    }

    /**
     * Hide the loading indicator emoji.
     */
    protected function hideLoadingIndicator(): void
    {
        $output = $this->getConsoleOutput();

        if (! $output) {
            return;
        }

        // `\033[1D` moves cursor back 1 position
        $output->write("\033[2D ");
    }

    /**
     * Get console output if available.
     */
    protected function getConsoleOutput(): ?OutputInterface
    {
        /** @var ConsoleProgressTrackerAdapter $adapter */
        $adapter = app(ConsoleProgressTrackerAdapter::class);

        return $adapter->getOutput($this->progressTracker);
    }
}
