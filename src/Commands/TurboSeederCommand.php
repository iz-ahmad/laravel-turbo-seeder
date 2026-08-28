<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Log;
use IzAhmad\TurboSeeder\Actions\GuardAgainstProductionAction;
use IzAhmad\TurboSeeder\Contracts\ProgressTrackerInterface;
use IzAhmad\TurboSeeder\Helpers\ExceptionFormatter;
use IzAhmad\TurboSeeder\Services\ConsoleProgressTracker;

final class TurboSeederCommand extends Command
{
    use ConfirmableTrait;

    public $signature = 'turbo-seeder:run
                        {seeder? : The seeder class name (optional)}
                        {--class= : The seeder class name}
                        {--force : Skip the production confirmation prompt}';

    public $description = 'Run TurboSeeder for high-performance and fast database seeding with bulk amount of data';

    public function handle(): int
    {
        if (! $this->confirmToProceed(
            'App Environment: '.app()->environment().'. Turbo Seeder may insert or truncate large amounts of data.',
            fn () => ! app()->environment('local', 'testing'),
        )) {
            return self::FAILURE;
        }

        if (! $seeder = $this->validateArguments()) {
            return self::FAILURE;
        }

        $tracker = new ConsoleProgressTracker($this->output);

        app()->instance(ProgressTrackerInterface::class, $tracker);
        app()->instance(GuardAgainstProductionAction::CONFIRMED_BINDING, true);

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        try {
            $seeder->run();

            $recordsInserted = $tracker->getTotalRecordsInserted();
            $duration = round(microtime(true) - $startTime, 2);
            $memoryUsed = round((memory_get_peak_usage(true) - $startMemory) / 1024 / 1024, 2);

            $this->newLine();
            $this->components->info('✓ Seeding completed successfully!');

            $this->displayMetrics($duration, $memoryUsed, $recordsInserted);

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->handleException($e);

            return self::FAILURE;
        } finally {
            app()->forgetInstance(ProgressTrackerInterface::class);
            app()->forgetInstance(GuardAgainstProductionAction::CONFIRMED_BINDING);
        }
    }

    private function validateArguments(): ?object
    {
        $seederClass = $this->argument('seeder') ?? $this->option('class');

        if (! $seederClass) {
            $this->error('✗ Seeder class is required!');
            $this->info('Usage: php artisan turbo-seeder:run YourSeederClass');
            $this->info('   or: php artisan turbo-seeder:run --class=YourSeederClass');

            return null;
        }

        if (! str_contains($seederClass, '\\')) {
            $seederNamespace = config('turbo-seeder.seeder_classes_namespace', 'Database\\Seeders\\');
            $seederClass = "{$seederNamespace}{$seederClass}";
        }

        if (! class_exists($seederClass)) {
            $this->error("✗ Seeder class [{$seederClass}] not found!");

            return null;
        }

        $seeder = app($seederClass);

        if (! method_exists($seeder, 'run')) {
            $this->error('✗ Seeder class must have a run() method!');

            return null;
        }

        if (method_exists($seeder, 'setCommand')) {
            $seeder->setCommand($this);
        }

        return $seeder;
    }

    private function displayMetrics(float $duration, float $memoryMB, int $recordsInserted = 0): void
    {
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Records', number_format($recordsInserted)],
                ['Duration', round($duration, 2).' seconds'],
                ['Peak Memory Usage', round($memoryMB, 2).' MB'],
            ]
        );
    }

    private function handleException(\Throwable $e): void
    {
        $this->newLine();
        $this->components->error('✗ Seeding failed!');

        $formattedMessage = ExceptionFormatter::format($e);
        $this->error($formattedMessage);

        Log::error('TurboSeeder Command Failed', [
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'trace' => $e->getTraceAsString(),
        ]);

        if ($this->output->isVerbose() || config('turbo-seeder.get_error_trace_on_console', false)) {
            $this->newLine();
            $this->line('<comment>Full error details with stack trace:</comment>');
            $this->line($e->getTraceAsString());
        }

        $this->newLine();
        $this->line('💡 Tip: Check the logs for more details.');
    }
}
