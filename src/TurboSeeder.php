<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder;

use Illuminate\Database\Eloquent\Factories\Factory;
use IzAhmad\TurboSeeder\Builder\TurboSeederBuilder;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\DTOs\SeederResultDTO;
use IzAhmad\TurboSeeder\Services\SeederOrchestrator;

class TurboSeeder
{
    public function __construct(
        private readonly SeederOrchestrator $orchestrator
    ) {}

    public function execute(SeederConfigurationDTO $config): SeederResultDTO
    {
        return $this->orchestrator->execute($config);
    }

    public function create(?string $table = null): TurboSeederBuilder
    {
        $builder = app(TurboSeederBuilder::class);

        if ($table !== null) {
            $builder->table($table);
        }

        return $builder;
    }

    public function fromFactory(Factory $factory): TurboSeederBuilder
    {
        return app(TurboSeederBuilder::class)->fromFactory($factory);
    }
}
