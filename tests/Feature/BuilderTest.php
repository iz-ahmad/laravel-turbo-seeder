<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Builder\TurboSeederBuilder;
use IzAhmad\TurboSeeder\Enums\SeederStrategy;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

test('builder validates required fields', function () {
    TurboSeeder::create()->run();
})->throws(InvalidArgumentException::class, 'Table name is required');

test('builder requires a generator', function () {
    TurboSeeder::create('test_users')->run();
})->throws(InvalidArgumentException::class, 'Data generator is required');

test('builder requires a generator even when columns are set', function () {
    TurboSeeder::create('test_users')
        ->columns(['name'])
        ->run();
})->throws(InvalidArgumentException::class, 'Data generator is required');

test('columns are inferred from generator when not set explicitly', function () {
    $result = TurboSeeder::create('test_users')
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@infer.test"])
        ->count(5)
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(5);
});

test('inferred columns are available via getColumns after run', function () {
    $builder = TurboSeeder::create('test_users')
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@infer2.test"])
        ->count(1);

    $builder->run();

    expect($builder->getColumns())->toBe(['name', 'email']);
});

test('inferred columns are available via toConfiguration', function () {
    $config = TurboSeeder::create('test_users')
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@inferconf.test"])
        ->count(1)
        ->toConfiguration();

    expect($config->columns)->toBe(['name', 'email']);
});

test('builder validates count', function () {
    TurboSeeder::create('test_users')
        ->columns(['name'])
        ->generate(fn ($i) => ['name' => "User {$i}"])
        ->count(0)
        ->run();
})->throws(InvalidArgumentException::class, 'Count must be at least 1');

test('can chain methods fluently', function () {
    $builder = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(10)
        ->chunkSize(5)
        ->withoutProgressTracking();

    expect($builder)->toBeInstanceOf(TurboSeederBuilder::class);
});

test('can use when condition', function () {
    $useCsv = false;

    $builder = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"])
        ->count(10)
        ->when($useCsv, fn ($b) => $b->useCsvStrategy());

    expect($builder->getStrategy())->toBe(SeederStrategy::DEFAULT);
});

test('can use unless condition', function () {
    $isProduction = false;

    $builder = TurboSeeder::create('test_users')
        ->columns(['name'])
        ->generate(fn ($i) => ['name' => "User {$i}"])
        ->count(10)
        ->unless($isProduction, fn ($b) => $b->withProgressTracking());

    expect($builder->getOptions())->toHaveKey('progress_tracking');
});

test('can get configuration without executing', function () {
    $config = TurboSeeder::create('test_users')
        ->columns(['name'])
        ->generate(fn ($i) => ['name' => "User {$i}"])
        ->count(100)
        ->toConfiguration();

    expect($config->table)->toBe('test_users')
        ->and($config->count)->toBe(100);
});

test('builder getters return correct values', function () {
    $builder = TurboSeeder::create('test_users')
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

test('batch generator can be used', function () {
    $result = TurboSeeder::create('test_users')
        ->generateBatch(function (int $start, int $size) {
            $records = [];
            for ($i = 0; $i < $size; $i++) {
                $records[] = [
                    'name' => "User ".($start + $i),
                    'email' => "user".($start + $i)."@batch.test",
                ];
            }

            return $records;
        })
        ->count(100)
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(100);
});

test('batch generator columns are inferred correctly', function () {
    $builder = TurboSeeder::create('test_users')
        ->generateBatch(function (int $start, int $size) {
            return [
                ['name' => "User {$start}", 'email' => "u{$start}@batch.test"],
            ];
        })
        ->count(1);

    $builder->run();

    expect($builder->getColumns())->toBe(['name', 'email']);
});

test('cannot use both generate and generateBatch', function () {
    TurboSeeder::create('test_users')
        ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@both.test"])
        ->generateBatch(fn ($start, $size) => [['name' => 'User', 'email' => 'u@both.test']])
        ->count(1)
        ->run();
})->throws(InvalidArgumentException::class, 'Cannot use both generate() and generateBatch()');

test('generateBatch requires a batch generator', function () {
    TurboSeeder::create('test_users')->run();
})->throws(InvalidArgumentException::class, 'Data generator is required');

test('batch generator reduces closure call overhead', function () {
    $singleCallCount = 0;
    $batchCallCount = 0;
    $timestamp = time();

    $singleResult = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(function ($i) use (&$singleCallCount, $timestamp) {
            $singleCallCount++;

            return ['name' => "User {$i}", 'email' => "u{$i}_{$timestamp}@overhead.test"];
        })
        ->count(50)
        ->withoutProgressTracking()
        ->run();

    $batchResult = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generateBatch(function ($start, $size) use (&$batchCallCount, $timestamp) {
            $batchCallCount++;

            $records = [];
            for ($i = 0; $i < $size; $i++) {
                $records[] = ['name' => "User ".($start + $i), 'email' => "b".($start + $i)."_{$timestamp}@overhead.test"];
            }

            return $records;
        })
        ->count(50)
        ->withoutProgressTracking()
        ->run();

    expect($singleResult->recordsInserted)->toBe(50)
        ->and($batchResult->recordsInserted)->toBe(50)
        ->and($singleCallCount)->toBe(50)
        ->and($batchCallCount)->toBeLessThan($singleCallCount);
});
