<?php

declare(strict_types=1);

namespace IzAhmad\TurboSeeder\Tests\Fixtures;

use IzAhmad\TurboSeeder\Facades\TurboSeeder;

/**
 * Mirrors seeders that can make several ->run() calls in one run() method
 */
final class TurboSeederRunCommandMultiCallTestSeeder
{
    public function run(): void
    {
        TurboSeeder::forTable('test_users')
            ->columns(['name', 'email'])
            ->generate(fn ($i) => [
                'name' => "User {$i}",
                'email' => "user{$i}@multicall1.test",
            ])
            ->count(5)
            ->run();

        TurboSeeder::forTable('test_users')
            ->columns(['name', 'email'])
            ->generate(fn ($i) => [
                'name' => "User {$i}",
                'email' => "user{$i}@multicall2.test",
            ])
            ->count(5)
            ->run();
    }
}
