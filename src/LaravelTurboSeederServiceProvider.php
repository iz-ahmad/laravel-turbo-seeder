<?php

namespace IzAhmad\LaravelTurboSeeder;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use IzAhmad\LaravelTurboSeeder\Commands\LaravelTurboSeederCommand;

class LaravelTurboSeederServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-turbo-seeder')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_turbo_seeder_table')
            ->hasCommand(LaravelTurboSeederCommand::class);
    }
}
