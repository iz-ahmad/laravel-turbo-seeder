<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Events;

/**
 * Dispatched when a seeding operation fails with an exception.
 *
 * Complements TurboSeederCompleted (which only fires on success), letting
 * listeners alert, log, or clean up after a failed seed. The original
 * throwable is carried for inspection.
 */
final class TurboSeederFailed
{
    public function __construct(
        public readonly string $table,
        public readonly string $connection,
        public readonly \Throwable $exception,
    ) {}
}
