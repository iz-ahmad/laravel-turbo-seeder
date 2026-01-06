<?php

namespace IzAhmad\TurboSeeder\Commands;

use Illuminate\Console\Command;

class TurboSeederCommand extends Command
{
    public $signature = 'turbo-seeder';

    public $description = 'My command';

    public function handle(): int
    {
        // TODO: implement the command
        $this->comment('All done');

        return self::SUCCESS;
    }
}
