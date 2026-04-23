<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Traits;

use IzAhmad\TurboSeeder\Builder\TurboSeederBuilder;
use IzAhmad\TurboSeeder\DTOs\SeederResultDTO;
use IzAhmad\TurboSeeder\Examples\ExampleSeeder;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

/**
 * Trait to use TurboSeeder in your seeder classes.
 * It provides helper methods to use Turbo Seeder easily and quickly with various options.
 *
 * @see TurboSeederBuilder
 * @see TurboSeeder
 * @see ExampleSeeder
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
     * Generate a unique email address closure.
     */
    protected function uniqueEmail(string $domain = 'turbo.test'): \Closure
    {
        return TurboData::uniqueEmail($domain);
    }

    /**
     * Generate a unique username closure.
     */
    protected function uniqueValue(string $prefix = 'usr'): \Closure
    {
        return TurboData::uniqueUsername($prefix);
    }

    /**
     * Generate a unique UUID-based value closure.
     */
    protected function uniqueUuid(string $prefix = ''): \Closure
    {
        return TurboData::uniqueUuid($prefix);
    }
}
