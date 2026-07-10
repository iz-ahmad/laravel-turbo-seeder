<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Tests\Fixtures;

use IzAhmad\TurboSeeder\Facades\TurboSeeder;

final class TurboSeederRunCommandTestSeeder
{
    public function run(): void
    {
        TurboSeeder::forTable('test_users')
            ->columns(['name', 'email'])
            ->generate(fn ($i) => [
                'name' => "User {$i}",
                'email' => "user{$i}@test.com",
            ])
            ->count(10)
            ->run();
    }
}
