<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Services\CsvWriter;

beforeEach(function () {
    $this->tempFile = sys_get_temp_dir().'/test_'.uniqid().'.csv';
});

afterEach(function () {
    if (file_exists($this->tempFile)) {
        unlink($this->tempFile);
    }
});

test('can write single row to csv', function () {
    $writer = new CsvWriter($this->tempFile);
    $writer->open();
    $writer->writeRow(['John Doe', 'john@example.com']);
    $writer->close();

    expect(file_exists($this->tempFile))->toBeTrue()
        ->and(file_get_contents($this->tempFile))->toContain('John Doe');
});

test('can write multiple rows', function () {
    $writer = new CsvWriter($this->tempFile);
    $writer->open();
    $writer->writeRow(['User 1', 'user1@test.com']);
    $writer->writeRow(['User 2', 'user2@test.com']);
    $writer->close();

    $content = file_get_contents($this->tempFile);
    expect($content)->toContain('User 1')
        ->and($content)->toContain('User 2');
});

test('tracks rows written', function () {
    $writer = new CsvWriter($this->tempFile);
    $writer->open();
    $writer->writeRow(['Row 1']);
    $writer->writeRow(['Row 2']);
    $writer->writeRow(['Row 3']);
    $writer->close();

    expect($writer->getRowsWritten())->toBe(3);
});

test('creates directory if not exists', function () {
    $dir = sys_get_temp_dir().'/turbo-test-'.uniqid();
    $file = $dir.'/test.csv';

    $writer = new CsvWriter($file);
    $writer->open();
    $writer->close();

    expect(is_dir($dir))->toBeTrue();

    unlink($file);
    rmdir($dir);
});

test('throws exception when file cannot be opened', function () {
    $writer = new CsvWriter('/invalid/path/file.csv');

    $writer->open();
})->throws(RuntimeException::class);

test('returns correct file path', function () {
    $writer = new CsvWriter($this->tempFile);

    expect($writer->getFilePath())->toBe($this->tempFile);
});
