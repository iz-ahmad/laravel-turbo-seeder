<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Strategies\Concerns\ClassifiesDatabaseErrors;

/**
 * Build a PDOException carrying a SQLSTATE + driver errno, like PDO does.
 */
function pdoError(string $sqlState, ?int $errno = null, string $message = 'error'): PDOException
{
    $e = new PDOException($message);
    $e->errorInfo = [$sqlState, $errno, $message];

    return $e;
}

beforeEach(function () {
    $this->classifier = new class
    {
        use ClassifiesDatabaseErrors;

        public function transient(Throwable $e): bool
        {
            return $this->isTransientLockError($e);
        }

        public function state(Throwable $e): ?string
        {
            return $this->sqlState($e);
        }

        public function errno(Throwable $e): ?int
        {
            return $this->driverErrno($e);
        }
    };
});

test('extracts sqlstate and errno from a PDO exception', function () {
    $e = pdoError('40001', 1213, 'Deadlock found');

    expect($this->classifier->state($e))->toBe('40001')
        ->and($this->classifier->errno($e))->toBe(1213);
});

test('serialization failure SQLSTATE is transient', function () {
    expect($this->classifier->transient(pdoError('40001')))->toBeTrue();
});

test('postgres deadlock SQLSTATE is transient', function () {
    expect($this->classifier->transient(pdoError('40P01')))->toBeTrue();
});

test('mysql lock-wait and deadlock errnos are transient', function () {
    expect($this->classifier->transient(pdoError('HY000', 1205)))->toBeTrue()
        ->and($this->classifier->transient(pdoError('40000', 1213)))->toBeTrue();
});

test('unrelated errors are not transient', function () {
    expect($this->classifier->transient(pdoError('23000', 1062)))->toBeFalse()
        ->and($this->classifier->transient(new RuntimeException('boom')))->toBeFalse();
});
