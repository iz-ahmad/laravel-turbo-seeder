<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Strategies\Concerns;

/**
 * Classifies database errors by SQLSTATE / driver error number rather than by
 * matching English error text, which breaks across driver locales and versions.
 */
trait ClassifiesDatabaseErrors
{
    /**
     * Extract the five-character SQLSTATE from a PDO exception, if present.
     *
     * Walks the exception chain: strategies re-wrap the driver error in a plain
     * RuntimeException, and Laravel wraps PDO failures in a QueryException whose
     * own errorInfo is empty — the real SQLSTATE lives on a nested PDOException.
     */
    protected function sqlState(\Throwable $e): ?string
    {
        foreach ($this->throwableChain($e) as $throwable) {
            if (! $throwable instanceof \PDOException) {
                continue;
            }

            if (is_array($throwable->errorInfo ?? null) && isset($throwable->errorInfo[0])) {
                return $throwable->errorInfo[0];
            }

            $code = $throwable->getCode();

            // PDOException codes are SQLSTATE strings; a 0 code carries no SQLSTATE.
            if ($code !== 0 && $code !== '00000') {
                return (string) $code;
            }
        }

        return null;
    }

    /**
     * Extract the driver-specific error number (e.g. MySQL errno) if present.
     *
     * Walks the exception chain for the same reason as sqlState().
     */
    protected function driverErrno(\Throwable $e): ?int
    {
        foreach ($this->throwableChain($e) as $throwable) {
            if ($throwable instanceof \PDOException && is_array($throwable->errorInfo ?? null) && isset($throwable->errorInfo[1])) {
                return (int) $throwable->errorInfo[1];
            }
        }

        return null;
    }

    /**
     * Whether the failure is a transient deadlock / lock-timeout worth retrying.
     */
    protected function isTransientLockError(\Throwable $e): bool
    {
        // 40001 serialization_failure (MySQL + PostgreSQL), 40P01 PG deadlock_detected.
        if (in_array($this->sqlState($e), ['40001', '40P01'], true)) {
            return true;
        }

        // MySQL: 1205 lock wait timeout, 1213 deadlock found.
        return in_array($this->driverErrno($e), [1205, 1213], true);
    }

    /**
     * Yield the throwable and each of its previous exceptions in order.
     *
     * @return iterable<\Throwable>
     */
    private function throwableChain(\Throwable $e): iterable
    {
        $current = $e;

        while ($current !== null) {
            yield $current;

            $current = $current->getPrevious();
        }
    }
}
