<?php

declare(strict_types=1);

use Carbon\Carbon;
use IzAhmad\TurboSeeder\Helpers\TurboData;

beforeEach(function () {
    TurboData::reset();
});

test('reset clears every cached value including date caches', function () {
    TurboData::nowOnce();
    TurboData::dateRange('2020-01-01', '2020-12-31');
    TurboData::sequentialDate('2020-01-01', 'day', 1);

    TurboData::reset();

    expect(TurboData::nowOnce())->toBeString()
        ->and(TurboData::dateRange('2020-01-01', '2020-12-31'))->toBeInstanceOf(Carbon::class)
        ->and(TurboData::sequentialDate('2020-01-01', 'day', 1))->toBeInstanceOf(Carbon::class);
});

// ── cycleFrom() ───────────────────────────────────────────────────────────────

test('cycleFrom returns a closure', function () {
    expect(TurboData::cycleFrom(['a', 'b']))->toBeInstanceOf(Closure::class);
});

test('cycleFrom cycles through values by index', function () {
    $cycle = TurboData::cycleFrom(['x', 'y', 'z']);

    expect($cycle(0))->toBe('x');
    expect($cycle(1))->toBe('y');
    expect($cycle(2))->toBe('z');
    expect($cycle(3))->toBe('x'); // wraps
});

test('cycleFrom works with single value', function () {
    $cycle = TurboData::cycleFrom(['only']);

    expect($cycle(0))->toBe('only');
    expect($cycle(99))->toBe('only');
});

test('cycleFrom throws on empty array', function () {
    TurboData::cycleFrom([]);
})->throws(InvalidArgumentException::class);

// ── weightedFrom() ────────────────────────────────────────────────────────────

test('weightedFrom returns a value from the weights array', function () {
    $result = TurboData::weightedFrom(['active' => 70, 'inactive' => 30]);

    expect($result)->toBeIn(['active', 'inactive']);
});

test('weightedFrom returns the only value when weight is 100%', function () {
    // only 'always' can win - 'never' has weight 0
    for ($i = 0; $i < 100; $i++) {
        $result = TurboData::weightedFrom(['always' => 1, 'never' => 0]);
        expect($result)->toBe('always');
    }
});

test('weightedFrom throws on zero-sum weights', function () {
    TurboData::weightedFrom(['a' => 0, 'b' => 0]);
})->throws(InvalidArgumentException::class);

test('weightedFrom throws on negative weight', function () {
    TurboData::weightedFrom(['a' => 100, 'b' => -40]);
})->throws(InvalidArgumentException::class, "weight for 'b' must be non-negative");

// ── randomFrom() ─────────────────────────────────────────────────────────────

test('randomFrom returns a value from the array', function () {
    $values = ['alpha', 'beta', 'gamma'];
    $result = TurboData::randomFrom($values);

    expect($result)->toBeIn($values);
});

test('randomFrom throws on empty array', function () {
    TurboData::randomFrom([]);
})->throws(InvalidArgumentException::class);

// ── randomInt() ───────────────────────────────────────────────────────────────

test('randomInt returns int within range', function () {
    $result = TurboData::randomInt(5, 10);

    expect($result)->toBeInt()
        ->toBeGreaterThanOrEqual(5)
        ->toBeLessThanOrEqual(10);
});

test('randomInt with equal min and max returns that value', function () {
    expect(TurboData::randomInt(7, 7))->toBe(7);
});

// ── randomFloat() ─────────────────────────────────────────────────────────────

test('randomFloat returns float within range', function () {
    $result = TurboData::randomFloat(2, 1.00, 9.99);

    expect($result)->toBeFloat()
        ->toBeGreaterThanOrEqual(1.00)
        ->toBeLessThanOrEqual(9.99);
});

test('randomFloat respects decimal places', function () {
    for ($i = 0; $i < 20; $i++) {
        $result = TurboData::randomFloat(2, 0.00, 100.00);
        expect(round($result, 2))->toBe($result);
    }
});

test('randomFloat with 0 decimals returns whole number', function () {
    for ($i = 0; $i < 20; $i++) {
        $result = TurboData::randomFloat(0, 1.0, 10.0);
        expect($result)->toBe(round($result, 0));
    }
});

