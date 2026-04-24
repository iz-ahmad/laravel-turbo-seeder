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
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        DatabaseConnectionDTO $dbConnection,
        SeederConfigurationDTO $config
    ): array {
        $context = $this->prepareBeforeTransaction($dbConnection, $config);
        $this->prepareAfterTransaction($dbConnection, $config);

        return $context;
    }

    /**
     * Prepare session-level settings that must run before the transaction.
     *
     * @return array<string, mixed>
     */
    public function prepareBeforeTransaction(
        DatabaseConnectionDTO $dbConnection,
        SeederConfigurationDTO $config
    ): array {
        $connection = $dbConnection->connection;

        if ($config->shouldDisableQueryLog()) {
            DB::connection($dbConnection->name)->disableQueryLog();
        }

        return match ($dbConnection->driver) {
            DatabaseDriver::MYSQL => $this->prepareMySql($connection, $config),
            DatabaseDriver::PGSQL => [],
            DatabaseDriver::SQLITE => $this->prepareSqlite($connection, $config),
        };
    }

    /**
     * Prepare settings that require an open transaction (e.g. PostgreSQL SET CONSTRAINTS).
     */
    public function prepareAfterTransaction(
        DatabaseConnectionDTO $dbConnection,
        SeederConfigurationDTO $config
    ): void {
        $connection = $dbConnection->connection;

        if ($dbConnection->driver === DatabaseDriver::PGSQL) {
            $this->preparePostgreSql($connection, $config);
        }
    }

    /** @return array<string, mixed> */
    private function prepareMySql(Connection $connection, SeederConfigurationDTO $config): array
    {
        if ($config->shouldDisableForeignKeyChecks()) {
            $connection->statement('SET FOREIGN_KEY_CHECKS=0');
            $connection->statement('SET unique_checks=0');
        }

        return [];
    }

    private function preparePostgreSql(Connection $connection, SeederConfigurationDTO $config): void
    {
        if ($config->shouldDisableForeignKeyChecks()) {
            $connection->statement('SET CONSTRAINTS ALL DEFERRED');
        }
    }

    /** @return array<string, mixed> */
    private function prepareSqlite(Connection $connection, SeederConfigurationDTO $config): array
    {
        $context = [];

        $syncResult = $connection->select('PRAGMA synchronous');
        $context['synchronous'] = $syncResult[0]->synchronous ?? 2;

        $journalResult = $connection->select('PRAGMA journal_mode');
        $context['journal_mode'] = $journalResult[0]->journal_mode ?? 'delete';

        if ($config->shouldDisableForeignKeyChecks()) {
            $fkResult = $connection->select('PRAGMA foreign_keys');
            $context['foreign_keys'] = $fkResult[0]->foreign_keys ?? 1;
            $connection->statement('PRAGMA foreign_keys=OFF');
        }

        $connection->statement('PRAGMA synchronous=OFF');
        $connection->statement('PRAGMA journal_mode=MEMORY');

        return $context;
    }
}
