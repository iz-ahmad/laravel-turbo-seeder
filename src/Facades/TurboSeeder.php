<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Facades;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use IzAhmad\TurboSeeder\Builder\TurboSeederBuilder;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\DTOs\SeederResultDTO;

/**
 * @method static SeederResultDTO execute(SeederConfigurationDTO $config)
 * @method static TurboSeederBuilder forTable(class-string<Model>|Model|string $table)
 * @method static TurboSeederBuilder fromFactory(Factory $factory)
 *
 * @see \IzAhmad\TurboSeeder\TurboSeeder
 */
class TurboSeeder extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'turbo-seeder';
    }

    /**
     * Create a new seeder builder instance for the given table - a literal table
     * name, an Eloquent Model class-string, or a Model instance.
     *
     * @param  class-string<Model>|Model|string  $table
     */
    public static function forTable(string|Model $table): TurboSeederBuilder
    {
        return app(TurboSeederBuilder::class)->table($table);
    }

    /**
     * Create a builder that generates rows from a Laravel model factory.
     */
    public static function fromFactory(Factory $factory): TurboSeederBuilder
    {
        return app(TurboSeederBuilder::class)->fromFactory($factory);
    }
}
