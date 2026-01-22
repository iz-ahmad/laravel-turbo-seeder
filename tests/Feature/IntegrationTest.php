<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

test('can seed multiple tables in sequence', function () {
    TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(10)
        ->run();

    TurboSeeder::create('test_posts')
        ->columns(['user_id', 'title', 'content'])
        ->generate(fn ($i) => [
            'user_id' => ($i % 10) + 1,
            'title' => "Post {$i}",
            'content' => "Content {$i}",
        ])
        ->count(30)
        ->run();

    expect(DB::table('test_users')->count())->toBe(10)
        ->and(DB::table('test_posts')->count())->toBe(30);
});

test('can seed with different strategies in same test', function () {
    DB::table('test_users')->truncate();

    TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(50)
        ->useDefaultStrategy()
        ->run();

    expect(DB::table('test_users')->count())->toBe(50);

    DB::table('test_users')->truncate();

    TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(50)
        ->useCsvStrategy()
        ->run();

    expect(DB::table('test_users')->count())->toBe(50);
});

test('can seed with complex data types', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email', 'age', 'created_at'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
            'age' => random_int(18, 80),
            'created_at' => now()->subDays(random_int(0, 365)),
        ])
        ->count(100)
        ->run();

    expect($result->success)->toBeTrue()
        ->and(DB::table('test_users')->count())->toBe(100);

    $user = DB::table('test_users')->first();
    expect($user->age)->toBeInt()
        ->and($user->created_at)->not->toBeNull();
});
