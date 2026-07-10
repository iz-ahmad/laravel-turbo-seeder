<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder;

use Illuminate\Database\Eloquent\Factories\Factory;
use IzAhmad\TurboSeeder\Builder\TurboSeederBuilder;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\DTOs\SeederResultDTO;
use IzAhmad\TurboSeeder\Services\SeederOrchestrator;

/**
 * Main TurboSeeder service class.
 *
 * This is the primary entry point for the TurboSeeder package.
 */
class TurboSeeder
{
    public function __construct(
        private readonly SeederOrchestrator $orchestrator
    ) {}

    /**
     * Execute seeding with the given configuration.
     */
    public function execute(SeederConfigurationDTO $config): SeederResultDTO
    {
        return $this->orchestrator->execute($config);
    }

    /**
     * Create a new seeder builder instance for the given table.
     */
    public function forTable(string $table): TurboSeederBuilder
    {
        return app(TurboSeederBuilder::class)->table($table);
    }

    /**
     * Create a builder that generates rows from a Laravel model factory.
     */
    public function fromFactory(Factory $factory): TurboSeederBuilder
    {
        return app(TurboSeederBuilder::class)->fromFactory($factory);
    }
}
