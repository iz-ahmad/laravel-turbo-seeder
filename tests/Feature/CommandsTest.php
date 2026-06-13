<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

test('can run turbo seeder test connection command', function () {
    $this->artisan('turbo-seeder:test-connection')
        ->expectsOutputToContain('Testing connection')
        ->assertSuccessful();
});

test('test connection command shows driver info', function () {
    $this->artisan('turbo-seeder:test-connection', ['connection' => 'testing'])
        ->expectsOutputToContain('SQLite')
        ->expectsOutputToContain('DEFAULT strategy')
        ->assertSuccessful();
});

test('clear cache command works', function () {
    $tempPath = sys_get_temp_dir().'/turbo-seeder-test';

    if (! is_dir($tempPath)) {
        mkdir($tempPath, 0755, true);
    }

    file_put_contents($tempPath.'/test.csv', 'test data');

    $this->artisan('turbo-seeder:clear-cache')
        ->expectsOutputToContain('Clearing TurboSeeder cache')
        ->assertSuccessful();
});

test('benchmark command validates connection', function () {
    $this->artisan('turbo-seeder:benchmark', [
        '--connection' => 'testing',
        '--records' => 100,
    ])
        ->expectsOutputToContain('Starting TurboSeeder Performance Benchmark')
        ->assertSuccessful();
});

test('benchmark command refuses to drop an existing table', function () {
    // test_users is created by the test migrations, so it must not be dropped.
    $this->artisan('turbo-seeder:benchmark', [
        '--connection' => 'testing',
        '--table' => 'test_users',
        '--records' => 100,
    ])
        ->expectsOutputToContain('already exists')
        ->assertFailed();

    expect(Schema::connection('testing')->hasTable('test_users'))->toBeTrue();
});
