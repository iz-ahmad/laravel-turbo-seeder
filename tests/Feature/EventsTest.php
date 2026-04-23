<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use IzAhmad\TurboSeeder\Events\TurboSeederCompleted;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

test('TurboSeederCompleted event is dispatched after successful seeding', function () {
    Event::fake([TurboSeederCompleted::class]);

    TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@event.test"])
        ->count(5)
        ->run();

    Event::assertDispatched(TurboSeederCompleted::class, function (TurboSeederCompleted $event) {
        return $event->table === 'test_users'
            && $event->result->success === true
            && $event->result->recordsInserted === 5;
    });
});

test('TurboSeederCompleted event is dispatched on dry-run with isDryRun flag', function () {
    Event::fake([TurboSeederCompleted::class]);

    TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@dryevent.test"])
        ->count(3)
        ->dryRun()
        ->run();

    Event::assertDispatched(TurboSeederCompleted::class, function (TurboSeederCompleted $event) {
        return $event->result->isDryRun === true;
    });
});

test('TurboSeederCompleted event is not dispatched when seeding fails', function () {
    Event::fake([TurboSeederCompleted::class]);

    expect(fn () => TurboSeeder::create('test_users')
        ->columns(['name', 'email', 'nonexistent_column'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@fail.test", 'nonexistent_column' => 'x'])
        ->count(5)
        ->run())
        ->toThrow(RuntimeException::class);

    Event::assertNotDispatched(TurboSeederCompleted::class);
});
