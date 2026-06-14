<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Contracts;

use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;

interface SeederStrategyInterface
{
    public function seed(SeederConfigurationDTO $config): int;

    public function supports(DatabaseDriver $driver): bool;

    public function getOptimalChunkSize(): int;

    public function prepareEnvironment(): void;

    /**
     * Clean up and restore database environment after seeding.
     *
     * @param  bool  $fromException  Whether the cleanup is due to an exception.
     */
    public function cleanup(bool $fromException = false): void;
}
