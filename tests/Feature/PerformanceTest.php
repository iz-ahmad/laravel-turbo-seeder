<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

test('can seed 1000 records in under 5 seconds', function () {
    $start = microtime(true);

    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email', 'age'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
            'age' => random_int(18, 80),
        ])
        ->count(1000)
        ->run();

    $duration = microtime(true) - $start;

    expect($duration)->toBeLessThan(5)
        ->and($result->success)->toBeTrue()
        ->and(DB::table('test_users')->count())->toBe(1000);
});

test('uses less than 256mb memory for 5000 records', function () {
    $startMemory = memory_get_usage(true);

    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email', 'age'])
        ->generate(fn ($i) => [
            'name' => str_repeat('User', 10)." {$i}",
            'email' => "user{$i}@test.com",
            'age' => random_int(18, 80),
        ])
        ->count(5000)
        ->run();

    $peakMemory = memory_get_peak_usage(true) - $startMemory;
    $peakMemoryMB = $peakMemory / 1024 / 1024;

    expect($peakMemoryMB)->toBeLessThan(256)
        ->and($result->success)->toBeTrue();
});

test('maintains consistent performance across multiple runs', function () {
    $durations = [];

    for ($run = 0; $run < 3; $run++) {
        DB::table('test_users')->truncate();

        $start = microtime(true);

        TurboSeeder::create('test_users')
            ->columns(['name', 'email'])
            ->generate(fn ($i) => [
                'name' => "User {$i}",
                'email' => "user{$i}@test.com",
            ])
            ->count(1000)
            ->run();

        $durations[] = microtime(true) - $start;
    }

    $avgDuration = array_sum($durations) / count($durations);
    $maxVariation = max($durations) - min($durations);

    expect($maxVariation)->toBeLessThan($avgDuration * 0.5);
});

test('handles large records efficiently', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => [
            'name' => str_repeat('LongName', 10)." {$i}",
            'email' => str_repeat('long', 10)."user{$i}@test.com",
        ])
        ->count(1000)
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->durationSeconds)->toBeLessThan(10);
});
