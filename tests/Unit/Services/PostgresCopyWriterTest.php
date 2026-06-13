<?php

declare(strict_types=1);

use IzAhmad\TurboSeeder\Services\PostgresCopyWriter;

function copyWriter(): PostgresCopyWriter
{
    return new PostgresCopyWriter(sys_get_temp_dir().'/turbo-copy-test.txt');
}

test('formats a plain record as tab-delimited line', function () {
    $line = copyWriter()->formatLine(
        ['name' => 'Alice', 'age' => 30],
        ['name', 'age'],
    );

    expect($line)->toBe("Alice\t30\n");
});

test('encodes null as the COPY null sentinel', function () {
    $line = copyWriter()->formatLine(
        ['name' => 'Bob', 'age' => null],
        ['name', 'age'],
    );

    expect($line)->toBe("Bob\t\\N\n");
});

test('escapes backslash, tab, newline and carriage return', function () {
    $line = copyWriter()->formatLine(
        ['v' => "a\\b\tc\nd\re"],
        ['v'],
    );

    expect($line)->toBe("a\\\\b\\tc\\nd\\re\n");
});

test('a literal backslash-N value stays distinct from the null sentinel', function () {
    $line = copyWriter()->formatLine(
        ['v' => '\N'],
        ['v'],
    );

    // Real value "\N" becomes "\\N"; the NULL sentinel is the unescaped "\N".
    expect($line)->toBe("\\\\N\n")
        ->and($line)->not->toBe("\\N\n");
});

test('encodes booleans and arrays via ValueFormatter', function () {
    $line = copyWriter()->formatLine(
        ['flag' => true, 'data' => ['k' => 'v']],
        ['flag', 'data'],
    );

    expect($line)->toBe("1\t{\"k\":\"v\"}\n");
});
