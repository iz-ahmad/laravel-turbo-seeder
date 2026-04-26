<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Actions;

use Illuminate\Database\Connection;
use IzAhmad\TurboSeeder\DTOs\DatabaseConnectionDTO;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;

final class CleanupEnvironmentAction
{
    /**
     * Restore the database environment to its normal state after seeding.
     *
     * @param  array<string, mixed>  $context  Values captured by PrepareEnvironmentAction.
     */
    public function __invoke(
        DatabaseConnectionDTO $dbConnection,
        SeederConfigurationDTO $config,
        array $context = []
    ): void {
        $connection = $dbConnection->connection;

        match ($dbConnection->driver) {
            DatabaseDriver::MYSQL => $this->cleanupMySql($connection, $config),
            DatabaseDriver::PGSQL => $this->cleanupPostgreSql($connection, $config),
            DatabaseDriver::SQLITE => $this->cleanupSqlite($connection, $config, $context),
        };
    }

    private function cleanupMySql(Connection $connection, SeederConfigurationDTO $config): void
    {
        if ($config->shouldDisableForeignKeyChecks()) {
            $connection->statement('SET unique_checks=1');
            $connection->statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function cleanupPostgreSql(Connection $connection, SeederConfigurationDTO $config): void
    {
        // PG constraints are automatically re-enabled after the transaction commits.
    }

    /** @param  array<string, mixed>  $context */
    private function cleanupSqlite(Connection $connection, SeederConfigurationDTO $config, array $context): void
    {
        $synchronous = $context['synchronous'] ?? 2;
        $journalMode = $context['journal_mode'] ?? 'delete';

        $connection->statement("PRAGMA synchronous={$synchronous}");
        $connection->statement("PRAGMA journal_mode={$journalMode}");

        if ($config->shouldDisableForeignKeyChecks()) {
            $foreignKeys = $context['foreign_keys'] ?? 1;
            $connection->statement("PRAGMA foreign_keys={$foreignKeys}");
        }
    }
}
