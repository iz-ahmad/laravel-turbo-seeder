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

test('quoteTable applies the connection prefix to the table name', function () {
    expect(SqlIdentifier::quoteTable('users', DatabaseDriver::MYSQL, 'app_'))->toBe('`app_users`')
        ->and(SqlIdentifier::quoteTable('users', DatabaseDriver::PGSQL, 'app_'))->toBe('"app_users"');
});

test('quoteTable applies the prefix after the schema qualifier', function () {
    expect(SqlIdentifier::quoteTable('mydb.users', DatabaseDriver::MYSQL, 'app_'))->toBe('`mydb`.`app_users`')
        ->and(SqlIdentifier::quoteTable('public.users', DatabaseDriver::PGSQL, 'app_'))->toBe('"public"."app_users"');
});

test('quoteTable accepts an empty prefix and alphanumeric underscore prefixes', function () {
    expect(SqlIdentifier::quoteTable('users', DatabaseDriver::MYSQL, ''))->toBe('`users`')
        ->and(SqlIdentifier::quoteTable('users', DatabaseDriver::MYSQL, 'App_01_'))->toBe('`App_01_users`');
});

test('quoteTable rejects an unsafe prefix', function (string $prefix) {
    SqlIdentifier::quoteTable('users', DatabaseDriver::MYSQL, $prefix);
})->with([
    'backtick' => 'a`b_',
    'double quote' => 'a"b_',
    'semicolon' => 'a;b_',
    'space' => 'a b_',
    'dash' => 'a-b_',
    'dot' => 'a.b_',
    'newline' => "a\nb_",
])->throws(InvalidArgumentException::class, 'Invalid table prefix');

test('assertSafePrefix rejects a trailing newline', function () {
    SqlIdentifier::assertSafePrefix("pfx_\n");
})->throws(InvalidArgumentException::class);
