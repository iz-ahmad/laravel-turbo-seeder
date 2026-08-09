<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

test('seeding throws with a clear message when the table does not exist', function () {
    expect(fn () => TurboSeeder::forTable('nonexistent_table')
        ->columns(['name'])
        ->generate(fn ($i) => ['name' => "User {$i}"])
        ->count(1)
        ->run())
        ->toThrow(RuntimeException::class, 'nonexistent_table');
});

test('seeding throws when a declared column does not exist on the table', function () {
    expect(fn () => TurboSeeder::forTable('test_users')
        ->columns(['name', 'email', 'nonexistent_column'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@col.test", 'nonexistent_column' => 'x'])
        ->count(5)
        ->run())
        ->toThrow(RuntimeException::class, 'nonexistent_column');
});

test('schema validation error message lists available columns', function () {
    $errorMessage = null;

    try {
        TurboSeeder::forTable('test_users')
            ->columns(['name', 'missing_col'])
            ->generate(fn ($i) => ['name' => "User {$i}", 'missing_col' => 'x'])
            ->count(1)
            ->run();
    } catch (RuntimeException $e) {
        $errorMessage = $e->getMessage();
    }

    expect($errorMessage)->toContain('missing_col')
        ->and($errorMessage)->toContain('test_users');
});

test('withoutColumnValidation skips schema check and fails at the database level instead', function () {
    expect(fn () => TurboSeeder::forTable('test_users')
        ->columns(['name', 'email', 'nonexistent_column'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@skip.test", 'nonexistent_column' => 'x'])
        ->count(3)
        ->withoutColumnValidation()
        ->run())
        ->toThrow(RuntimeException::class);

    // Row count stays 0 because the DB error caused a rollback
    expect(DB::table('test_users')->count())->toBe(0);
});

test('schema validation passes for all valid columns on test_users', function () {
    $result = TurboSeeder::forTable('test_users')
        ->columns(['name', 'email', 'age'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@valid.test", 'age' => 25])
        ->count(5)
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(5);
});

test('column names with invalid characters are rejected', function () {
    expect(fn () => TurboSeeder::forTable('test_users')
        ->columns(['name', 'bad-column'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'bad-column' => 'x']))
        ->toThrow(InvalidArgumentException::class, 'bad-column');
});

test('column names with backticks are rejected', function () {
    expect(fn () => TurboSeeder::forTable('test_users')
        ->columns(['name', '`injection`'])
        ->generate(fn ($i) => ['name' => "User {$i}", '`injection`' => 'x']))
        ->toThrow(InvalidArgumentException::class);
});

test('seeding throws with a friendly message when a NOT NULL non-default column is missing', function () {
    // test_users.name is NOT NULL with no default; omitting it is a guaranteed DB failure
    expect(fn () => TurboSeeder::forTable('test_users')
        ->columns(['email'])
        ->generate(fn ($i) => ['email' => "u{$i}@nn.test"])
        ->count(1)
        ->run()
    )->toThrow(RuntimeException::class, 'name');
});

test('NOT NULL check error message names the missing columns, the table, and the escape hatch', function () {
    $message = null;

    try {
        TurboSeeder::forTable('test_users')
            ->columns(['email'])
            ->generate(fn ($i) => ['email' => "u{$i}@nn2.test"])
            ->count(1)
            ->run();
    } catch (RuntimeException $e) {
        $message = $e->getMessage();
    }

    expect($message)
        ->toContain('name')
        ->toContain('test_users')
        ->toContain('withoutColumnValidation');
});

test('withoutColumnValidation() bypasses the NOT NULL gap check', function () {
    // Without the guard this hits the DB and fails with a constraint error,
    // but still throws RuntimeException — just from the DB, not from our check.
    expect(fn () => TurboSeeder::forTable('test_users')
        ->columns(['email'])
        ->generate(fn ($i) => ['email' => "u{$i}@skip-nn.test"])
        ->count(1)
        ->withoutColumnValidation()
        ->run()
    )->toThrow(RuntimeException::class);
});

test('nullable columns absent from seeded columns do not trigger the NOT NULL check', function () {
    // test_users.age is nullable — omitting it is valid
    $result = TurboSeeder::forTable('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "U{$i}", 'email' => "u{$i}@nn3.test"])
        ->count(3)
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(3);
});

test('NOT NULL generated columns are not flagged as uncovered', function () {
    Schema::create('test_generated', function (Blueprint $table) {
        $table->id();
        $table->integer('base');
        $table->integer('doubled')->storedAs('base * 2')->nullable(false);
    });

    $columns = collect(Schema::getColumns('test_generated'))->firstWhere('name', 'doubled');

    if (($columns['generation'] ?? null) === null || $columns['nullable'] === true) {
        Schema::dropIfExists('test_generated');

        test()->markTestSkipped('Driver does not report a NOT NULL generated column.');
    }

    try {
        $result = TurboSeeder::forTable('test_generated')
            ->columns(['base'])
            ->generate(fn ($i) => ['base' => $i + 1])
            ->count(3)
            ->run();

        expect($result->success)->toBeTrue()
            ->and(DB::table('test_generated')->count())->toBe(3)
            ->and(DB::table('test_generated')->where('base', 1)->value('doubled'))->toBe(2);
    } finally {
        Schema::dropIfExists('test_generated');
    }
});
