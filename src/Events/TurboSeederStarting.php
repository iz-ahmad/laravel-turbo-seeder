<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Events;

use IzAhmad\TurboSeeder\Enums\SeederStrategy;

/**
 * Dispatched just before a seeding operation begins, after validation passes.
 *
 * Useful for logging, timing, or pausing external processes before a large
 * seed. Only scalar context is carried (no generator closure) so listeners
 * remain queue-safe.
 */
final class TurboSeederStarting
{
    public function __construct(
        public readonly string $table,
        public readonly int $count,
        public readonly SeederStrategy $strategy,
        public readonly string $connection,
    ) {}
}
