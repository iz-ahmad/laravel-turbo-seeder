<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\DTOs\SeederResultDTO;

test('can create successful result', function () {
    $result = new SeederResultDTO(
        success: true,
        recordsInserted: 1000,
        durationSeconds: 5.5,
        peakMemoryBytes: 50 * 1024 * 1024
    );

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(1000)
        ->and($result->durationSeconds)->toBe(5.5)
        ->and($result->peakMemoryBytes)->toBe(50 * 1024 * 1024)
        ->and($result->errorMessage)->toBeNull();
});

test('can create failed result', function () {
    $result = new SeederResultDTO(
        success: false,
        recordsInserted: 0,
        errorMessage: 'Something went wrong'
    );

    expect($result->success)->toBeFalse()
        ->and($result->recordsInserted)->toBe(0)
        ->and($result->errorMessage)->toBe('Something went wrong');
});

test('calculates records per second', function () {
    $result = new SeederResultDTO(
        success: true,
        recordsInserted: 1000,
        durationSeconds: 5.0
    );

    expect($result->getRecordsPerSecond())->toBe(200.0);
});

test('returns zero rate when duration is zero', function () {
    $result = new SeederResultDTO(
        success: true,
        recordsInserted: 1000,
        durationSeconds: 0.0
    );

    expect($result->getRecordsPerSecond())->toBe(0.0);
});

test('converts memory to megabytes', function () {
    $result = new SeederResultDTO(
        success: true,
        recordsInserted: 1000,
        peakMemoryBytes: 100 * 1024 * 1024
    );

    expect($result->getPeakMemoryInMB())->toBe(100.0);
});

test('converts result to array', function () {
    $result = new SeederResultDTO(
        success: true,
        recordsInserted: 1000,
        durationSeconds: 10.0,
        peakMemoryBytes: 50 * 1024 * 1024
    );

    $array = $result->toArray();

    expect($array)->toBeArray()
        ->and($array['success'])->toBeTrue()
        ->and($array['records_inserted'])->toBe(1000)
        ->and($array['duration_seconds'])->toBe(10.0)
        ->and($array['peak_memory_mb'])->toBe(50.0)
        ->and($array['records_per_second'])->toBe(100.0);
});
