<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

test('dry-run does not commit any rows', function () {
    $result = TurboSeeder::forTable('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@dryrun.test"])
        ->count(20)
        ->dryRun()
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->isDryRun)->toBeTrue()
        ->and($result->recordsInserted)->toBe(20)
        ->and(DB::table('test_users')->count())->toBe(0);
});

test('dry-run combined with withoutTransactions throws and writes nothing', function () {
    // Without a transaction there is nothing to roll back, so rows would be
    // permanently committed. The builder must refuse this combination loudly.
    $run = fn () => TurboSeeder::forTable('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@dryrun-notx.test"])
        ->count(5)
        ->dryRun()
        ->withoutTransactions()
        ->run();

    expect($run)->toThrow(InvalidArgumentException::class)
        ->and(DB::table('test_users')->count())->toBe(0);
});

test('dryRun false behaves as normal seeding', function () {
    $result = TurboSeeder::forTable('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@nodryrun.test"])
        ->count(10)
        ->dryRun(false)
        ->run();

    expect($result->isDryRun)->toBeFalse()
        ->and(DB::table('test_users')->count())->toBe(10);
});
