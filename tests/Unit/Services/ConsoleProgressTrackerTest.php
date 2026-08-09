<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Services\ConsoleProgressTracker;

test('getTotalRecordsInserted returns the count from a single finish()', function () {
    $tracker = new ConsoleProgressTracker;
    $tracker->start(100);
    $tracker->finish(100);

    expect($tracker->getTotalRecordsInserted())->toBe(100);
});

test('finish() records the count even after an earlier finish() finalized the bar', function () {
    // Mirrors the CSV strategy: the generation phase finishes the bar with no
    // count, then ExecuteSeederAction finishes again with the real count.
    $tracker = new ConsoleProgressTracker;
    $tracker->start(100);
    $tracker->finish();      // generation phase, count not yet known
    $tracker->finish(100);   // real insert count

    expect($tracker->getTotalRecordsInserted())->toBe(100);
});

test('getTotalRecordsInserted accumulates across multiple seed runs on one tracker', function () {
    $tracker = new ConsoleProgressTracker;

    $tracker->start(100);
    $tracker->finish(100);

    $tracker->start(200);
    $tracker->finish(200);

    expect($tracker->getTotalRecordsInserted())->toBe(300);
});
