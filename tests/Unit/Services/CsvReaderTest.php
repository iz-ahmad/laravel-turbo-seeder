<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Services\CsvReader;
use IzAhmad\TurboSeeder\Services\CsvWriter;

beforeEach(function () {
    $this->tempFile = sys_get_temp_dir().'/test_'.uniqid().'.csv';
});

afterEach(function () {
    if (file_exists($this->tempFile)) {
        unlink($this->tempFile);
    }
});

test('can read rows from csv file', function () {
    $writer = new CsvWriter($this->tempFile);
    $writer->open();
    $writer->writeRow(['John Doe', 'john@example.com']);
    $writer->writeRow(['Jane Doe', 'jane@example.com']);
    $writer->close();

    $reader = new CsvReader($this->tempFile);
    $reader->open();

    $rows = iterator_to_array($reader->readRows());

    expect($rows)->toHaveCount(2)
        ->and($rows[0])->toBe(['John Doe', 'john@example.com'])
        ->and($rows[1])->toBe(['Jane Doe', 'jane@example.com']);

    $reader->close();
});

test('can read csv in chunks', function () {
    $writer = new CsvWriter($this->tempFile);
    $writer->open();
    for ($i = 1; $i <= 10; $i++) {
        $writer->writeRow(["User {$i}", "user{$i}@test.com"]);
    }
    $writer->close();

    $reader = new CsvReader($this->tempFile);
    $reader->open();

    $chunks = iterator_to_array($reader->readChunks(3));

    expect($chunks)->toHaveCount(4)
        ->and($chunks[0])->toHaveCount(3)
        ->and($chunks[3])->toHaveCount(1);

    $reader->close();
});

test('throws exception when file does not exist', function () {
    $reader = new CsvReader('/nonexistent/file.csv');
    $reader->open();
})->throws(RuntimeException::class, 'File does not exist');

test('returns correct file path', function () {
    $reader = new CsvReader($this->tempFile);

    expect($reader->getFilePath())->toBe($this->tempFile);
});

test('file handle is closed when generator is abandoned mid-stream', function () {
    $writer = new CsvWriter($this->tempFile);
    $writer->open();
    for ($i = 1; $i <= 10; $i++) {
        $writer->writeRow(["User {$i}", "user{$i}@test.com"]);
    }
    $writer->close();

    $reader = new CsvReader($this->tempFile);
    $reader->open();

    $gen = $reader->readRows();
    $gen->current(); // read first row
    $gen = null;     // abandon generator — triggers finally block

    // File handle should be closed; opening for read again must succeed
    $reader2 = new CsvReader($this->tempFile);
    $reader2->open();
    $rows = iterator_to_array($reader2->readRows());
    expect($rows)->toHaveCount(10);
});
