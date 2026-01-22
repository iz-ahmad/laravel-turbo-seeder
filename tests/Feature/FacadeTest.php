<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

test('facade can create builder', function () {
    $builder = TurboSeeder::create('test_users');

    expect($builder)->toBeInstanceOf(\IzAhmad\TurboSeeder\Builder\TurboSeederBuilder::class);
});

test('facade can create builder with table name', function () {
    $builder = TurboSeeder::create('test_users');

    expect($builder->getTable())->toBe('test_users');
});

test('facade can execute seeding', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(10)
        ->run();

    expect($result->success)->toBeTrue()
        ->and(DB::table('test_users')->count())->toBe(10);
});
