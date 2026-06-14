<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Enums\DatabaseDriver;
use IzAhmad\TurboSeeder\Services\SqlIdentifier;

test('quoteTable wraps MySQL identifier in backticks', function () {
    expect(SqlIdentifier::quoteTable('users', DatabaseDriver::MYSQL))->toBe('`users`');
});

test('quoteTable wraps PostgreSQL identifier in double quotes', function () {
    expect(SqlIdentifier::quoteTable('users', DatabaseDriver::PGSQL))->toBe('"users"');
});

test('quoteTable wraps SQLite identifier in double quotes', function () {
    expect(SqlIdentifier::quoteTable('users', DatabaseDriver::SQLITE))->toBe('"users"');
});

test('quoteTable handles schema prefix for MySQL', function () {
    expect(SqlIdentifier::quoteTable('mydb.users', DatabaseDriver::MYSQL))->toBe('`mydb`.`users`');
});

test('quoteTable handles schema prefix for PostgreSQL', function () {
    expect(SqlIdentifier::quoteTable('public.users', DatabaseDriver::PGSQL))->toBe('"public"."users"');
});

test('quoteTable escapes embedded backtick in MySQL identifier', function () {
    expect(SqlIdentifier::quoteTable('use`rs', DatabaseDriver::MYSQL))->toBe('`use``rs`');
});

test('quoteTable escapes embedded double-quote in PostgreSQL identifier', function () {
    expect(SqlIdentifier::quoteTable('use"rs', DatabaseDriver::PGSQL))->toBe('"use""rs"');
});

test('quoteTable escapes embedded double-quote in SQLite identifier', function () {
    expect(SqlIdentifier::quoteTable('use"rs', DatabaseDriver::SQLITE))->toBe('"use""rs"');
});
