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

test('an unsafe connection prefix fails before the table is truncated or written', function () {
    DB::table('prefixed_items')->insert(['n' => 1]);

    $connection = DB::connection('testing');
    $connection->setTablePrefix('bad-prefix_');

    try {
        TurboSeeder::forTable('prefixed_items')
            ->columns(['n'])
            ->generate(fn ($i) => ['n' => $i])
            ->count(5)
            ->truncate()
            ->withoutProgressTracking()
            ->run();
    } catch (InvalidArgumentException $e) {
        $message = $e->getMessage();
    } finally {
        $connection->setTablePrefix('pfx_');
    }

    expect($message ?? null)->toContain('Invalid table prefix')
        ->and(DB::table('prefixed_items')->count())->toBe(1);
});

test('benchmark command works on a prefixed connection', function () {
    $this->artisan('turbo-seeder:benchmark', ['--records' => 200, '--table' => 'bench_items'])
        ->assertSuccessful();

    expect(Schema::hasTable('bench_items'))->toBeFalse();
});

test('truncate empties a prefixed table before seeding', function () {
    DB::table('prefixed_items')->insert(['n' => 1]);

    TurboSeeder::forTable('prefixed_items')
        ->columns(['n'])
        ->generate(fn ($i) => ['n' => $i + 10])
        ->count(3)
        ->truncate()
        ->withoutProgressTracking()
        ->run();

    expect(DB::table('prefixed_items')->pluck('n')->all())->toBe([10, 11, 12]);
});
