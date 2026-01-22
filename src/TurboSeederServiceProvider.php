<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder;

use IzAhmad\TurboSeeder\Actions\CleanupCsvAction;
use IzAhmad\TurboSeeder\Actions\CleanupEnvironmentAction;
use IzAhmad\TurboSeeder\Actions\ExecuteSeederAction;
use IzAhmad\TurboSeeder\Actions\GenerateCsvAction;
use IzAhmad\TurboSeeder\Actions\PrepareEnvironmentAction;
use IzAhmad\TurboSeeder\Builder\TurboSeederBuilder;
use IzAhmad\TurboSeeder\Commands\TurboBenchmarkCommand;
use IzAhmad\TurboSeeder\Commands\TurboClearCacheCommand;
use IzAhmad\TurboSeeder\Commands\TurboSeederCommand;
use IzAhmad\TurboSeeder\Commands\TurboTestConnectionCommand;
use IzAhmad\TurboSeeder\Contracts\MemoryManagerInterface;
use IzAhmad\TurboSeeder\Contracts\ProgressTrackerInterface;
use IzAhmad\TurboSeeder\Services\MemoryManager;
use IzAhmad\TurboSeeder\Services\NullProgressTracker;
use IzAhmad\TurboSeeder\Services\SeederOrchestrator;
use IzAhmad\TurboSeeder\Services\StrategyResolver;
use IzAhmad\TurboSeeder\Strategies\MySqlCsvStrategy;
use IzAhmad\TurboSeeder\Strategies\MySqlSeederStrategy;
use IzAhmad\TurboSeeder\Strategies\PostgreSqlCsvStrategy;
use IzAhmad\TurboSeeder\Strategies\PostgreSqlSeederStrategy;
use IzAhmad\TurboSeeder\Strategies\SqliteCsvStrategy;
use IzAhmad\TurboSeeder\Strategies\SqliteSeederStrategy;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TurboSeederServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('turbo-seeder')
            ->hasConfigFile('turbo-seeder')
            ->hasCommands([
                TurboSeederCommand::class,
                TurboBenchmarkCommand::class,
                TurboTestConnectionCommand::class,
                TurboClearCacheCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->registerInterfaces();
        $this->registerActions();
        $this->registerServices();
        $this->registerStrategies();
    }

    private function registerInterfaces(): void
    {
        $this->app->singleton(MemoryManagerInterface::class, function ($app) {
            return new MemoryManager(config('turbo-seeder', []));
        });

        $this->app->singleton(ProgressTrackerInterface::class, function ($app) {
            return new NullProgressTracker;
        });
    }

    private function registerActions(): void
    {
        $this->app->bind(PrepareEnvironmentAction::class);
        $this->app->bind(CleanupEnvironmentAction::class);
        $this->app->bind(ExecuteSeederAction::class);
        $this->app->bind(GenerateCsvAction::class);
        $this->app->bind(CleanupCsvAction::class);
    }

    private function registerServices(): void
    {
        $this->app->singleton(StrategyResolver::class, function ($app) {
            return new StrategyResolver;
        });

        $this->app->singleton(SeederOrchestrator::class, function ($app) {
            return new SeederOrchestrator(
                $app->make(StrategyResolver::class),
                $app->make(ExecuteSeederAction::class)
            );
        });

        $this->app->bind(TurboSeederBuilder::class, function ($app) {
            return new TurboSeederBuilder(
                $app->make(SeederOrchestrator::class)
            );
        });

        $this->app->singleton('turbo-seeder', function ($app) {
            return new TurboSeeder(
                $app->make(SeederOrchestrator::class)
            );
        });
    }

    private function registerStrategies(): void
    {
        $resolver = $this->app->make(StrategyResolver::class);

        // default strategies (bulk insert way)
        $resolver->register('default.mysql', MySqlSeederStrategy::class);
        $resolver->register('default.pgsql', PostgreSqlSeederStrategy::class);
        $resolver->register('default.sqlite', SqliteSeederStrategy::class);

        // csv strategies (csv file-based import way)
        $resolver->register('csv.mysql', MySqlCsvStrategy::class);
        $resolver->register('csv.pgsql', PostgreSqlCsvStrategy::class);
        $resolver->register('csv.sqlite', SqliteCsvStrategy::class);
    }
}
