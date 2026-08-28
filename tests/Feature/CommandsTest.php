<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use IzAhmad\TurboSeeder\Tests\Fixtures\TurboSeederRunCommandMultiCallTestSeeder;
use IzAhmad\TurboSeeder\Tests\Fixtures\TurboSeederRunCommandTestSeeder;

test('can run turbo seeder test connection command', function () {
    $this->artisan('turbo-seeder:test-connection')
        ->expectsOutputToContain('Testing connection')
        ->assertSuccessful();
});

test('test connection command shows driver info', function () {
    $displayName = match (config('database.connections.testing.driver')) {
        'mysql' => 'MySQL',
        'pgsql' => 'PostgreSQL',
        default => 'SQLite',
    };

    $this->artisan('turbo-seeder:test-connection', ['connection' => 'testing'])
        ->expectsOutputToContain($displayName)
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

test('turbo-seeder:run outputs info row with table, strategy and count', function () {
    $this->artisan('turbo-seeder:run', ['seeder' => TurboSeederRunCommandTestSeeder::class])
        ->expectsOutputToContain('table test_users   strategy default   count 10')
        ->assertSuccessful();
});

test('turbo-seeder:run --force skips the production confirmation prompt', function () {
    $original = app()->environment();
    app()->instance('env', 'production');

    try {
        $this->artisan('turbo-seeder:run', [
            'seeder' => TurboSeederRunCommandTestSeeder::class,
            '--force' => true,
        ])->assertSuccessful();
    } finally {
        app()->instance('env', $original);
    }
});

test('turbo-seeder:run prompts and aborts without seeding when declined in production', function () {
    $original = app()->environment();
    app()->instance('env', 'production');

    try {
        $this->artisan('turbo-seeder:run', ['seeder' => TurboSeederRunCommandTestSeeder::class])
            ->expectsConfirmation('Are you sure you want to run this command?', 'no')
            ->assertFailed();
    } finally {
        app()->instance('env', $original);
    }
});

test('turbo-seeder:run prompts and proceeds when confirmed in production', function () {
    $original = app()->environment();
    app()->instance('env', 'production');

    try {
        $this->artisan('turbo-seeder:run', ['seeder' => TurboSeederRunCommandTestSeeder::class])
            ->expectsConfirmation('Are you sure you want to run this command?', 'yes')
            ->assertSuccessful();
    } finally {
        app()->instance('env', $original);
    }
});

test('turbo-seeder:run confirms once even when the seeder makes multiple ->run() calls', function () {
    $original = app()->environment();
    app()->instance('env', 'production');

    try {
        // Only one expectsConfirmation() is queued
        $this->artisan('turbo-seeder:run', ['seeder' => TurboSeederRunCommandMultiCallTestSeeder::class])
            ->expectsConfirmation('Are you sure you want to run this command?', 'yes')
            ->assertSuccessful();
    } finally {
        app()->instance('env', $original);
    }
});

test('benchmark command drops the benchmark table even when seeding fails', function () {
    $table = 'benchmark_failure_'.time();

    $this->artisan('turbo-seeder:benchmark', [
        '--connection' => 'testing',
        '--table' => $table,
        '--records' => 0,
    ])->assertFailed();

    expect(Schema::connection('testing')->hasTable($table))->toBeFalse();
});
