<?php

namespace IzAhmad\LaravelTurboSeeder\Commands;

use Illuminate\Console\Command;

class LaravelTurboSeederCommand extends Command
{
    public $signature = 'laravel-turbo-seeder';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
