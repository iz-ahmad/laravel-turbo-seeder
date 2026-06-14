<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Support;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Bridges a Laravel model factory into a TurboSeeder generator closure.
 *
 * Each row is produced by the factory's definition (states applied, Faker
 * resolved) via raw(), then bulk-inserted directly — Model::save(), events,
 * observers and accessors are intentionally skipped for speed. Anything those
 * would normally compute must live in the factory definition itself.
 */
final class FactoryDataGenerator
{
    private readonly Factory $factory;

    public function __construct(Factory $factory)
    {
        // Normalise count to null so raw() yields a single row per call; the row
        // count is driven by the builder's count() instead of the factory's.
        $this->factory = $factory->count(null);

        $this->warnIfParentRelationshipsUnrecycled($this->factory);
    }

    /**
     * The Eloquent model the factory builds.
     */
    public function model(): Model
    {
        return $this->factory->newModel();
    }

    /**
     * The table the factory's model writes to.
     */
    public function table(): string
    {
        return $this->model()->getTable();
    }

    /**
     * Whether the factory's model maintains created_at/updated_at.
     */
    public function usesTimestamps(): bool
    {
        return $this->model()->usesTimestamps();
    }

    /**
     * The model's created_at column name.
     */
    public function createdAtColumn(): ?string
    {
        return $this->model()->getCreatedAtColumn();
    }

    /**
     * The model's updated_at column name.
     */
    public function updatedAtColumn(): ?string
    {
        return $this->model()->getUpdatedAtColumn();
    }

    /**
     * Build the per-index generator closure.
     *
     * @return \Closure(int): array<string, mixed>
     */
    public function toGenerator(): \Closure
    {
        $factory = $this->factory;

        return static function (int $index) use ($factory): array {
            /** @var array<string, mixed> */
            return $factory->raw();
        };
    }

    /**
     * Warn early when the factory has for() parent relationships but no recycle()
     * pool. In that case raw() calls Eloquent create() for the related model on
     * every row, defeating the purpose of bulk seeding. The fix is to pre-load the
     * parent models and pass them via $factory->recycle(RelatedModel::all()).
     */
    private function warnIfParentRelationshipsUnrecycled(Factory $factory): void
    {
        try {
            $ref = new \ReflectionClass($factory);

            $forProp = $ref->getProperty('for');
            $forProp->setAccessible(true);
            $for = $forProp->getValue($factory);

            if (! $for instanceof Collection || $for->isEmpty()) {
                return;
            }

            $recycleProp = $ref->getProperty('recycle');
            $recycleProp->setAccessible(true);
            $recycle = $recycleProp->getValue($factory);

            if ($recycle instanceof Collection && $recycle->isNotEmpty()) {
                return;
            }

            Log::warning(
                'TurboSeeder fromFactory(): factory has for() parent relationships but no recycle() pool. '
                .'Each seeded row will trigger an individual Eloquent create() for the related model — '
                .'pre-load the parents and pass them via $factory->recycle(RelatedModel::all()).',
            );
        } catch (\Throwable) {
            // Reflection failed (e.g. property renamed in a future Laravel version) — skip.
        }
    }
}
