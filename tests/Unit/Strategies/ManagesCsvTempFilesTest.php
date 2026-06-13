<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Strategies\Concerns\ManagesCsvTempFiles;

function csvPathGuard(): object
{
    return new class
    {
        use ManagesCsvTempFiles;

        public function check(string $path): string
        {
            return $this->assertSafeCsvPath($path);
        }
    };
}

test('accepts a normal absolute temp path', function () {
    $path = '/tmp/turbo-seeder/test_users_ab12cd34.csv';

    expect(csvPathGuard()->check($path))->toBe($path);
});

test('accepts a windows-style path', function () {
    $path = 'C:\\Temp\\turbo-seeder\\users_ab12.csv';

    expect(csvPathGuard()->check($path))->toBe($path);
});

test('rejects a path containing a single quote', function () {
    csvPathGuard()->check("/tmp/evil'; DROP TABLE users--.csv");
})->throws(RuntimeException::class, 'unexpected characters');

test('rejects a path containing a newline', function () {
    csvPathGuard()->check("/tmp/file\n.csv");
})->throws(RuntimeException::class);
