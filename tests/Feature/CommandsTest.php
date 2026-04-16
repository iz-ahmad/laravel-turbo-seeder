<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

test('can run turbo seeder test connection command', function () {
    $output = new BufferedOutput;
    Artisan::call('turbo-seeder:test-connection', [], $output);

    expect($output->fetch())->toContain('Testing connection');
});

test('test connection command shows driver info', function () {
    $output = new BufferedOutput;
    Artisan::call('turbo-seeder:test-connection', ['connection' => 'testing'], $output);

    $text = $output->fetch();

    expect($text)->toMatch('/MySQL|PostgreSQL|SQLite/')
        ->and($text)->toContain('DEFAULT strategy');
});

test('clear cache command works', function () {
    $tempPath = sys_get_temp_dir().'/turbo-seeder-test';

    if (! is_dir($tempPath)) {
        mkdir($tempPath, 0755, true);
    }

    file_put_contents($tempPath.'/test.csv', 'test data');

    $output = new BufferedOutput;
    Artisan::call('turbo-seeder:clear-cache', [], $output);

    expect($output->fetch())->toContain('Clearing TurboSeeder cache');
});

test('benchmark command validates connection', function () {
    $output = new BufferedOutput;
    Artisan::call('turbo-seeder:benchmark', [
        '--connection' => 'testing',
        '--records' => 100,
    ], $output);

    $text = $output->fetch();

    expect($text)->toContain('Starting TurboSeeder Performance Benchmark');
});
