<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Actions;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\DTOs\DatabaseConnectionDTO;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;

final class PrepareEnvironmentAction
{
    /**
     * Prepare the db environment for seeding.
     */
    public function __invoke(
        DatabaseConnectionDTO $dbConnection,
        SeederConfigurationDTO $config
    ): void {
        $connection = $dbConnection->connection;

        if ($config->shouldDisableQueryLog()) {
            DB::connection($dbConnection->name)->disableQueryLog();
        }

        match ($dbConnection->driver) {
            DatabaseDriver::MYSQL => $this->prepareMySql($connection, $config),
            DatabaseDriver::PGSQL => $this->preparePostgreSql($connection, $config),
            DatabaseDriver::SQLITE => $this->prepareSqlite($connection, $config),
        };
    }

    private function prepareMySql(Connection $connection, SeederConfigurationDTO $config): void
    {
        if ($config->shouldDisableForeignKeyChecks()) {
            $connection->statement('SET FOREIGN_KEY_CHECKS=0');
            $connection->statement('SET unique_checks=0');
        }

        $connection->statement('SET autocommit=0');
    }

    private function preparePostgreSql(Connection $connection, SeederConfigurationDTO $config): void
    {
        if ($config->shouldDisableForeignKeyChecks()) {
            $connection->statement('SET CONSTRAINTS ALL DEFERRED');
        }
    }

    private function prepareSqlite(Connection $connection, SeederConfigurationDTO $config): void
    {
        if ($config->shouldDisableForeignKeyChecks()) {
            $connection->statement('PRAGMA foreign_keys=OFF');
        }

        $connection->statement('PRAGMA synchronous=OFF');
        $connection->statement('PRAGMA journal_mode=MEMORY');
    }
}
