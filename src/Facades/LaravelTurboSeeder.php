<?php

namespace IzAhmad\LaravelTurboSeeder\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \IzAhmad\LaravelTurboSeeder\LaravelTurboSeeder
 */
class LaravelTurboSeeder extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \IzAhmad\LaravelTurboSeeder\LaravelTurboSeeder::class;
    }
}
