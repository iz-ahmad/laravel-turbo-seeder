<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Enums\MemoryThreshold;

test('has correct threshold values', function () {
    expect(MemoryThreshold::LOW->value)->toBe(50)
        ->and(MemoryThreshold::MEDIUM->value)->toBe(70)
        ->and(MemoryThreshold::HIGH->value)->toBe(80)
        ->and(MemoryThreshold::CRITICAL->value)->toBe(90);
});

test('returns correct percentage', function () {
    expect(MemoryThreshold::LOW->getPercentage())->toBe(50)
        ->and(MemoryThreshold::CRITICAL->getPercentage())->toBe(90);
});

test('should garbage collect at high thresholds', function () {
    expect(MemoryThreshold::LOW->shouldGarbageCollect())->toBeFalse()
        ->and(MemoryThreshold::MEDIUM->shouldGarbageCollect())->toBeFalse()
        ->and(MemoryThreshold::HIGH->shouldGarbageCollect())->toBeTrue()
        ->and(MemoryThreshold::CRITICAL->shouldGarbageCollect())->toBeTrue();
});

test('should warn at critical threshold', function () {
    expect(MemoryThreshold::LOW->shouldWarn())->toBeFalse()
        ->and(MemoryThreshold::MEDIUM->shouldWarn())->toBeFalse()
        ->and(MemoryThreshold::HIGH->shouldWarn())->toBeFalse()
        ->and(MemoryThreshold::CRITICAL->shouldWarn())->toBeTrue();
});

test('can create from percentage', function () {
    expect(MemoryThreshold::fromPercentage(45))->toBe(MemoryThreshold::LOW)
        ->and(MemoryThreshold::fromPercentage(75))->toBe(MemoryThreshold::MEDIUM)
        ->and(MemoryThreshold::fromPercentage(85))->toBe(MemoryThreshold::HIGH)
        ->and(MemoryThreshold::fromPercentage(95))->toBe(MemoryThreshold::CRITICAL);
});
