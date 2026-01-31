<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Services;

use IzAhmad\TurboSeeder\Contracts\SeederStrategyInterface;
use IzAhmad\TurboSeeder\DTOs\DatabaseConnectionDTO;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\Enums\SeederStrategy as SeederStrategyEnum;

/**
 * Resolves the appropriate seeding strategy based on configuration and database driver.
 *
 * This service maintains a registry of available strategies and resolves
 * the correct strategy implementation for a given database connection and
 * seeding strategy type (default or CSV).
 */
final class StrategyResolver
{
    /**
     * @var array<string, class-string<\IzAhmad\TurboSeeder\Contracts\SeederStrategyInterface>>
     */
    private array $strategies = [];

    /**
     * Register a seeder strategy.
     *
     * @param  class-string<\IzAhmad\TurboSeeder\Contracts\SeederStrategyInterface>  $strategyClass
     */
    public function register(string $key, string $strategyClass): void
    {
        // @phpstan-ignore-next-line
        if (! is_subclass_of($strategyClass, SeederStrategyInterface::class)) {
            throw new \InvalidArgumentException(
                'Strategy class must implement SeederStrategyInterface'
            );
        }

        $this->strategies[$key] = $strategyClass;
    }

    /**
     * resolve the appropriate strategy for the given configuration.
     */
    public function resolve(
        SeederConfigurationDTO $config,
        DatabaseConnectionDTO $dbConnection
    ): SeederStrategyInterface {
        $strategyKey = $this->buildStrategyKey($config->strategy, $dbConnection);

        if (! isset($this->strategies[$strategyKey])) {
            throw new \RuntimeException(
                "No strategy registered for: {$strategyKey}"
            );
        }

        $strategyClass = $this->strategies[$strategyKey];

        return app($strategyClass, [
            'dbConnection' => $dbConnection,
            'config' => $config,
        ]);
    }

    /**
     * Build the strategy key from strategy type and database driver.
     */
    private function buildStrategyKey(
        SeederStrategyEnum $strategy,
        DatabaseConnectionDTO $dbConnection
    ): string {
        return $strategy->value.'.'.$dbConnection->driver->value;
    }

    /**
     * Check if a strategy is registered.
     */
    public function hasStrategy(string $key): bool
    {
        return isset($this->strategies[$key]);
    }

    /**
     * Get all registered strategies.
     *
     * @return array<string, class-string<\IzAhmad\TurboSeeder\Contracts\SeederStrategyInterface>>
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }

    /**
     * Resolve the default strategy for the given database connection.
     */
    public function resolveDefault(
        DatabaseConnectionDTO $dbConnection,
        SeederConfigurationDTO $config
    ): SeederStrategyInterface {
        $strategyKey = 'default.'.$dbConnection->driver->value;

        if (! isset($this->strategies[$strategyKey])) {
            throw new \RuntimeException(
                "No default strategy registered for: {$dbConnection->driver->value}"
            );
        }

        $strategyClass = $this->strategies[$strategyKey];

        return app($strategyClass, [
            'dbConnection' => $dbConnection,
            'config' => $config,
        ]);
    }
}
