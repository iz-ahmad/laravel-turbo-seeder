<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

beforeEach(fn () => TurboData::reset());

test('validate() throws when generator returns a Closure value for a column', function () {
    expect(fn () => TurboSeeder::forTable('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => TurboData::uniqueEmail(), // forgot to call $fn($i)
        ])
        ->count(1)
        ->run()
    )->toThrow(InvalidArgumentException::class, 'email');
});

test('validate() error message names all Closure-valued columns', function () {
    $message = null;

    try {
        TurboSeeder::forTable('test_users')
            ->columns(['name', 'email'])
            ->generate(fn ($i) => [
                'name' => TurboData::uniqueUsername(), // also a Closure
                'email' => TurboData::uniqueEmail(),
            ])
            ->count(1)
            ->run();
    } catch (InvalidArgumentException $e) {
        $message = $e->getMessage();
    }

    expect($message)
        ->toContain('name')
        ->toContain('email')
        ->toContain('$fn($i)');
});

test('validate() passes when closures are correctly called outside the generator', function () {
    $email = TurboData::uniqueEmail();

    Log::shouldReceive('warning')->never();

    $result = TurboSeeder::forTable('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "U{$i}", 'email' => $email($i)])
        ->count(2)
        ->run();

    expect($result->success)->toBeTrue();
});

test('uniqueEmail() logs one warning when called inside the generator', function () {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $msg) => str_contains($msg, 'uniqueEmail') && str_contains($msg, 'inside the generate()'));

    TurboSeeder::forTable('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => TurboData::uniqueEmail()($i), // wrong: new token every row
        ])
        ->count(3)
        ->run();
});

test('warning fires only once per seeder run even for many rows', function () {
    Log::shouldReceive('warning')
        ->once(); // not 50 times

    TurboSeeder::forTable('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => TurboData::uniqueEmail()($i),
        ])
        ->count(50)
        ->run();
});

test('fromTable() warns when called inside the generator', function () {
    TurboSeeder::forTable('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "Ref {$i}", 'email' => "ref{$i}@guard.test"])
        ->count(3)
        ->run();

    TurboData::reset();

    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $msg) => str_contains($msg, 'fromTable') && str_contains($msg, 'inside the generate()'));

    TurboSeeder::forTable('test_counters')
        ->columns(['slug', 'hits'])
        ->generate(fn ($i) => [
            'slug' => 'slug-'.TurboData::fromTable('test_users', 'name')($i).'-'.$i, // wrong: inside
            'hits' => $i,
        ])
        ->count(1)
        ->run();
});

test('closure-factory helpers do NOT warn when called outside the generator', function () {
    Log::shouldReceive('warning')->never();

    $email = TurboData::uniqueEmail();
    $username = TurboData::uniqueUsername('usr');

    TurboSeeder::forTable('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => $username($i), 'email' => $email($i)])
        ->count(2)
        ->run();
});

test('markGeneratorActive flag is reset by TurboData::reset()', function () {
    TurboData::markGeneratorActive(true);
    TurboData::reset();

    expect(TurboData::isInsideGenerator())->toBeFalse();
});

test('markGeneratorActive(true) clears the warned-methods list so the next run warns again', function () {
    Log::shouldReceive('warning')->twice();

    TurboSeeder::forTable('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "U{$i}", 'email' => TurboData::uniqueEmail()($i)])
        ->count(1)
        ->run();

    TurboData::reset();

    TurboSeeder::forTable('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "V{$i}", 'email' => TurboData::uniqueEmail()($i)])
        ->count(1)
        ->run();
});
