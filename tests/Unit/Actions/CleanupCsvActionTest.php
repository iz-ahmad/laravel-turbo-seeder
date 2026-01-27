<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Actions\CleanupCsvAction;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/turbo-seeder-test-'.uniqid();
    mkdir($this->tempDir, 0755, true);
    $this->tempFile = $this->tempDir.'/test.csv';
    file_put_contents($this->tempFile, 'test data');
});

afterEach(function () {
    if (is_dir($this->tempDir)) {
        array_map('unlink', glob($this->tempDir.'/*'));
        rmdir($this->tempDir);
    }
});

test('can cleanup single csv file', function () {
    $action = new CleanupCsvAction;
    $action($this->tempFile);

    expect(file_exists($this->tempFile))->toBeFalse();
});

test('can cleanup multiple csv files', function () {
    $file1 = $this->tempDir.'/file1.csv';
    $file2 = $this->tempDir.'/file2.csv';
    file_put_contents($file1, 'data1');
    file_put_contents($file2, 'data2');

    $action = new CleanupCsvAction;
    $action->cleanupMultiple([$file1, $file2]);

    expect(file_exists($file1))->toBeFalse()
        ->and(file_exists($file2))->toBeFalse();
});

test('can cleanup directory with pattern', function () {
    file_put_contents($this->tempDir.'/test1.csv', 'data1');
    file_put_contents($this->tempDir.'/test2.csv', 'data2');
    file_put_contents($this->tempDir.'/other.txt', 'data3');

    $action = new CleanupCsvAction;
    $deleted = $action->cleanupDirectory($this->tempDir, '*.csv');

    expect($deleted)->toBe(3)
        ->and(file_exists($this->tempDir.'/other.txt'))->toBeTrue();
});

test('handles non-existent file gracefully', function () {
    $action = new CleanupCsvAction;
    $action('/nonexistent/file.csv');

    expect(true)->toBeTrue();
});
