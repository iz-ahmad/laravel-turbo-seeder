<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Tests\Fixtures\TestUserFactory;
use IzAhmad\TurboSeeder\Tests\Fixtures\TestUserModel;

test('fromFactory seeds rows using the factory definition', function () {
    $result = TurboSeeder::fromFactory(TestUserModel::factory())
        ->count(50)
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(50)
        ->and(DB::table('test_users')->count())->toBe(50);

    $row = DB::table('test_users')->first();
    expect($row->name)->not->toBeNull()
        ->and($row->email)->not->toBeNull();
});

test('fromFactory infers the table from the factory model', function () {
    TurboSeeder::fromFactory(TestUserFactory::new())
        ->count(5)
        ->run();

    expect(DB::table('test_users')->count())->toBe(5);
});

test('fromFactory auto-fills timestamps when the model uses them', function () {
    TurboSeeder::fromFactory(TestUserModel::factory())
        ->count(10)
        ->run();

    expect(DB::table('test_users')->whereNotNull('created_at')->count())->toBe(10)
        ->and(DB::table('test_users')->whereNotNull('updated_at')->count())->toBe(10);
});

test('fromFactory applies factory states', function () {
    TurboSeeder::fromFactory(TestUserModel::factory()->adult())
        ->count(8)
        ->run();

    expect(DB::table('test_users')->where('age', 40)->count())->toBe(8);
});

test('fromFactory can use the CSV strategy', function () {
    $result = TurboSeeder::fromFactory(TestUserModel::factory())
        ->count(30)
        ->useCsvStrategy()
        ->run();

    expect($result->success)->toBeTrue()
        ->and(DB::table('test_users')->count())->toBe(30);
});

test('withoutTimestamps disables auto timestamps on the factory path', function () {
    TurboSeeder::fromFactory(TestUserModel::factory())
        ->columns(['name', 'email', 'age'])
        ->withoutTimestamps()
        ->count(3)
        ->run();

    // created_at/updated_at are nullable-by-omission here; assert they were not set.
    expect(DB::table('test_users')->whereNull('created_at')->count())->toBe(3);
});
