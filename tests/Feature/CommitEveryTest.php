<?php

declare(strict_types=1);

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

test('commitEvery seeds all rows correctly', function () {
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@commit.test"])
        ->chunkSize(10)
        ->commitEvery(2)
        ->count(95)
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(95)
        ->and(DB::table('test_users')->count())->toBe(95);
});

test('commitEvery on CSV strategy logs a warning and still seeds', function () {
    $warnings = [];
    Event::listen(MessageLogged::class, function (MessageLogged $e) use (&$warnings) {
        if ($e->level === 'warning') {
            $warnings[] = $e->message;
        }
    });

    TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@csv.test"])
        ->count(5)
        ->useCsvStrategy()
        ->commitEvery(2)
        ->run();

    expect(collect($warnings)->contains(fn ($m) => str_contains($m, 'commitEvery')))->toBeTrue()
        ->and(DB::table('test_users')->count())->toBe(5);
});

test('commitEvery rejects values below one', function () {
    $run = fn () => TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@c.test"])
        ->commitEvery(0);

    expect($run)->toThrow(InvalidArgumentException::class);
});

test('commitEvery combined with useTransactions() is rejected', function () {
    expect(fn () => TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@c.test"])
        ->count(10)
        ->commitEvery(2)
        ->useTransactions()
        ->run()
    )->toThrow(InvalidArgumentException::class, 'commitEvery() and useTransactions() cannot be combined');
});
