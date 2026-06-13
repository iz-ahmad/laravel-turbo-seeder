<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Builder\TurboSeederBuilder;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

test('columnsFromSchema derives columns excluding the auto-increment key', function () {
    $builder = TurboSeeder::create('test_users')->columnsFromSchema();

    // test_users: id (auto-inc, excluded), name, email, age, created_at, updated_at
    expect($builder->getColumns())->not->toContain('id')
        ->and($builder->getColumns())->toContain('name')
        ->and($builder->getColumns())->toContain('email')
        ->and($builder->getColumns())->toContain('age');
});

test('columnsFromSchema seeds successfully when the generator supplies values', function () {
    $result = TurboSeeder::create('test_users')
        ->columnsFromSchema()
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@schema.test",
            'age' => 25,
            'created_at' => now(),
            'updated_at' => now(),
        ])
        ->count(5)
        ->run();

    expect($result->success)->toBeTrue()
        ->and(DB::table('test_users')->count())->toBe(5);
});

test('columnsFromSchema requires a table first', function () {
    expect(fn () => app(TurboSeederBuilder::class)->columnsFromSchema())
        ->toThrow(InvalidArgumentException::class, 'before columnsFromSchema');
});
