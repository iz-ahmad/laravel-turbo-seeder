<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Contracts\ProgressTrackerInterface;
use IzAhmad\TurboSeeder\Enums\SeederStrategy;
use IzAhmad\TurboSeeder\Exceptions\ProductionEnvironmentException;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

test('run() throws outside local/testing without force()', function () {
    $original = app()->environment();
    app()->instance('env', 'production');

    try {
        TurboSeeder::forTable('test_users')
            ->columns(['name', 'email'])
            ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@prodguard.test"])
            ->count(3)
            ->run();

        $this->fail('Expected a RuntimeException to be thrown.');
    } catch (RuntimeException $e) {
        expect($e->getPrevious())->toBeInstanceOf(ProductionEnvironmentException::class);
    } finally {
        app()->instance('env', $original);
    }
});

test('force() lets run() proceed outside local/testing', function () {
    $original = app()->environment();
    app()->instance('env', 'production');

    try {
        $result = TurboSeeder::forTable('test_users')
            ->columns(['name', 'email'])
            ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@prodforce.test"])
            ->force()
            ->count(3)
            ->run();

        expect($result->success)->toBeTrue()
            ->and($result->recordsInserted)->toBe(3);
    } finally {
        app()->instance('env', $original);
    }
});

test('durationSeconds excludes truncate time', function () {
    $tracker = new class implements ProgressTrackerInterface
    {
        public function writeHeader(int $total, SeederStrategy $strategy, string $table): void {}

        public function start(int $total, SeederStrategy $strategy = SeederStrategy::DEFAULT, string $table = ''): void {}

        public function advance(int $step = 1): void {}

        public function finish(int $recordsInserted = 0): void {}

        public function setMessage(string $message): void {}

        public function getPercentage(): float
        {
            return 0.0;
        }

        public function warn(string $message): void {}

        public function notice(string $message): void
        {
            if (str_contains($message, 'Truncating')) {
                usleep(1_000_000);
            }
        }
    };

    app()->instance(ProgressTrackerInterface::class, $tracker);

    $result = TurboSeeder::forTable('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@duration.test"])
        ->truncate()
        ->count(3)
        ->run();

    expect($result->durationSeconds)->toBeLessThan(0.5);
});
