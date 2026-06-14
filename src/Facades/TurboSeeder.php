<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Facades;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Facade;
use IzAhmad\TurboSeeder\Builder\TurboSeederBuilder;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\DTOs\SeederResultDTO;

/**
 * @method static SeederResultDTO execute(SeederConfigurationDTO $config)
 * @method static TurboSeederBuilder create(?string $table = null)
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

    public static function create(?string $table = null): TurboSeederBuilder
    {
        $builder = app(TurboSeederBuilder::class);

        if ($table !== null) {
            $builder->table($table);
        }

        return $builder;
    }

    public static function fromFactory(Factory $factory): TurboSeederBuilder
    {
        return app(TurboSeederBuilder::class)->fromFactory($factory);
    }
}
