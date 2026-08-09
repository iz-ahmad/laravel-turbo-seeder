<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Resolves SQLite's maximum bound-variable count for the connected engine.
 *
 * SQLite 3.32.0 (2020) raised SQLITE_MAX_VARIABLE_NUMBER from 999 to 32766,
 * allowing far larger multi-row inserts. The limit is detected once per
 * strategy instance and falls back to the conservative 999 when unknown.
 */
trait ResolvesSqliteVariableLimit
{
    private const LEGACY_VARIABLE_LIMIT = 999;

    private const MODERN_VARIABLE_LIMIT = 32766;

    private ?int $resolvedSqliteVariableLimit = null;

    protected function sqliteVariableLimit(): int
    {
        if ($this->resolvedSqliteVariableLimit !== null) {
            return $this->resolvedSqliteVariableLimit;
        }

        $limit = self::LEGACY_VARIABLE_LIMIT;

        try {
            $version = (string) DB::connection($this->dbConnection->name)
                ->getPdo()
                ->getAttribute(\PDO::ATTR_SERVER_VERSION);

            if ($version !== '' && version_compare($version, '3.32.0', '>=')) {
                $limit = self::MODERN_VARIABLE_LIMIT;
            }
        } catch (\Throwable) {
            $limit = self::LEGACY_VARIABLE_LIMIT;
        }

        return $this->resolvedSqliteVariableLimit = $limit;
    }

    /**
     * Maximum rows per insert batch for the given column count.
     */
    protected function sqliteMaxRowsPerBatch(int $columnCount): int
    {
        return max(1, intdiv($this->sqliteVariableLimit(), max(1, $columnCount)));
    }
}
