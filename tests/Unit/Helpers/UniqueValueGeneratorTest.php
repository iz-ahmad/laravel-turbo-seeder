<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Helpers\UniqueValueGenerator;

test('uniqueEmail returns a closure', function () {
    $generator = UniqueValueGenerator::uniqueEmail();

    expect($generator)->toBeInstanceOf(\Closure::class);
});

test('uniqueEmail generates email with default prefix', function () {
    $generator = UniqueValueGenerator::uniqueEmail();
    $email = $generator(0);

    expect($email)
        ->toStartWith('user')
        ->toEndWith('@test.com')
        ->toContain('_');
});

test('uniqueEmail generates email with custom prefix', function () {
    $generator = UniqueValueGenerator::uniqueEmail('custom');
    $email = $generator(0);

    expect($email)
        ->toStartWith('custom')
        ->toEndWith('@test.com');
});

test('uniqueEmail generates different emails for different indices', function () {
    $generator = UniqueValueGenerator::uniqueEmail();
    $email1 = $generator(0);
    $email2 = $generator(1);

    expect($email1)->not->toBe($email2);
});

test('uniqueEmail format contains prefix index timestamp and random', function () {
    $generator = UniqueValueGenerator::uniqueEmail('test');
    $email = $generator(42);

    expect($email)->toMatch('/^test42_\d+_[a-zA-Z0-9]{4}@test\.com$/');
});

test('uniqueEmail with null prefix uses default', function () {
    $generator = UniqueValueGenerator::uniqueEmail(null);
    $email = $generator(0);

    expect($email)->toStartWith('user');
});

test('uniqueValue returns a closure', function () {
    $generator = UniqueValueGenerator::uniqueValue();

    expect($generator)->toBeInstanceOf(\Closure::class);
});

test('uniqueValue generates value with default prefix', function () {
    $generator = UniqueValueGenerator::uniqueValue();
    $value = $generator(0);

    expect($value)
        ->toStartWith('unique_')
        ->toContain('_');
});

test('uniqueValue generates value with custom prefix', function () {
    $generator = UniqueValueGenerator::uniqueValue('custom');
    $value = $generator(0);

    expect($value)->toStartWith('custom_');
});

test('uniqueValue generates different values for different indices', function () {
    $generator = UniqueValueGenerator::uniqueValue();
    $value1 = $generator(0);
    $value2 = $generator(1);

    expect($value1)->not->toBe($value2);
});

test('uniqueValue format contains prefix index timestamp and random', function () {
    $generator = UniqueValueGenerator::uniqueValue('test');
    $value = $generator(99);

    expect($value)->toMatch('/^test_99_\d+_[a-zA-Z0-9]{4}$/');
});

test('uniqueValue with null prefix uses default', function () {
    $generator = UniqueValueGenerator::uniqueValue(null);
    $value = $generator(0);

    expect($value)->toStartWith('unique_');
});

test('uniqueUuid returns a closure', function () {
    $generator = UniqueValueGenerator::uniqueUuid();

    expect($generator)->toBeInstanceOf(\Closure::class);
});

test('uniqueUuid generates UUID without prefix', function () {
    $generator = UniqueValueGenerator::uniqueUuid();
    $uuid = $generator();

    expect($uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

test('uniqueUuid generates UUID with prefix', function () {
    $generator = UniqueValueGenerator::uniqueUuid('prefix_');
    $uuid = $generator();

    expect($uuid)
        ->toStartWith('prefix_')
        ->toMatch('/^prefix_[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

test('uniqueEmail and uniqueValue produce different formats', function () {
    $emailGen = UniqueValueGenerator::uniqueEmail('test');
    $valueGen = UniqueValueGenerator::uniqueValue('test');

    $email = $emailGen(0);
    $value = $valueGen(0);

    expect($email)->toEndWith('@test.com')
        ->and($value)->not->toEndWith('@test.com')
        ->and($email)->not->toBe($value);
});
