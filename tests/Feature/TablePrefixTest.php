<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

beforeEach(function () {
    DB::connection('testing')->setTablePrefix('pfx_');

    Schema::dropIfExists('prefixed_items');
    Schema::create('prefixed_items', function ($table) {
        $table->integer('n');
    });
});

afterEach(function () {
    Schema::dropIfExists('prefixed_items');
    DB::connection('testing')->setTablePrefix('');
});

test('default strategy seeds a prefixed table', function () {
    TurboSeeder::forTable('prefixed_items')
        ->columns(['n'])
        ->generate(fn ($i) => ['n' => $i])
        ->count(25)
        ->withoutProgressTracking()
        ->run();

    expect(DB::table('prefixed_items')->count())->toBe(25);
});

test('csv strategy seeds a prefixed table', function () {
    TurboSeeder::forTable('prefixed_items')
        ->columns(['n'])
        ->generate(fn ($i) => ['n' => $i])
        ->count(25)
        ->useCsvStrategy()
        ->withoutProgressTracking()
        ->run();

    expect(DB::table('prefixed_items')->count())->toBe(25);
});

test('upsert works on a prefixed table', function () {
    Schema::dropIfExists('prefixed_items');
    Schema::create('prefixed_items', function ($table) {
        $table->integer('n')->unique();
        $table->string('label');
    });

    foreach (['first', 'second'] as $label) {
        TurboSeeder::forTable('prefixed_items')
            ->columns(['n', 'label'])
            ->generate(fn ($i) => ['n' => $i, 'label' => $label])
            ->count(5)
            ->upsert(['n'])
            ->withoutProgressTracking()
            ->run();
    }

    expect(DB::table('prefixed_items')->count())->toBe(5)
        ->and(DB::table('prefixed_items')->value('label'))->toBe('second');
});

test('postgres sequence sync works on a prefixed table', function () {
    Schema::dropIfExists('prefixed_items');
    Schema::create('prefixed_items', function ($table) {
        $table->id();
        $table->integer('n');
    });

    TurboSeeder::forTable('prefixed_items')
        ->columns(['id', 'n'])
        ->generate(fn ($i) => ['id' => $i + 1, 'n' => $i])
        ->count(5)
        ->withoutProgressTracking()
        ->run();

    expect(DB::table('prefixed_items')->insertGetId(['n' => 99]))->toBe(6);
});
