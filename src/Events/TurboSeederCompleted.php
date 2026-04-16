<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Events;

use IzAhmad\TurboSeeder\DTOs\SeederResultDTO;

/**
 * Dispatched after a seeding operation completes successfully.
 *
 * Listeners may use this event to trigger follow-up actions such as
 * cache invalidation, notifications, or post-seed analytics.
 *
 * For dry-run operations, the event is still dispatched but
 * $result->isDryRun will be true and no rows were committed.
 */
final class TurboSeederCompleted
{
    public function __construct(
        public readonly string $table,
        public readonly SeederResultDTO $result,
    ) {}
}
