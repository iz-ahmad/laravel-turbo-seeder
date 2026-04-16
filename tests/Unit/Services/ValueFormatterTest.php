<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Support\Collection;
use IzAhmad\TurboSeeder\Services\ValueFormatter;

// ── format() ─────────────────────────────────────────────────────────────────

test('format returns null as null', function () {
    expect(ValueFormatter::format(null))->toBeNull();
});

test('format returns bool true as int 1', function () {
    expect(ValueFormatter::format(true))->toBe(1);
});

test('format returns bool false as int 0', function () {
    expect(ValueFormatter::format(false))->toBe(0);
});

test('format returns int unchanged', function () {
    expect(ValueFormatter::format(42))->toBe(42);
});

test('format returns float unchanged', function () {
    expect(ValueFormatter::format(3.14))->toBe(3.14);
});

test('format returns string unchanged', function () {
    expect(ValueFormatter::format('hello'))->toBe('hello');
});

test('format converts DateTimeInterface to Y-m-d H:i:s string', function () {
    $dt = new \DateTime('2024-06-15 10:30:00');
    expect(ValueFormatter::format($dt))->toBe('2024-06-15 10:30:00');
});

test('format converts Carbon to Y-m-d H:i:s string', function () {
    $carbon = Carbon::create(2024, 6, 15, 10, 30, 0);
    expect(ValueFormatter::format($carbon))->toBe('2024-06-15 10:30:00');
});

test('format converts BackedEnum to its value', function () {
    $enum = StatusFixture::Active;
    expect(ValueFormatter::format($enum))->toBe('active');
});

test('format converts integer BackedEnum to its value', function () {
    $enum = PriorityFixture::High;
    expect(ValueFormatter::format($enum))->toBe(1);
});

test('format converts UnitEnum to its name', function () {
    $enum = ColorFixture::Red;
    expect(ValueFormatter::format($enum))->toBe('Red');
});

test('format converts array to JSON string', function () {
    $result = ValueFormatter::format(['a' => 1, 'b' => 2]);
    expect($result)->toBe('{"a":1,"b":2}');
});

test('format converts Illuminate Collection to JSON string', function () {
    $collection = new Collection(['x', 'y', 'z']);
    expect(ValueFormatter::format($collection))->toBe('["x","y","z"]');
});

test('format throws on invalid JSON input', function () {
    $circular = new \stdClass;
    $circular->self = $circular;
    ValueFormatter::format($circular);
})->throws(\JsonException::class);

// ── formatForCsv() ────────────────────────────────────────────────────────────

test('formatForCsv returns null marker for null', function () {
    expect(ValueFormatter::formatForCsv(null))->toBe('\\N');
});

test('formatForCsv uses custom null marker', function () {
    expect(ValueFormatter::formatForCsv(null, '__NULL__'))->toBe('__NULL__');
});

test('formatForCsv returns "1" for bool true', function () {
    expect(ValueFormatter::formatForCsv(true))->toBe('1');
});

test('formatForCsv returns "0" for bool false', function () {
    expect(ValueFormatter::formatForCsv(false))->toBe('0');
});

test('formatForCsv formats datetime as Y-m-d H:i:s', function () {
    $dt = new \DateTime('2024-06-15 10:30:00');
    expect(ValueFormatter::formatForCsv($dt))->toBe('2024-06-15 10:30:00');
});

test('formatForCsv converts BackedEnum to string value', function () {
    $enum = StatusFixture::Active;
    expect(ValueFormatter::formatForCsv($enum))->toBe('active');
});

test('formatForCsv converts array to JSON string', function () {
    $result = ValueFormatter::formatForCsv(['key' => 'val']);
    expect($result)->toBe('{"key":"val"}');
});

// ── extend() ─────────────────────────────────────────────────────────────────

test('can register custom type formatter', function () {
    ValueFormatter::extend(CustomTypeFixture::class, fn ($v) => 'custom:'.$v->value);

    $result = ValueFormatter::format(new CustomTypeFixture('test-value'));

    expect($result)->toBe('custom:test-value');
});

// ── Fixtures ──────────────────────────────────────────────────────────────────

enum StatusFixture: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

enum PriorityFixture: int
{
    case High = 1;
    case Low = 2;
}

enum ColorFixture
{
    case Red;
    case Blue;
}

class CustomTypeFixture
{
    public function __construct(public readonly string $value) {}
}
