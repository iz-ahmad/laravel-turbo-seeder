<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Helpers\ExceptionFormatter;

test('exception formatter returns full message when under limit', function () {
    config()->set('turbo-seeder.max_error_message_length_in_console', 100);

    $exception = new RuntimeException('Short error message');

    $formatted = ExceptionFormatter::format($exception);

    expect($formatted)->toBe('Short error message');
});

test('exception formatter truncates long messages based on config length', function () {
    config()->set('turbo-seeder.max_error_message_length_in_console', 50);

    $longMessage = str_repeat('A', 200).' SQL: INSERT INTO `table` (`col`) VALUES (...)';
    $exception = new RuntimeException($longMessage);

    $formatted = ExceptionFormatter::format($exception);

    expect(strlen($formatted))->toBeGreaterThan(50)
        ->and($formatted)->toEndWith('... (truncated)');
});

test('exception formatter respects default length when config not set', function () {
    config()->set('turbo-seeder.max_error_message_length_in_console', null);

    $longMessage = str_repeat('B', 700);
    $exception = new RuntimeException($longMessage);

    $formatted = ExceptionFormatter::format($exception);

    expect(strlen($formatted))->toBeGreaterThan(600)
        ->and($formatted)->toEndWith('... (truncated)');
});

