<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Contracts;

use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;

interface SeederStrategyInterface
{
    /**
     * Execute the seeding operation.
     */
    public function seed(SeederConfigurationDTO $config): int;

    /**
     * check if this strategy supports the given database driver.
     */
    public function supports(DatabaseDriver $driver): bool;

    /**
     * Get the optimal chunk size for this strategy.
     */
    public function getOptimalChunkSize(): int;

    /**
     * prepare the database environment for seeding.
     */
    public function prepareEnvironment(): void;

    /**
     * Clean up and restore database environment after seeding.
     */
    public function cleanup(): void;
}
