<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder;

use IzAhmad\TurboSeeder\Builder\TurboSeederBuilder;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\DTOs\SeederResultDTO;
use IzAhmad\TurboSeeder\Services\SeederOrchestrator;

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
     * Create a new seeder builder instance.
     */
    public function create(?string $table = null): TurboSeederBuilder
    {
        $builder = app(TurboSeederBuilder::class);

        if ($table !== null) {
            $builder->table($table);
        }

        return $builder;
    }
}
