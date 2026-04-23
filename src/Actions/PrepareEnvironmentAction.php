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
     * Runs both pre-transaction and post-transaction phases in sequence.
     * Use prepareBeforeTransaction() / prepareAfterTransaction() directly when
     * you need to begin a transaction between the two phases (e.g. ManagesEnvironment).
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
     * Run preparations that must happen BEFORE the transaction begins.
     * MySQL/SQLite session variables and PRAGMAs belong here.
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
     * Run preparations that must happen AFTER the transaction begins.
     * PostgreSQL's SET CONSTRAINTS ALL DEFERRED requires an open transaction.
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