// ── randomBool() ──────────────────────────────────────────────────────────────

test('randomBool returns a bool', function () {
    expect(TurboData::randomBool())->toBeBool();
});

test('randomBool with probability 1.0 always returns true', function () {
    for ($i = 0; $i < 50; $i++) {
        expect(TurboData::randomBool(1.0))->toBeTrue();
    }
});

test('randomBool with probability 0.0 always returns false', function () {
    for ($i = 0; $i < 50; $i++) {
        expect(TurboData::randomBool(0.0))->toBeFalse();
    }
});

// ── nullable() ────────────────────────────────────────────────────────────────

test('nullable with probability 0 never returns null', function () {
    for ($i = 0; $i < 50; $i++) {
        expect(TurboData::nullable(0.0, 'value'))->toBe('value');
    }
});

test('nullable with probability 1 always returns null', function () {
    for ($i = 0; $i < 50; $i++) {
        expect(TurboData::nullable(1.0, 'value'))->toBeNull();
    }
});

test('nullable accepts callable value and only evaluates when not null', function () {
    $called = 0;
    $callable = function () use (&$called) {
        $called++;

        return 'computed';
    };

    // probability = 0 → never null → callable always evaluated
    for ($i = 0; $i < 10; $i++) {
        TurboData::nullable(0.0, $callable);
    }

    expect($called)->toBe(10);
});

test('nullable does not evaluate callable when null is returned', function () {
    $called = 0;
    $callable = function () use (&$called) {
        $called++;

        return 'computed';
    };

    // probability = 1 → always null → callable never evaluated
    for ($i = 0; $i < 10; $i++) {
        TurboData::nullable(1.0, $callable);
    }

    expect($called)->toBe(0);
});

// ── dateRange() ───────────────────────────────────────────────────────────────

test('dateRange returns a Carbon instance', function () {
    $result = TurboData::dateRange('2023-01-01', '2023-12-31');

    expect($result)->toBeInstanceOf(Carbon::class);
});

test('dateRange returns date within specified range', function () {
    $from = Carbon::parse('2023-01-01');
    $to = Carbon::parse('2023-12-31');

    for ($i = 0; $i < 20; $i++) {
        $result = TurboData::dateRange('2023-01-01', '2023-12-31');

        expect($result->timestamp)->toBeGreaterThanOrEqual($from->timestamp)
            ->toBeLessThanOrEqual($to->timestamp);
    }
});

test('dateRange accepts same from and to date', function () {
    $result = TurboData::dateRange('2024-06-15', '2024-06-15');

    expect($result->toDateString())->toBe('2024-06-15');
});

test('dateRange throws when from is after to', function () {
    TurboData::dateRange('2024-12-31', '2024-01-01');
})->throws(InvalidArgumentException::class);

// ── sequentialDate() ──────────────────────────────────────────────────────────

test('sequentialDate returns a Carbon instance', function () {
    expect(TurboData::sequentialDate('2024-01-01', 'day', 0))->toBeInstanceOf(Carbon::class);
});

test('sequentialDate index 0 returns the start date unchanged', function () {
    $result = TurboData::sequentialDate('2024-06-15', 'day', 0);

    expect($result->toDateString())->toBe('2024-06-15');
});

test('sequentialDate increments by day', function () {
    $d0 = TurboData::sequentialDate('2024-01-01', 'day', 0);
    $d1 = TurboData::sequentialDate('2024-01-01', 'day', 1);
    $d7 = TurboData::sequentialDate('2024-01-01', 'day', 7);

    expect($d0->toDateString())->toBe('2024-01-01');
    expect($d1->toDateString())->toBe('2024-01-02');
    expect($d7->toDateString())->toBe('2024-01-08');
});

test('sequentialDate returns independent copies so calls do not mutate each other', function () {
    $d1 = TurboData::sequentialDate('2024-01-01', 'day', 1);
    $d2 = TurboData::sequentialDate('2024-01-01', 'day', 2);

    // If the cached start were mutated, d1 would reflect d2's offset
    expect($d1->toDateString())->toBe('2024-01-02');
    expect($d2->toDateString())->toBe('2024-01-03');
});

