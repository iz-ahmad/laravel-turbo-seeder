<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Services;

use IzAhmad\TurboSeeder\Actions\ExecuteSeederAction;
use IzAhmad\TurboSeeder\DTOs\DatabaseConnectionDTO;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\DTOs\SeederResultDTO;

final class SeederOrchestrator
{
    public function __construct(
        private readonly StrategyResolver $strategyResolver,
        private readonly ExecuteSeederAction $executeAction,
    ) {}

    /**
     * Execute the seeding operation.
     */
    public function execute(SeederConfigurationDTO $config): SeederResultDTO
    {
        $dbConnection = DatabaseConnectionDTO::fromName($config->connection);

        $strategy = $this->strategyResolver->resolve($config, $dbConnection);

        return ($this->executeAction)($strategy, $config);
    }
}
