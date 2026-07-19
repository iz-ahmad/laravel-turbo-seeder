<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Support;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Bridges a Laravel model factory into a TurboSeeder generator closure.
 *
 * Each row is produced by the factory's definition (Faker resolved) via raw(), then bulk-inserted
 * Model events, observers and accessors are intentionally skipped for speed;
 * anything they would normally compute must live in the factory definition itself.
 */
final class FactoryDataGenerator
{
    private readonly Factory $factory;

    private readonly ?string $recycleWarning;

    public function __construct(Factory $factory)
    {
        $this->recycleWarning = $this->warnIfParentRelationshipsUnrecycled($factory);

        $this->factory = $factory->count(null);
    }

    public function getRecycleWarning(): ?string
    {
        return $this->recycleWarning;
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
            return $factory->raw();
        };
    }

    /**
     * Warn early when the factory has for() parent relationships but no recycle() pool.
     * In that case raw() calls Eloquent create() for the related model on every row, defeating the purpose of bulk seeding.
     */
    private function warnIfParentRelationshipsUnrecycled(Factory $factory): ?string
    {
        try {
            $ref = new \ReflectionClass($factory);

            $forProp = $ref->getProperty('for');
            $for = $forProp->getValue($factory);

            if (! $for instanceof Collection || $for->isEmpty()) {
                return null;
            }

            $recycleProp = $ref->getProperty('recycle');
            $recycle = $recycleProp->getValue($factory);

            if ($recycle instanceof Collection && $recycle->isNotEmpty()) {
                return null;
            }

            return 'TurboSeeder fromFactory(): factory has for() parent relationships but no recycle() pool. '
                .'Each seeded row will trigger an individual Eloquent create() for the related model — '
                .'pre-load the parents and pass them via $factory->recycle(RelatedModel::all()).';
        } catch (\Throwable) {
            // reflection failed (e.g. property renamed in a future Laravel version)
            return null;
        }
    }
}
