<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Enums\MemoryThreshold;
use IzAhmad\TurboSeeder\Services\MemoryManager;

test('can get current memory usage', function () {
    $manager = new MemoryManager;

    expect($manager->getCurrentMemoryUsage())->toBePositiveNumber();
});

test('can calculate memory usage percentage', function () {
    $manager = new MemoryManager(['memory' => ['limit_mb' => 256]]);

    $percentage = $manager->getMemoryUsagePercentage();

    expect($percentage)->toBeFloat()
        ->toBeGreaterThanOrEqual(0)
        ->toBeLessThan(100);
});

test('returns correct threshold status', function () {
    $manager = new MemoryManager;

    $status = $manager->getThresholdStatus();

    expect($status)->toBeInstanceOf(MemoryThreshold::class);
});

test('determines when to garbage collect based on chunk threshold', function () {
    $manager = new MemoryManager([
        'memory' => [
            'gc_threshold_percent' => 100, // disable memory-based GC
            'force_gc_after_chunks' => 5,
        ],
    ]);

    // Counter only advances inside forceCleanup(), not in shouldGarbageCollect().
    // After 4 calls, counter is below threshold.
    for ($i = 0; $i < 4; $i++) {
        $manager->forceCleanup();
    }
    expect($manager->shouldGarbageCollect())->toBeFalse();

    // 5th call hits the threshold → GC fires and counter resets to 0.
    $manager->forceCleanup();
    expect($manager->shouldGarbageCollect())->toBeFalse(); // counter reset after GC
});

test('can force cleanup', function () {
    $manager = new MemoryManager([
        'memory' => [
            'gc_threshold_percent' => 0,
            'force_gc_after_chunks' => 1,
        ],
    ]);

    expect(fn () => $manager->forceCleanup())->not->toThrow(Exception::class);
});

test('returns memory limit', function () {
    $manager = new MemoryManager(['memory' => ['limit_mb' => 128]]);

    expect($manager->getMemoryLimit())->toBe(128 * 1024 * 1024);
});

test('can get peak memory usage', function () {
    $manager = new MemoryManager;

    expect($manager->getPeakMemoryUsage())->toBePositiveNumber();
});