// ── nowOnce() ────────────────────────────────────────────────────────────────

test('nowOnce returns a string', function () {
    expect(TurboData::nowOnce())->toBeString();
});

test('nowOnce returns the same value on repeated calls', function () {
    $first = TurboData::nowOnce();
    $second = TurboData::nowOnce();

    expect($first)->toBe($second);
});

test('resetNowOnce allows new value to be generated', function () {
    $first = TurboData::nowOnce();
    TurboData::resetNowOnce();
    $second = TurboData::nowOnce();

    // Both should be valid datetime strings
    expect($first)->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');
    expect($second)->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');
});

// ── fromQuery() ────────────────────────────────────────────────────────────────

test('fromQuery returns a closure', function () {
    $pool = TurboData::fromQuery(fn () => [1, 2, 3]);

    expect($pool)->toBeInstanceOf(Closure::class);
});

test('fromQuery cycles through loaded values', function () {
    $loadCount = 0;
    $pool = TurboData::fromQuery(function () use (&$loadCount) {
        $loadCount++;

        return ['a', 'b', 'c'];
    });

    expect($pool(0))->toBe('a');
    expect($pool(1))->toBe('b');
    expect($pool(2))->toBe('c');
    expect($pool(3))->toBe('a'); // wraps
    expect($loadCount)->toBe(1); // loaded only once
});

test('fromQuery throws when loader returns empty array', function () {
    $pool = TurboData::fromQuery(fn () => []);
    $pool(0);
})->throws(RuntimeException::class);

// ── uniqueEmail() / uniqueUsername() / uniqueSlug() ──────────────────────────

test('uniqueEmail returns closure producing valid emails', function () {
    $gen = TurboData::uniqueEmail();

    expect($gen(0))->toContain('@')
        ->toEndWith('.test');
});

test('uniqueEmail produces different emails per index', function () {
    $gen = TurboData::uniqueEmail();

    expect($gen(0))->not->toBe($gen(1));
});

test('uniqueEmail respects custom domain', function () {
    $gen = TurboData::uniqueEmail('example.com');

    expect($gen(0))->toEndWith('@example.com');
});

test('uniqueUsername returns closure with prefix', function () {
    $gen = TurboData::uniqueUsername('staff');

    expect($gen(5))->toStartWith('staff_');
});

test('uniqueUsername produces unique values per index', function () {
    $gen = TurboData::uniqueUsername();

    expect($gen(0))->not->toBe($gen(1));
});

test('uniqueSlug produces url-safe slugs', function () {
    $gen = TurboData::uniqueSlug('My Product');

    $slug = $gen(0);

    expect($slug)->toStartWith('my-product-')
        ->toMatch('/^[a-z0-9\-]+$/');
});

test('uniqueSlug produces unique values per index', function () {
    $gen = TurboData::uniqueSlug('item');

    expect($gen(0))->not->toBe($gen(1));
});

test('uniqueUuid returns a closure producing UUIDs', function () {
    $gen = TurboData::uniqueUuid();

    expect($gen())->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

test('uniqueUuid with prefix prepends it', function () {
    $gen = TurboData::uniqueUuid('ref_');

    expect($gen())->toStartWith('ref_');
});

// ── hashedPassword() ──────────────────────────────────────────────────────────

test('hashedPassword returns a string', function () {
    expect(TurboData::hashedPassword())->toBeString();
});

test('hashedPassword returns a valid bcrypt hash', function () {
    $hash = TurboData::hashedPassword('secret');

    expect(password_verify('secret', $hash))->toBeTrue();
});

test('hashedPassword returns the same hash on repeated calls', function () {
    $first = TurboData::hashedPassword();
    $second = TurboData::hashedPassword();

    expect($first)->toBe($second);
});

test('hashedPassword caches separately per password', function () {
    $hash1 = TurboData::hashedPassword('password');
    $hash2 = TurboData::hashedPassword('other');

    expect($hash1)->not->toBe($hash2);
    expect(password_verify('password', $hash1))->toBeTrue();
    expect(password_verify('other', $hash2))->toBeTrue();
});
