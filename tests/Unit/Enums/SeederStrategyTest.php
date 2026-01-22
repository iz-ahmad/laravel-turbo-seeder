<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Enums\SeederStrategy;

test('has correct strategy values', function () {
    expect(SeederStrategy::DEFAULT->value)->toBe('default')
        ->and(SeederStrategy::CSV->value)->toBe('csv');
});

test('returns correct description', function () {
    expect(SeederStrategy::DEFAULT->getDescription())
        ->toContain('bulk insert')
        ->and(SeederStrategy::CSV->getDescription())
        ->toContain('CSV-based');
});

test('identifies file-based strategies', function () {
    expect(SeederStrategy::DEFAULT->isFileBasedStrategy())->toBeFalse()
        ->and(SeederStrategy::CSV->isFileBasedStrategy())->toBeTrue();
});
