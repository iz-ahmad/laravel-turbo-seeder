<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Contracts\ProgressTrackerInterface;
use IzAhmad\TurboSeeder\Enums\SeederStrategy;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

/**
 * Test progress tracker to verify CSV generation progress.
 */
class TestProgressTracker implements ProgressTrackerInterface
{
    public int $startCount = 0;

    public int $advanceCount = 0;

    public int $finishCount = 0;

    public int $totalReceived = 0;

    public array $advances = [];

    public function start(int $total, SeederStrategy $strategy = SeederStrategy::DEFAULT): void
    {
        $this->startCount++;
        $this->totalReceived = $total;
    }

    public function advance(int $step = 1): void
    {
        $this->advanceCount++;
        $this->advances[] = $step;
    }

    public function finish(): void
    {
        $this->finishCount++;
    }

    public function setMessage(string $message): void
    {
        // no-op
    }

    public function getPercentage(): float
    {
        return 0.0;
    }
}

test('csv strategy shows progress during csv generation', function () {
    $this->truncateTable('test_users');

    $tracker = new TestProgressTracker;
    app()->instance(ProgressTrackerInterface::class, $tracker);

    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
        ])
        ->count(5000)
        ->useCsvStrategy()
        ->withProgressTracking()
        ->run();

    expect($result->success)->toBeTrue()
        ->and($result->recordsInserted)->toBe(5000)
        ->and($tracker->startCount)->toBeGreaterThanOrEqual(1) // Started at least once (could be 2 if fallback occurs)
        ->and($tracker->advanceCount)->toBeGreaterThan(0) // Progress was updated during CSV generation
        ->and($tracker->finishCount)->toBeGreaterThanOrEqual(1) // Finished at least once (could be 2 if fallback occurs)
        ->and(DB::table('test_users')->count())->toBe(5000);
});

test('csv strategy progress tracker receives correct total count', function () {
    $this->truncateTable('test_users');

    $tracker = new TestProgressTracker;
    app()->instance(ProgressTrackerInterface::class, $tracker);

    TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
        ])
        ->count(10000)
        ->useCsvStrategy()
        ->withProgressTracking()
        ->run();

    expect($tracker->startCount)->toBeGreaterThanOrEqual(1)
        ->and($tracker->totalReceived)->toBe(10000);
});

test('csv strategy advances progress in batches during generation', function () {
    $this->truncateTable('test_users');

    $tracker = new TestProgressTracker;
    app()->instance(ProgressTrackerInterface::class, $tracker);

    TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
        ])
        ->count(15000) // Will be processed in batches
        ->useCsvStrategy()
        ->withProgressTracking()
        ->run();

    // Verify progress was advanced multiple times (in batches)
    expect($tracker->advances)->not->toBeEmpty()
        ->and($tracker->advanceCount)->toBeGreaterThan(1) // Multiple batches
        ->and(array_sum($tracker->advances))->toBeGreaterThanOrEqual(15000); // Total should be at least count (could be more if fallback occurs)
});

test('csv strategy with fallback still tracks progress correctly', function () {
    $this->truncateTable('test_users');

    $tracker = new TestProgressTracker;
    app()->instance(ProgressTrackerInterface::class, $tracker);

    // This will potentially fallback to default strategy if CSV import fails
    $result = TurboSeeder::create('test_users')
        ->columns(['name', 'email'])
        ->generate(fn ($i) => [
            'name' => "User {$i}",
            'email' => "user{$i}@test.com",
        ])
        ->count(1000)
        ->useCsvStrategy()
        ->withProgressTracking()
        ->run();

    expect($result->success)->toBeTrue()
        ->and($tracker->startCount)->toBeGreaterThanOrEqual(1) // Started at least once
        ->and($tracker->finishCount)->toBeGreaterThanOrEqual(1) // Finished at least once
        ->and(DB::table('test_users')->count())->toBe(1000);
});
