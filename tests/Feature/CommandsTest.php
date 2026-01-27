<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

test('can run turbo seeder test connection command', function () {
    Artisan::call('turbo-seeder:test-connection');

    expect(Artisan::output())->toContain('Testing connection');
});

test('test connection command shows driver info', function () {
    Artisan::call('turbo-seeder:test-connection', ['connection' => 'testing']);

    $output = Artisan::output();

    expect($output)->toMatch('/MySQL|PostgreSQL|SQLite/')
        ->and($output)->toContain('DEFAULT strategy');
});

test('clear cache command works', function () {
    $tempPath = sys_get_temp_dir().'/turbo-seeder-test';

    if (! is_dir($tempPath)) {
        mkdir($tempPath, 0755, true);
    }

    file_put_contents($tempPath.'/test.csv', 'test data');

    Artisan::call('turbo-seeder:clear-cache');

    expect(Artisan::output())->toContain('Clearing TurboSeeder cache');
});

test('benchmark command validates connection', function () {
    Artisan::call('turbo-seeder:benchmark', [
        '--connection' => 'testing',
        '--records' => 100,
    ]);

    $output = Artisan::output();

    expect($output)->toContain('Starting TurboSeeder Performance Benchmark');
});
