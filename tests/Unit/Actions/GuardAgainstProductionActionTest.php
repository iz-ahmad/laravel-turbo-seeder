<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Actions\GuardAgainstProductionAction;
use IzAhmad\TurboSeeder\DTOs\SeederConfigurationDTO;
use IzAhmad\TurboSeeder\Exceptions\ProductionEnvironmentException;

function guardTestConfig(array $options = []): SeederConfigurationDTO
{
    return new SeederConfigurationDTO(
        table: 'test_users',
        columns: ['name'],
        generator: fn ($i) => ['name' => "User {$i}"],
        count: 1,
        connection: 'testing',
        options: $options,
    );
}

test('allows running in local/testing environments without force', function () {
    $action = new GuardAgainstProductionAction;

    $action(guardTestConfig());
})->throwsNoExceptions();

test('throws outside local/testing without force or prior confirmation', function () {
    $original = app()->environment();
    app()->instance('env', 'production');

    $action = new GuardAgainstProductionAction;

    try {
        expect(fn () => $action(guardTestConfig()))->toThrow(ProductionEnvironmentException::class);
    } finally {
        app()->instance('env', $original);
    }
});

test('isForced() bypasses the guard outside local/testing', function () {
    $original = app()->environment();
    app()->instance('env', 'production');

    $action = new GuardAgainstProductionAction;

    try {
        $action(guardTestConfig(['force' => true]));
    } finally {
        app()->instance('env', $original);
    }
})->throwsNoExceptions();

test('the CONFIRMED_BINDING container marker bypasses the guard outside local/testing', function () {
    $original = app()->environment();
    app()->instance('env', 'production');
    app()->instance(GuardAgainstProductionAction::CONFIRMED_BINDING, true);

    $action = new GuardAgainstProductionAction;

    try {
        $action(guardTestConfig());
    } finally {
        app()->instance('env', $original);
        app()->forgetInstance(GuardAgainstProductionAction::CONFIRMED_BINDING);
    }
})->throwsNoExceptions();
