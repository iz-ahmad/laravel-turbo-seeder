<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use IzAhmad\TurboSeeder\Helpers\TurboData;

beforeEach(function () {
    TurboData::reset();
});

test('sequentialDate logs warning once when generated date exceeds MySQL TIMESTAMP ceiling', function () {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $msg) => str_contains($msg, 'sequentialDate()') && str_contains($msg, '2038'));

    // 2025-01-01 + 4767 days = 2038-01-20, first date past the ceiling
    TurboData::sequentialDate('2025-01-01', 'day', 4767);
});

test('sequentialDate does not warn when generated date is within MySQL TIMESTAMP ceiling', function () {
    Log::shouldReceive('warning')->never();

    TurboData::sequentialDate('2025-01-01', 'day', 100);
});

test('sequentialDate warns independently per distinct start date', function () {
    Log::shouldReceive('warning')->twice();

    TurboData::sequentialDate('2025-01-01', 'day', 4767);
    TurboData::sequentialDate('2026-01-01', 'day', 4767);
});

test('sequentialDate TIMESTAMP warning resets after TurboData::reset()', function () {
    Log::shouldReceive('warning')->twice();

    TurboData::sequentialDate('2025-01-01', 'day', 4767);
    TurboData::reset();
    TurboData::sequentialDate('2025-01-01', 'day', 4767);
});

test('dateRange logs warning once when upper bound exceeds MySQL TIMESTAMP ceiling', function () {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $msg) => str_contains($msg, 'dateRange()') && str_contains($msg, '2038'));

    TurboData::dateRange('2030-01-01', '2045-12-31');
    TurboData::dateRange('2030-01-01', '2045-12-31'); // same $to — no additional warning
});

test('dateRange does not warn when range stays within MySQL TIMESTAMP ceiling', function () {
    Log::shouldReceive('warning')->never();

    TurboData::dateRange('2020-01-01', '2037-12-31');
});

test('dateRange TIMESTAMP warning resets after TurboData::reset()', function () {
    Log::shouldReceive('warning')->twice();

    TurboData::dateRange('2030-01-01', '2045-12-31');
    TurboData::reset();
    TurboData::dateRange('2030-01-01', '2045-12-31');
});

test('dateRange and sequentialDate warn independently for the same date string', function () {
    Log::shouldReceive('warning')->twice();

    TurboData::dateRange('2030-01-01', '2045-01-01');
    TurboData::sequentialDate('2045-01-01', 'day', 0);
});
