<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies\Concerns;

use IzAhmad\TurboSeeder\Services\SqlIdentifier;

/**
 * Requires the following property in the using class:
 * - \IzAhmad\TurboSeeder\DTOs\DatabaseConnectionDTO $dbConnection
 */
trait QuotesTableName
{
    protected function quoteTable(string $table): string
    {
        return SqlIdentifier::quoteTable($table, $this->dbConnection->driver, $this->dbConnection->tablePrefix());
    }
}
