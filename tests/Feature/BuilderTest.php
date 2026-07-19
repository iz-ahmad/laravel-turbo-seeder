<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Builder\TurboSeederBuilder;
use IzAhmad\TurboSeeder\Enums\SeederStrategy;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;
use IzAhmad\TurboSeeder\Tests\Fixtures\TestUserModel;
use IzAhmad\TurboSeeder\Tests\Fixtures\TestUserOnSecondaryConnectionModel;

test('builder validates that table name is set', function () {
    app(TurboSeederBuilder::class)->run();
})->throws(InvalidArgumentException::class, 'Table name is required');

test('builder requires a generator', function () {
    TurboSeeder::forTable('test_users')->run();
})->throws(InvalidArgumentException::class, 'Data generator is required');

test('builder requires a generator even when columns are set', function () {
    TurboSeeder::forTable('test_users')
        ->columns(['name'])
        ->run();
})->throws(InvalidArgumentException::class, 'Data generator is required');

test('columns are inferred from generator when not set explicitly', function () {
    $result = TurboSeeder::forTable('test_users')
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@infer.test"])
        ->count(5)
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(5);
});

test('inferred columns are available via getColumns after run', function () {
    $builder = TurboSeeder::forTable('test_users')
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@infer2.test"])
        ->count(1);

    $builder->run();

    expect($builder->getColumns())->toBe(['name', 'email']);
});

test('inferred columns are available via toConfiguration', function () {
    $config = TurboSeeder::forTable('test_users')
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@inferconf.test"])
        ->count(1)
        ->toConfiguration();

    expect($config->columns)->toBe(['name', 'email']);
});

test('builder validates count', function () {
    TurboSeeder::forTable('test_users')
        ->columns(['name'])
        ->generate(fn ($i) => ['name' => "User {$i}"])
        ->count(0)
        ->run();
})->throws(InvalidArgumentException::class, 'Count must be at least 1');

test('can chain methods fluently', function () {
    $builder = TurboSeeder::forTable('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(10)
        ->chunkSize(5)
        ->withoutProgressTracking();

    expect($builder)->toBeInstanceOf(TurboSeederBuilder::class);
});

test('can use when condition', function () {
    $useCsv = false;

    $builder = TurboSeeder::forTable('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(10)
        ->when($useCsv, fn ($b) => $b->useCsvStrategy());

    expect($builder->getStrategy())->toBe(SeederStrategy::DEFAULT);
});

test('can use unless condition', function () {
    $isProduction = false;

    $builder = TurboSeeder::forTable('test_users')
        ->columns(['name'])
        ->generate(fn ($i) => ['name' => "User {$i}"])
        ->count(10)
        ->unless($isProduction, fn ($b) => $b->withProgressTracking());

    expect($builder->getOptions())->toHaveKey('progress_tracking');
});

test('can get configuration without executing', function () {
    $config = TurboSeeder::forTable('test_users')
        ->columns(['name'])
        ->generate(fn ($i) => ['name' => "User {$i}"])
        ->count(100)
        ->toConfiguration();

    expect($config->table)->toBe('test_users')
        ->and($config->count)->toBe(100);
});

test('builder getters return correct values', function () {
    $builder = TurboSeeder::forTable('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(500)
        ->chunkSize(100);

    expect($builder->getTable())->toBe('test_users')
        ->and($builder->getColumns())->toBe(['name', 'email'])
        ->and($builder->getCount())->toBe(500)
        ->and($builder->getStrategy())->toBe(SeederStrategy::DEFAULT)
        ->and($builder->getOptions())->toHaveKey('chunk_size')
        ->and($builder->getOptions()['chunk_size'])->toBe(100);
});

test('table() accepts schema-qualified names', function () {
    $builder = TurboSeeder::forTable('public.test_users')
        ->generate(fn ($i) => ['name' => "User {$i}"])
        ->count(1);

    expect($builder->getTable())->toBe('public.test_users');
});

test('table() rejects invalid table names', function (string $invalid) {
    TurboSeeder::forTable($invalid);
})->with([
    '123abc',
    'a.b.c',
    '.users',
    'users.',
    'my-table',
    '',
])->throws(InvalidArgumentException::class, 'Invalid table name');

test('generator-inferred column names are validated', function () {
    TurboSeeder::forTable('test_users')
        ->generate(fn ($i) => ['valid_col' => $i, 'invalid-col' => $i])
        ->count(1)
        ->run();
})->throws(InvalidArgumentException::class, 'Invalid column name [invalid-col] inferred from generator');

test('table() resolves the table name from a Model class-string', function () {
    $builder = TurboSeeder::forTable(TestUserModel::class)
        ->generate(fn ($i) => ['name' => "User {$i}"])
        ->count(1);

    expect($builder->getTable())->toBe('test_users');
});

test('table() resolves the table name from a Model instance', function () {
    $builder = TurboSeeder::forTable(new TestUserModel)
        ->generate(fn ($i) => ['name' => "User {$i}"])
        ->count(1);

    expect($builder->getTable())->toBe('test_users');
});

test('forTable() with a Model class seeds successfully', function () {
    $result = TurboSeeder::forTable(TestUserModel::class)
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@model.test"])
        ->count(5)
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(5);
});

test('table() with a Model class auto-applies the model\'s connection', function () {
    $config = TurboSeeder::forTable(TestUserOnSecondaryConnectionModel::class)
        ->generate(fn ($i) => ['name' => "User {$i}"])
        ->count(1)
        ->toConfiguration();

    expect($config->connection)->toBe('testing_secondary');
});

test('explicit connection() called after table(Model) overrides the model\'s connection', function () {
    $config = TurboSeeder::forTable(TestUserOnSecondaryConnectionModel::class)
        ->connection('testing')
        ->generate(fn ($i) => ['name' => "User {$i}"])
        ->count(1)
        ->toConfiguration();

    expect($config->connection)->toBe('testing');
});

test('explicit connection() called before table(Model) still overrides the model\'s connection', function () {
    $builder = TurboSeeder::forTable('test_users')
        ->connection('testing')
        ->generate(fn ($i) => ['name' => "User {$i}"])
        ->count(1);

    $config = $builder->table(TestUserOnSecondaryConnectionModel::class)->toConfiguration();

    expect($config->connection)->toBe('testing');
});

test('table() rejects a class that is not an Eloquent model', function () {
    TurboSeeder::forTable(TurboData::class);
})->throws(InvalidArgumentException::class, 'is not an Eloquent model');
