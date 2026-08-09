<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

test('withTimestamps fills created_at and updated_at on the generate() path', function () {
    TurboSeeder::forTable('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@ts.test"])
        ->withTimestamps()
        ->count(10)
        ->run();

    expect(DB::table('test_users')->whereNotNull('created_at')->count())->toBe(10)
        ->and(DB::table('test_users')->whereNotNull('updated_at')->count())->toBe(10);
});

test('withTimestamps does not override timestamps the generator already sets', function () {
    $fixed = '2020-01-01 00:00:00';

    TurboSeeder::forTable('test_users')
        ->columns(['name', 'email', 'created_at'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@ts2.test",
            'created_at' => $fixed,
        ])
        ->withTimestamps()
        ->count(4)
        ->run();

    expect(DB::table('test_users')->where('created_at', $fixed)->count())->toBe(4);
});

test('truncate empties the table before seeding', function () {
    DB::table('test_users')->insert([
        'name' => 'Existing',
        'email' => 'existing@truncate.test',
    ]);

    expect(DB::table('test_users')->count())->toBe(1);

    TurboSeeder::forTable('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@truncate.test"])
        ->truncate()
        ->count(5)
        ->run();

    expect(DB::table('test_users')->count())->toBe(5)
        ->and(DB::table('test_users')->where('email', 'existing@truncate.test')->exists())->toBeFalse();
});

test('truncate cannot be combined with dryRun', function () {
    $run = fn () => TurboSeeder::forTable('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@td.test"])
        ->truncate()
        ->dryRun()
        ->count(5)
        ->run();

    expect($run)->toThrow(InvalidArgumentException::class);
});
