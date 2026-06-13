<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit/DTOs', 'Unit/Enums', 'Unit/Services', 'Unit/Actions', 'Unit/Helpers', 'Unit/Strategies');

/*
|--------------------------------------------------------------------------
| Test Helpers
|--------------------------------------------------------------------------
*/

expect()->extend('toBeWithinRange', function (float $min, float $max) {
    return $this->toBeGreaterThanOrEqual($min)
        ->toBeLessThanOrEqual($max);
});

expect()->extend('toBePositiveNumber', function () {
    return $this->toBeGreaterThan(0);
});

expect()->extend('toBeMemoryInMB', function () {
    return $this->toBeFloat()
        ->toBeGreaterThanOrEqual(0);
});
