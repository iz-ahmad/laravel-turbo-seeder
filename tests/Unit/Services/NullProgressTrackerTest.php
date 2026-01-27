<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Services\NullProgressTracker;

test('can start progress tracking', function () {
    $tracker = new NullProgressTracker;
    $tracker->start(100);

    expect($tracker->getPercentage())->toBe(0.0);
});

test('can advance progress', function () {
    $tracker = new NullProgressTracker;
    $tracker->start(100);
    $tracker->advance(25);

    expect($tracker->getPercentage())->toBe(25.0);
});

test('can finish progress', function () {
    $tracker = new NullProgressTracker;
    $tracker->start(100);
    $tracker->finish();

    expect($tracker->getPercentage())->toBe(100.0);
});

test('can set message without errors', function () {
    $tracker = new NullProgressTracker;
    $tracker->setMessage('Test message');

    expect($tracker->getPercentage())->toBe(0.0);
});

test('calculates percentage correctly', function () {
    $tracker = new NullProgressTracker;
    $tracker->start(200);
    $tracker->advance(50);

    expect($tracker->getPercentage())->toBe(25.0);
});

test('returns zero percentage when total is zero', function () {
    $tracker = new NullProgressTracker;
    $tracker->start(0);

    expect($tracker->getPercentage())->toBe(0.0);
});
