<?php

namespace IzAhmad\TurboSeeder\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \IzAhmad\TurboSeeder\TurboSeeder
 */
class TurboSeeder extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \IzAhmad\TurboSeeder\TurboSeeder::class;
    }
}
