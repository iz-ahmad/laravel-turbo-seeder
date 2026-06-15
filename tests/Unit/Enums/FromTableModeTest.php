<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Enums\FromTableMode;

test('normalize accepts valid string "cycle"', function () {
    expect(FromTableMode::normalize('cycle'))->toBe(FromTableMode::CYCLE);
});

test('normalize accepts valid string "random"', function () {
    expect(FromTableMode::normalize('random'))->toBe(FromTableMode::RANDOM);
});

test('normalize throws on unknown string', function () {
    FromTableMode::normalize('sequential');
})->throws(InvalidArgumentException::class, 'fromTable() $mode must be "cycle" or "random".');
