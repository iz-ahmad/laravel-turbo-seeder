<?php

namespace IzAhmad\TurboSeeder;

use IzAhmad\TurboSeeder\Commands\TurboSeederCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TurboSeederServiceProvider extends PackageServiceProvider
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
            ->hasCommand(TurboSeederCommand::class);
    }
}
