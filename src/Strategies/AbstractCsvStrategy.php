<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies;

use Illuminate\Support\Facades\Log;
use IzAhmad\TurboSeeder\Actions\CleanupCsvAction;
use IzAhmad\TurboSeeder\Actions\ExecuteSeederAction;
use IzAhmad\TurboSeeder\Actions\GenerateCsvAction;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\Exceptions\CsvImportFailedException;
use IzAhmad\TurboSeeder\Services\StrategyResolver;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCsvStrategy extends AbstractSeederStrategy
{
    protected ?string $tempFilePath = null;

    /**
     * Seed the database using a CSV file.
     */
    public function seed(SeederConfigurationDTO $config): int
    {
        $this->tempFilePath = $this->generateTempFilePath($config->table);

        try {
            $this->displayStep1Message();
            $this->generateCsvFile($config);
            $this->displayStep2Message();

            return $this->performCsvImport($config);
        } catch (CsvImportFailedException $e) {
            if ($e->shouldFallback()) {
                return $this->fallbackToDefaultStrategy($config, $e);
            }

            throw $e;
        } finally {
            $this->cleanupTempFile();
        }
    }

    /**
     * Generate CSV file from data generator.
     */
    protected function generateCsvFile(SeederConfigurationDTO $config): void
    {
        $generateAction = app(GenerateCsvAction::class);
        $generateAction(
            $this->tempFilePath,
            $config->columns,
            $config->generator,
            $config->count,
            $config
        );
    }

    /**
     * Perform CSV import into database.
     */
    protected function performCsvImport(SeederConfigurationDTO $config): int
    {
        $this->importFromCsv($config->table, $config->columns);

        $this->displayImportSuccessMessage();

        return $config->count;
    }

    /**
     * Fall back to default strategy when CSV import fails.
     */
    protected function fallbackToDefaultStrategy(
        SeederConfigurationDTO $config,
        CsvImportFailedException $exception
    ): int {
        Log::warning('TurboSeeder CSV strategy failed, falling back to default strategy', [
            'error' => $exception->getMessage(),
            'table' => $config->table,
            'driver' => $this->dbConnection->driver->value,
        ]);

        $this->displayFallbackWarning($exception);
        $this->cleanupCsvEnvironment();

        return $this->executeDefaultStrategy($config);
    }

    /**
     * Display fallback warning with instructions.
     */
    protected function displayFallbackWarning(CsvImportFailedException $exception): void
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
     * Clean up CSV strategy environment.
     */
    protected function cleanupCsvEnvironment(): void
    {
        if ($this->environmentPrepared) {
            $this->cleanup();
        }
    }

    /**
     * Execute default strategy as fallback.
     */
    protected function executeDefaultStrategy(SeederConfigurationDTO $config): int
    {
        $this->resetProgressTracker();

        $strategyResolver = app(StrategyResolver::class);
        $defaultStrategy = $strategyResolver->resolveDefault($this->dbConnection, $config);

        // $defaultStrategy->prepareEnvironment();
        // $recordsInserted = $defaultStrategy->seed($config);

        // $defaultStrategy->cleanup();

        // return $recordsInserted;

        $executeSeederAction = app(ExecuteSeederAction::class);
        $result = $executeSeederAction($defaultStrategy, $config);

        return $result->recordsInserted;
    }

    /**
     * Get fallback warning message based on database driver.
     */
    protected function getFallbackWarningMessage(): string
    {
        return match ($this->dbConnection->driver->value) {
            'mysql' => '⚠️  CSV strategy failed (MySQL LOAD DATA LOCAL INFILE not available). Falling back to default strategy.',
            'pgsql' => '⚠️  CSV strategy failed (PostgreSQL COPY command not available). Falling back to default strategy.',
            default => '⚠️  CSV strategy failed. Falling back to default strategy.',
        };
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
     * Display Step 1 message.
     */
    protected function displayStep1Message(): void
    {
        $output = $this->getConsoleOutput();

        if (! $output) {
            return;
        }

        $output->writeln('');
        $output->writeln('<comment>📝 Step 1/2: Generating CSV file...</comment>');
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

        $output->writeln('<info>   ✓ CSV file generated successfully</info>');
        $output->writeln('');
        $output->writeln('<comment>📥 Step 2/2: Importing data from CSV...</comment>');
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
        $output->writeln('<info>   ✓ Data imported successfully from CSV</info>');
    }

    /**
     * Get console output if available.
     */
    protected function getConsoleOutput(): ?OutputInterface
    {
        try {
            if ($this->progressTracker instanceof \IzAhmad\TurboSeeder\Services\ConsoleProgressTracker) {
                $reflection = new \ReflectionClass($this->progressTracker);

                if ($reflection->hasProperty('output')) {
                    $property = $reflection->getProperty('output');
                    $property->setAccessible(true);
                    $output = $property->getValue($this->progressTracker);

                    if ($output instanceof OutputInterface) {
                        return $output;
                    }
                }
            }

            if (app()->bound('Illuminate\Console\OutputStyle')) {
                $outputStyle = app('Illuminate\Console\OutputStyle');

                if (method_exists($outputStyle, 'getOutput')) {
                    return $outputStyle->getOutput();
                }
            }
        } catch (\Throwable) {
            // failing silently
        }

        return null;
    }

    /**
     * Reset progress tracker to 0.
     */
    protected function resetProgressTracker(): void
    {
        if (! $this->progressTracker instanceof \IzAhmad\TurboSeeder\Services\ConsoleProgressTracker) {
            return;
        }

        $reflection = new \ReflectionClass($this->progressTracker);

        if ($reflection->hasProperty('current')) {
            $currentProperty = $reflection->getProperty('current');
            $currentProperty->setAccessible(true);
            $currentProperty->setValue($this->progressTracker, 0);
        }

        if ($reflection->hasProperty('progressBar')) {
            $progressBarProperty = $reflection->getProperty('progressBar');
            $progressBarProperty->setAccessible(true);
            $progressBar = $progressBarProperty->getValue($this->progressTracker);

            if ($progressBar instanceof \Symfony\Component\Console\Helper\ProgressBar) {
                $progressBar->setProgress(0);
            }
        }
    }

    /**
     * Import data from CSV file into database.
     *
     * @param  array<int, string>  $columns
     */
    abstract protected function importFromCsv(string $table, array $columns): void;

    /**
     * Generate temporary file path for CSV.
     */
    protected function generateTempFilePath(string $table): string
    {
        $tempDir = config('turbo-seeder.csv_strategy.temp_path', storage_path('app/turbo-seeder'));

        $filename = sprintf(
            '%s_%s_%s.csv',
            $table,
            uniqid('', true),
            time()
        );

        return $tempDir.'/'.$filename;
    }

    /**
     * Clean up temporary CSV file.
     */
    protected function cleanupTempFile(): void
    {
        if (! $this->tempFilePath || ! file_exists($this->tempFilePath)) {
            return;
        }

        $cleanupAction = app(CleanupCsvAction::class);
        $cleanupAction($this->tempFilePath);
    }

    /**
     * Get the absolute path to the temporary CSV file.
     */
    protected function getAbsoluteFilePath(): string
    {
        if (! $this->tempFilePath) {
            throw new \RuntimeException('Temp file path not set');
        }

        return realpath($this->tempFilePath) ?: $this->tempFilePath;
    }
}
