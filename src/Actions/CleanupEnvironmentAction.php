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
     */
    public function __invoke(
        DatabaseConnectionDTO $dbConnection,
        SeederConfigurationDTO $config
    ): void {
        $connection = $dbConnection->connection;

        match ($dbConnection->driver) {
            DatabaseDriver::MYSQL => $this->cleanupMySql($connection, $config),
            DatabaseDriver::PGSQL => $this->cleanupPostgreSql($connection, $config),
            DatabaseDriver::SQLITE => $this->cleanupSqlite($connection, $config),
        };
    }

    private function cleanupMySql(Connection $connection, SeederConfigurationDTO $config): void
    {
        $connection->statement('SET autocommit=1');

        if ($config->shouldDisableForeignKeyChecks()) {
            $connection->statement('SET unique_checks=1');
            $connection->statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function cleanupPostgreSql(Connection $connection, SeederConfigurationDTO $config): void
    {
        // psql constraints are automatically re-enabled after transaction commit, so no need to cleanup anything here.
    }

    private function cleanupSqlite(Connection $connection, SeederConfigurationDTO $config): void
    {
        $connection->statement('PRAGMA synchronous=ON');
        $connection->statement('PRAGMA journal_mode=DELETE');

        if ($config->shouldDisableForeignKeyChecks()) {
            $connection->statement('PRAGMA foreign_keys=ON');
        }
    }
}
