<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\DTOs\DatabaseConnectionDTO;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\Enums\DatabaseDriver;
use IzAhmad\TurboSeeder\Enums\SeederStrategy;
use IzAhmad\TurboSeeder\Services\StrategyResolver;
use IzAhmad\TurboSeeder\Strategies\MySqlSeederStrategy;

test('can register strategy', function () {
    $resolver = new StrategyResolver;
    $resolver->register('test.mysql', MySqlSeederStrategy::class);

    expect($resolver->hasStrategy('test.mysql'))->toBeTrue();
});

test('throws exception when registering invalid strategy class', function () {
    $resolver = new StrategyResolver;
    $resolver->register('test.invalid', \stdClass::class);
})->throws(InvalidArgumentException::class, 'must implement SeederStrategyInterface');

test('can check if strategy exists', function () {
    $resolver = new StrategyResolver;
    $resolver->register('test.mysql', MySqlSeederStrategy::class);

    expect($resolver->hasStrategy('test.mysql'))->toBeTrue()
        ->and($resolver->hasStrategy('test.nonexistent'))->toBeFalse();
});

test('can get all registered strategies', function () {
    $resolver = new StrategyResolver;
    $resolver->register('test.mysql', MySqlSeederStrategy::class);
    $resolver->register('test.pgsql', MySqlSeederStrategy::class);

    $strategies = $resolver->getStrategies();

    expect($strategies)->toHaveCount(2)
        ->and($strategies)->toHaveKey('test.mysql')
        ->and($strategies)->toHaveKey('test.pgsql');
});
