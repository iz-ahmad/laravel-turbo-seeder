<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Facades;

use Illuminate\Support\Facades\Facade;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\DTOs\SeederResultDTO;

/**
 * @method static SeederResultDTO execute(SeederConfigurationDTO $config)
 *
 * @see \IzAhmad\TurboSeeder\TurboSeeder
 */
class TurboSeeder extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'turbo-seeder';
    }
}
