<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies\Concerns;

/**
 * Classifies database errors by SQLSTATE / driver error number rather than by
 * matching English error text, which breaks across driver locales and versions.
 */
trait ClassifiesDatabaseErrors
{
    protected function sqlState(\Throwable $e): ?string
    {
        if ($e instanceof \PDOException && is_array($e->errorInfo ?? null)) {
            return $e->errorInfo[0] ?? null;
        }

        $code = $e->getCode();

        // PDOException codes are SQLSTATE strings; a 0 code carries no SQLSTATE.
        return ($code === 0 || $code === '00000') ? null : (string) $code;
    }

    protected function driverErrno(\Throwable $e): ?int
    {
        if ($e instanceof \PDOException && is_array($e->errorInfo ?? null) && isset($e->errorInfo[1])) {
            return (int) $e->errorInfo[1];
        }

        return null;
    }

    protected function isTransientLockError(\Throwable $e): bool
    {
        // 40001 serialization_failure (MySQL + PostgreSQL), 40P01 PG deadlock_detected.
        if (in_array($this->sqlState($e), ['40001', '40P01'], true)) {
            return true;
        }

        // MySQL: 1205 lock wait timeout, 1213 deadlock found.
        return in_array($this->driverErrno($e), [1205, 1213], true);
    }
}
