<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Events;

use IzAhmad\TurboSeeder\DTOs\SeederResultDTO;

/**
 * Dispatched after a seeding operation finishes without throwing an exception.
 *
 * Listeners may use this event to trigger follow-up actions such as
 * cache invalidation, notifications, or post-seed analytics.
 *
 * IMPORTANT: This event fires even for dry-run operations. Always check
 * $result->isDryRun before acting on the assumption that rows were committed.
 * When isDryRun is true, no rows exist in the database — the transaction was
 * rolled back immediately after the inserts.
 *
 * The event is NOT dispatched when seeding fails (an exception is thrown).
 */
final class TurboSeederCompleted
{
    public function __construct(
        public readonly string $table,
        public readonly SeederResultDTO $result,
    ) {}
}
