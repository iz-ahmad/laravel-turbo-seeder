<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Support;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

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
            /** @var array<string, mixed> $attributes */
            $attributes = $factory->raw();

            foreach ($attributes as $key => $value) {
                if ($value instanceof Factory || $value instanceof Model) {
                    throw new \RuntimeException(
                        "TurboSeeder fromFactory(): attribute [{$key}] resolved to a related model/factory. "
                        .'Bulk seeding cannot persist relationships row-by-row — provide a concrete value in the '
                        .'factory definition or assign foreign keys with TurboData::fromTable().'
                    );
                }
            }

            return $attributes;
        };
    }
}
