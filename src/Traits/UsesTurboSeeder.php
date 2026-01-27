<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Traits;

use IzAhmad\TurboSeeder\Builder\TurboSeederBuilder;
use IzAhmad\TurboSeeder\DTOs\SeederResultDTO;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\UniqueValueGenerator;

/**
 * Trait to use TurboSeeder in your seeder classes.
 * It provides helper methods to use Turbo Seeder easily and quickly with various options.
 *
 * @see \IzAhmad\TurboSeeder\Builder\TurboSeederBuilder
 * @see \IzAhmad\TurboSeeder\Facades\TurboSeeder
 * @see \IzAhmad\TurboSeeder\Examples\ExampleSeeder
 */
trait UsesTurboSeeder
{
    /**
     * Create a new TurboSeederBuilder instance.
     */
    protected function turboSeed(?string $table = null): TurboSeederBuilder
    {
        return TurboSeeder::create($table);
    }

    /**
     * Quick seed helper with default configuration.
     *
     * @param  array<int, string>  $columns
     */
    protected function quickSeed(
        string $table,
        array $columns,
        \Closure $generator,
        int $count = 1000
    ): SeederResultDTO {
        return TurboSeeder::create($table)
            ->columns($columns)
            ->generate($generator)
            ->count($count)
            ->run();
    }

    /**
     * Quick seed helper (CSV based).
     *
     * @param  array<int, string>  $columns
     */
    protected function quickCsvSeed(
        string $table,
        array $columns,
        \Closure $generator,
        int $count = 1000
    ): SeederResultDTO {
        return TurboSeeder::create($table)
            ->columns($columns)
            ->generate($generator)
            ->count($count)
            ->useCsvStrategy()
            ->run();
    }

    /**
     * Generate a unique email for a table.
     */
    protected function uniqueEmail(?string $prefix = null): \Closure
    {
        return UniqueValueGenerator::uniqueEmail($prefix);
    }

    /**
     * Generate a unique value for any column.
     */
    protected function uniqueValue(?string $prefix = null): \Closure
    {
        return UniqueValueGenerator::uniqueValue($prefix);
    }

    /**
     * Generate a unique UUID-based value.
     */
    protected function uniqueUuid(string $prefix = ''): \Closure
    {
        return UniqueValueGenerator::uniqueUuid($prefix);
    }
}
