<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

// ── argument validation (eager, before any DB call) ──────────────────────────

test('fromTable throws on empty table name', function () {
    TurboData::fromTable('');
})->throws(InvalidArgumentException::class, '$table must not be empty');

test('fromTable throws on empty column name', function () {
    TurboData::fromTable('test_users', '');
})->throws(InvalidArgumentException::class, '$column must not be empty');

test('fromTable throws on invalid mode', function () {
    TurboData::fromTable('test_users', 'id', 'shuffle');
})->throws(InvalidArgumentException::class, '$mode must be "cycle" or "random"');

test('fromTable returns a closure', function () {
    expect(TurboData::fromTable('test_users'))->toBeInstanceOf(Closure::class);
});

// ── pool loading ──────────────────────────────────────────────────────────────

test('fromTable loads pool lazily on first call', function () {
    DB::table('test_users')->insert([
        ['name' => 'A', 'email' => 'a@t.test'],
        ['name' => 'B', 'email' => 'b@t.test'],
    ]);

    $fn = TurboData::fromTable('test_users');

    // no DB hit yet - just a closure
    expect($fn)->toBeInstanceOf(Closure::class);

    // first call fires the query
    $value = $fn(0);
    expect($value)->not->toBeNull();
});

test('fromTable queries DB exactly once across many calls', function () {
    DB::table('test_users')->insert([
        ['name' => 'U1', 'email' => 'u1@t.test'],
        ['name' => 'U2', 'email' => 'u2@t.test'],
        ['name' => 'U3', 'email' => 'u3@t.test'],
    ]);

    $queryCount = 0;
    DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    $fn = TurboData::fromTable('test_users');

    for ($i = 0; $i < 50; $i++) {
        $fn($i);
    }

    expect($queryCount)->toBe(1);
});

test('fromTable throws when table column returns no rows', function () {
    $fn = TurboData::fromTable('test_users');
    $fn(0);
})->throws(RuntimeException::class, 'returned no rows');

// ── cycle mode ────────────────────────────────────────────────────────────────

test('fromTable cycles values deterministically by index', function () {
    DB::table('test_users')->insert([
        ['name' => 'X', 'email' => 'x@t.test'],
        ['name' => 'Y', 'email' => 'y@t.test'],
        ['name' => 'Z', 'email' => 'z@t.test'],
    ]);

    $ids = DB::table('test_users')->orderBy('id')->pluck('id')->toArray();
    $fn = TurboData::fromTable('test_users');

    expect($fn(0))->toBe($ids[0]);
    expect($fn(1))->toBe($ids[1]);
    expect($fn(2))->toBe($ids[2]);
    expect($fn(3))->toBe($ids[0]); // wraps
    expect($fn(4))->toBe($ids[1]);
});

test('fromTable cycle is the default mode', function () {
    DB::table('test_users')->insert([
        ['name' => 'P', 'email' => 'p@t.test'],
        ['name' => 'Q', 'email' => 'q@t.test'],
    ]);

    $ids = DB::table('test_users')->orderBy('id')->pluck('id')->toArray();
    $cycle = TurboData::fromTable('test_users');
    $explicit = TurboData::fromTable('test_users', 'id', 'cycle');

    // both should produce identical deterministic sequence
    for ($i = 0; $i < 6; $i++) {
        expect($cycle($i))->toBe($explicit($i));
    }
});

// ── random mode ───────────────────────────────────────────────────────────────

test('fromTable random mode returns values from the pool', function () {
    DB::table('test_users')->insert([
        ['name' => 'R1', 'email' => 'r1@t.test'],
        ['name' => 'R2', 'email' => 'r2@t.test'],
        ['name' => 'R3', 'email' => 'r3@t.test'],
    ]);

    $ids = DB::table('test_users')->pluck('id')->toArray();
    $fn = TurboData::fromTable('test_users', 'id', 'random');

    for ($i = 0; $i < 30; $i++) {
        expect($fn($i))->toBeIn($ids);
    }
});

// ── custom column ─────────────────────────────────────────────────────────────

test('fromTable respects custom column parameter', function () {
    DB::table('test_users')->insert([
        ['name' => 'Alice', 'email' => 'alice@t.test'],
        ['name' => 'Bob',   'email' => 'bob@t.test'],
    ]);

    $fn = TurboData::fromTable('test_users', 'email');

    expect($fn(0))->toBeIn(['alice@t.test', 'bob@t.test']);
    expect($fn(1))->toBeIn(['alice@t.test', 'bob@t.test']);
});

// ── integration: use inside a seeder generator ────────────────────────────────

test('fromTable works inside a seeder generator for FK assignment', function () {
    TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@fk.test"])
        ->count(5)
        ->run();

    $userIds = TurboData::fromTable('test_users');

    TurboSeeder::create('test_posts')
        ->columns(['user_id', 'title', 'content'])
        ->generate(fn ($i) => [
            'user_id' => $userIds($i),
            'title' => "Post {$i}",
            'content' => "Content {$i}",
        ])
        ->count(20)
        ->run();

    $seededUserIds = DB::table('test_users')->pluck('id')->toArray();
    $postUserIds = DB::table('test_posts')->pluck('user_id')->toArray();

    expect(DB::table('test_posts')->count())->toBe(20);

    foreach ($postUserIds as $uid) {
        expect($uid)->toBeIn($seededUserIds);
    }
});
