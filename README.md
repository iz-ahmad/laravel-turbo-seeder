# Laravel Turbo Seeder

[![Tests](https://github.com/iz-ahmad/laravel-turbo-seeder/actions/workflows/run-tests.yml/badge.svg)](https://github.com/iz-ahmad/laravel-turbo-seeder/actions/workflows/run-tests.yml) [![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-blue.svg)](https://php.net) [![Laravel Version](https://img.shields.io/badge/laravel-10--13-red.svg)](https://laravel.com)
<!-- [![Latest Stable Version](https://img.shields.io/packagist/v/iz-ahmad/laravel-turbo-seeder.svg)](https://packagist.org/packages/iz-ahmad/laravel-turbo-seeder) -->
<!-- [![Total Downloads](https://img.shields.io/packagist/dt/iz-ahmad/laravel-turbo-seeder.svg)](https://packagist.org/packages/iz-ahmad/laravel-turbo-seeder) -->
<!-- [![License](https://img.shields.io/packagist/l/iz-ahmad/laravel-turbo-seeder.svg)](LICENSE.md) -->

**Your Laravel factories, at production scale - seed millions of records in seconds, not minutes.**

Laravel Turbo Seeder is a high-performance seeding package built for large-scale data generation (1M+ records) with very minimal time and memory usage. Ideal for testing applications with production-sized datasets.

Use the factory you already have, or drop down to a raw generator for maximum speed - both feed the same bulk-insert / native-CSV engine.

![Laravel Turbo Seeder Demo](images/banner.png)

---

## Why Turbo Seeder?

Default Laravel seeders don’t scale well. When seeding 500K–1M+ records for realistic performance testing, they can consume too much time and slow down development.

**Turbo Seeder eliminates that bottleneck.**

What used to take **~30 minutes** for **1M records** now completes in **~15–60 seconds**.

No more coffee breaks, tab-switching, or "I'll test later"! So you can:

* Test against production-scale datasets
* Detect slow queries and indexing issues early
* Iterate faster without waiting on long seeding cycles

## How It’s So Fast

1. **No Eloquent overhead**: raw queries only (no model events/observers; the generator path skips Faker entirely)
2. **Bulk inserts**: multi-row `INSERT` instead of row-by-row
3. **Native CSV imports**: `LOAD DATA` / `COPY` for maximum throughput
4. **Smart chunking**: controlled memory with automatic garbage collection
5. **Minimal overhead**: foreign key checks & query logging disabled automatically
6. **Streaming I/O**: CSV handled via streams, not loaded fully into memory

---

## Features At A Glance

* **Lightning Fast**: 1M records in 15–60 seconds (table-complexity dependent)
* **Memory Efficient**: under 200MB peak
* **Use Your Factories**: `fromFactory()` reuses your existing model factory at scale
* **Multi-Database**: MySQL, PostgreSQL, SQLite
* **Two Strategies**: bulk insert or native CSV import
* **Scaffolder**: `php artisan make:turbo-seeder` generates a ready-to-edit seeder
* **Fluent API**: clean, chainable interface
* **TurboData Helpers**: Faker-free data generation: weighted picks, date ranges, unique values
* **Data Type Handling**: automatically formats enums, JSON, dates, collections, and objects.
* **Relational Seeding**: load FK values from seeded tables in one line, zero extra queries
* **Progress Tracking**: real-time progress with metrics
* **Highly Configurable**: chunk size, transactions, upserts, retries, dry-run, etc.
* **Laravel 10–13 Compatible**

### Ideal For
* Performance and load testing with large datasets
* Dev environments needing production-scale data generation
* CI/CD pipelines requiring fast seeding
* Query and database performance benchmarking

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Two Ways to Generate Data](#two-ways-to-generate-data)
- [Quick Start](#quick-start)
  - [Basic Usage](#basic-usage-generator-path)
  - [CSV Strategy (Fastest)](#csv-strategy-fastest)
  - [Advanced Configuration](#advanced-configuration)
- [Common Use Cases](#common-use-cases)
- [Strategy Comparison](#strategy-comparison)
- [CSV Strategy Setup](#csv-strategy-setup)
  - [Troubleshooting](#troubleshooting)
- [Migration from Standard Seeders](#migration-from-standard-seeders)
- [API Documentation](#api-documentation)
- [Configuration Reference](#configuration-reference)
- [Architecture Overview](#architecture-overview)
- [Performance Benchmarks](#performance-benchmarks)
- [Others](#testing)

---

## Requirements

- PHP 8.2+
- Laravel 10.x, 11.x, 12.x, or 13.x
- MySQL 5.7+, PostgreSQL 9.6+, or SQLite 3.24+

---

## Installation

```bash
composer require iz-ahmad/laravel-turbo-seeder
```

The package auto-registers itself. Optionally publish the config:

```bash
php artisan vendor:publish --tag="turbo-seeder-config"
```

This creates `config/turbo-seeder.php` in your project.

> **Note:** This package is not publicly released yet.
> So for now, you can use it locally by cloning the repository and installing it in your Laravel application via a path repository in `composer.json`.

### Local Installation

1. Clone the repository somewhere on your machine:

```bash
git clone https://github.com/iz-ahmad/laravel-turbo-seeder.git
```

2. In your Laravel project's `composer.json`, add:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../path-to-laravel-turbo-seeder"
        }
    ]
}
```

3. Then require it:

```bash
composer require iz-ahmad/laravel-turbo-seeder:@dev
```

---

## Two Ways to Generate Data

TurboSeeder gives you **two interchangeable ways** to produce rows. Both run through
the same high-performance engine; pick whichever fits the job.

| | `fromFactory()` — convenience tier | `generate()` — speed tier |
|---|---|---|
| **You write** | Nothing new — reuse your existing model factory | A small closure returning an array |
| **Source of truth** | The factory definition (one place) | The generator closure |
| **Faker** | Yes (per row) | No (use `TurboData` helpers) |
| **Timestamps / states** | Auto-filled / applied | Manual (`withTimestamps()` helps) |
| **Throughput (1M rows)** | Fast (~minutes — Faker-bound) | Fastest (~15–60s) |
| **Best for** | ≤ ~100k rows, or when a factory already exists | Maximum speed, huge datasets |

> **Skipped on both paths:** model events, observers and accessors/mutators. Anything
> those compute (slugs, hashes, derived columns) must live in the factory definition or
> the generator closure.

### Factory path (`fromFactory()`)

Reuse the factory you already maintain — no second source of truth, no drift:

```php
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

TurboSeeder::fromFactory(User::factory()->unverified())
    ->count(1_000_000)
    ->run();
```

The table is inferred from the factory's model, factory **states** are applied, and
`created_at`/`updated_at` are filled automatically when the model uses timestamps.

---

## Quick Start

No extra configuration required to get started.  
The default strategy works out of the box with sensible, performance-optimized settings **already configured** for you. So you just have to: 

Install → write your generator (or reuse a factory) → run.

### Basic Usage (generator path)

```php
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

$uniqueEmail = TurboData::uniqueEmail();

TurboSeeder::create('users')
    ->columns(['name', 'email', 'password'])
    ->generate(fn ($index) => [
        'name'       => "User {$index}",
        'email'      => $uniqueEmail($index),
        'password'   => TurboData::hashedPassword(),
    ])
    ->withTimestamps()   // fills created_at / updated_at for you
    ->count(100000)
    ->run();
```

### CSV Strategy (Fastest)

```php
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

TurboSeeder::create('posts')
    ->columns(['user_id', 'title', 'content', 'created_at'])
    ->generate(fn ($index) => [
        'user_id'    => TurboData::randomInt(1, 10000),
        'title'      => "Post {$index}",
        'content'    => "Content for post {$index}",
        'created_at' => TurboData::nowOnce(),
    ])
    ->count(1000000)
    ->useCsvStrategy()
    ->run();
```

### Advanced Configuration

```php
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

TurboSeeder::create('orders')
    ->columns(['user_id', 'total', 'status', 'created_at'])
    ->generate(fn ($index) => [
        'user_id'    => TurboData::randomInt(1, 10000),
        'total'      => TurboData::randomFloat(2, 10.00, 999.99),
        'status'     => TurboData::weightedFrom(['pending' => 50, 'completed' => 40, 'cancelled' => 10]),
        'created_at' => TurboData::dateRange('2023-01-01', '2024-12-31'),
    ])
    ->count(50000)
    ->chunkSize(3000)
    ->withProgressTracking()
    ->disableForeignKeyChecks()
    ->connection('mysql')
    ->run();
```

See [examples/ExampleSeeder.php](examples/ExampleSeeder.php) and [Common Use Cases](#common-use-cases) for more examples.

---

## Common Use Cases

### Seeding Tables with Relationships

Create users with related posts using `fromTable()` for clean FK assignment:

```php
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

// Seed users first
TurboSeeder::create('users')
    ->columns(['name', 'email', 'created_at'])
    ->generate(fn ($index) => [
        'name'       => "User {$index}",
        'email'      => "user{$index}@example.com",
        'created_at' => TurboData::nowOnce(),
    ])
    ->count(50000)
    ->run();

// fromTable() loads user IDs once from the DB, then cycles deterministically
$userIds = TurboData::fromTable('users');

// seed posts - each post gets a valid user_id with zero extra DB queries
TurboSeeder::create('posts')
    ->columns(['user_id', 'title', 'content', 'created_at'])
    ->generate(fn ($index) => [
        'user_id'    => $userIds($index),
        'title'      => "Post {$index}",
        'content'    => "Content for post {$index}",
        'created_at' => TurboData::dateRange('2023-01-01', '2024-12-31'),
    ])
    ->count(100000)
    ->useCsvStrategy()
    ->run();
```

### Creating Time-Series Data

Generate sequential data for analytics:

```php
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

TurboSeeder::create('analytics_events')
    ->columns(['event_type', 'value', 'recorded_at'])
    ->generate(fn ($index) => [
        'event_type'  => TurboData::cycleFrom(['page_view', 'click', 'signup'])($index),
        'value'       => TurboData::randomInt(1, 100),
        'recorded_at' => TurboData::sequentialDate('2024-01-01', 'hour', $index),
    ])
    ->count(8760) // One year of hourly data
    ->run();
```

### Performance Testing Scenarios

Test your application with realistic data volumes:

```php
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

// Simulate e-commerce orders
TurboSeeder::create('orders')
    ->columns(['user_id', 'total', 'status', 'created_at'])
    ->generate(fn ($index) => [
        'user_id'    => TurboData::randomInt(1, 50000),
        'total'      => TurboData::randomFloat(2, 10.00, 999.99),
        'status'     => TurboData::weightedFrom(['pending' => 30, 'completed' => 60, 'cancelled' => 10]),
        'created_at' => TurboData::dateRange('2023-01-01', '2024-12-31'),
    ])
    ->count(500000)
    ->chunkSize(2000)
    ->withProgressTracking()
    ->run();
```

### CI/CD Integration

Use in your CI/CD pipeline for fast test data setup:

```bash
# In your CI/CD script
php artisan migrate:fresh --seed
php artisan turbo-seeder:run PerformanceTestSeeder
php artisan test
```

---

## Strategy Comparison

| Feature | Default Strategy | CSV Strategy |
|---------|-----------------|--------------|
| **Speed** | Fast (~15-60s for 1M) | Fastest (~9-40s for 1M)¹ |
| **Memory** | Moderate (~50-160 MB) | Minimal (~0 MB additional) |
| **Setup** | No configuration required | MySQL needs `local_infile`; PostgreSQL needs nothing |
| **Best For** | General use, remote databases | Maximum throughput |
| **Compatibility** | All databases | MySQL, PostgreSQL, SQLite |

¹ **SQLite Note:** CSV strategy may be _slower_ than default strategy on SQLite due to file I/O overhead. CSV shines mainly on MySQL (`LOAD DATA`) and PostgreSQL (`COPY`).

**Recommendation**: 
- **MySQL/PostgreSQL**: Use CSV strategy for 1M+ records (PostgreSQL works out of the box; MySQL needs `local_infile` enabled)
- **SQLite**: Use default strategy
- **General use**: Start with default. Switch to CSV strategy for maximum speed.

> **MySQL pre-flight:** before generating the (potentially huge) CSV, TurboSeeder
> checks that `LOCAL INFILE` is actually available. If it isn't, it falls back to
> the default strategy immediately - without wasting time writing a file that
> couldn't be imported.

## CSV Strategy Setup

The CSV strategy provides the fastest seeding performance but requires additional database configuration.

### Automatic Fallback

If CSV strategy is not properly configured, TurboSeeder will **automatically fall back** to the default (bulk insert) strategy. You'll see a warning message with instructions, but seeding will continue successfully.

### MySQL Configuration

To enable CSV strategy for MySQL, add `PDO::MYSQL_ATTR_LOCAL_INFILE` to your database connection options:

```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    // ... other settings ...
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        PDO::MYSQL_ATTR_LOCAL_INFILE => true,  // Add this line
    ]) : [],
],
```

**MySQL server-side requirement:** The above enables the client side. MySQL also requires `local_infile` to be enabled on the server — it defaults to `OFF` in MySQL 8.0 and later. Enable it at runtime: `SET GLOBAL local_infile = 1;` or permanently via `local_infile = 1` under `[mysqld]` in your MySQL config file.

**Security Note:** `LOAD DATA LOCAL INFILE` allows MySQL to read files from the client machine. Only enable this in trusted environments (development, staging). Consider disabling in production unless absolutely necessary.

### PostgreSQL Configuration

**No special configuration required.** The CSV strategy streams data with a
**client-side `COPY ... FROM STDIN`** (via PDO's `pgsqlCopyFromFile`), so it works
on managed and containerised PostgreSQL (RDS, Cloud SQL, Neon, Supabase, Docker)
where the database server cannot read the application's filesystem - and it does
**not** require a superuser. The connecting user only needs normal `INSERT`
privileges on the target table.

### SQLite Configuration

SQLite supports CSV strategy but has different performance characteristics:

**Performance Note:** Due to SQLite's file-based architecture and file I/O overhead, the CSV strategy sometimes may be **slower** than the default bulk insert strategy. So, for SQLite development, use the **default strategy** unless you specifically find CSV beneficial for your use case.

### Troubleshooting

If you see a warning about CSV strategy falling back to default:

1. **MySQL** - Verify `PDO::MYSQL_ATTR_LOCAL_INFILE => true` is in `config/database.php`
2. **PostgreSQL** - Check file permissions and COPY privileges
3. **All** - Review application logs for detailed error messages

The **default** strategy works _without_ any additional configuration and is still very fast.

---

## Migration from Standard Seeders

Converting existing Laravel seeders to use Turbo Seeder is straightforward:

### Before (Standard Laravel Seeder)

```php
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->count(10000)->create();
    }
}
```

### After (Turbo Seeder) — reuse your factory

The simplest migration keeps your existing factory as the single source of truth:

```php
use Illuminate\Database\Seeder;
use App\Models\User;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        TurboSeeder::fromFactory(User::factory())
            ->count(10000)
            ->run();
    }
}
```

### After (Turbo Seeder) — raw generator for maximum speed

When you want the absolute fastest path (no Faker), use a generator:

```php
use Illuminate\Database\Seeder;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $uniqueEmail = TurboData::uniqueEmail();

        TurboSeeder::create('users')
            ->columns(['name', 'email', 'password'])
            ->generate(fn ($i) => [
                'name'     => "User {$i}",
                'email'    => $uniqueEmail($i),
                'password' => TurboData::hashedPassword(),
            ])
            ->withTimestamps()
            ->count(10000)
            ->run();
    }
}
```

---

## API Documentation

### Fluent API Methods

#### Core Methods

- `TurboSeeder::create(string $table)` - Start a builder for the generator path
- `TurboSeeder::fromFactory(Factory $factory)` - Start a builder from a model factory (table inferred from the model)
- `table(string $table)` - Set the table name. Accepts plain names (`users`) and schema-qualified names (`public.users`, `myschema.my_table`). Names must start with a letter or underscore and contain only letters, digits, and underscores. **SQLite note:** schema-qualified names require an [ATTACHed database](https://www.sqlite.org/lang_attach.html) alias; without ATTACH the query will fail at runtime with "no such table".
- `columns(array $columns)` - Set columns to seed
- `columnsFromSchema()` - Derive the columns from the table schema (every column except the auto-increment key)
- `generate(Closure $generator)` - Set data generator function
- `count(int $count)` - Set number of records to seed
- `run()` - Execute the seeding operation

#### Strategy Methods

- `useCsvStrategy()` - Native CSV file import (fastest)
- `useDefaultStrategy()` - Bulk INSERT (default)
- `strategy(SeederStrategy $strategy)` - Set via enum directly

#### Configuration Methods

- `connection(string $connection)` - Database connection to use
- `chunkSize(int $size)` - Records per chunk. **Clamped automatically** to the driver's bind-parameter ceiling (65,535 on MySQL/PostgreSQL; SQLite uses its real variable limit), so a large value can't overflow a single statement.
- `withTimestamps()` / `withoutTimestamps()` - Auto-fill `created_at`/`updated_at` (on by default for the factory path when the model uses timestamps)
- `truncate()` - Empty the target table before seeding (committed; cannot be combined with `dryRun()`)
- `commitEvery(int $chunks)` - Default strategy only: commit every N chunks instead of one wrapping transaction (for very large seeds)
- `withProgressTracking()` / `withoutProgressTracking()` - Toggle progress bar
- `disableForeignKeyChecks()` / `enableForeignKeyChecks()` - Toggle FK checks
- `disableUniqueChecks()` / `enableUniqueChecks()` - **MySQL only**, opt-in. Disabling unique checks speeds up bulk loads but can admit duplicates into unique secondary indexes - only use it when the data is known unique. Not implied by `disableForeignKeyChecks()`.
- `disableQueryLog()` / `enableQueryLog()` - Toggle query logging
- `useTransactions()` / `withoutTransactions()` - Toggle the wrapping transaction. The CSV strategy defaults to **no** wrapping transaction (native imports are atomic per statement); the default strategy wraps the run unless `commitEvery()` is used.
- `options(array $options)` - Merge custom options
- `when(bool|callable $condition, callable $callback, ?callable $default)` - Conditional chaining
- `unless(bool|callable $condition, callable $callback, ?callable $default)` - Inverse conditional

#### Advanced Methods

- `dryRun(bool $enabled = true)` - Generate and validate data without committing. Uses transaction rollback; `$result->isDryRun` will be `true`.
> **Do not combine this with `withoutTransactions()`**; because without a transaction, there is nothing to roll back and rows will be permanently written.

- `upsert(array $uniqueBy)` - On conflict, update non-key columns. Uses `ON DUPLICATE KEY UPDATE` (MySQL), `ON CONFLICT DO UPDATE SET` (PostgreSQL / SQLite 3.24+). Keys must be a subset of declared columns **and must exactly match a unique or primary index** on the table - this is validated up front and fails fast with a clear error otherwise. When every declared column is a key (nothing to update), conflicts are skipped (`DO NOTHING`) consistently across MySQL, PostgreSQL and SQLite.

- `retryAttempts(int $attempts)` - Retry on transient deadlock / lock-timeout failures (SQLSTATE 40001, MySQL 1205) with exponential backoff. Accepts 1–10; defaults to 3.

- `withoutColumnValidation()` - Skip the pre-seed schema check that validates declared columns exist on the table.

#### Events

TurboSeeder dispatches three events:

- `TurboSeederStarting` — before seeding begins (carries `table`, `count`, `strategy`, `connection`).
- `TurboSeederCompleted` — after a successful seed, including dry-runs (carries `table`, `result`).
- `TurboSeederFailed` — when seeding throws (carries `table`, `connection`, `exception`).

`TurboSeederCompleted` is dispatched after every successful seed, including dry-runs; which you can use to trigger actions after seeding as per your requirements.

```php
use IzAhmad\TurboSeeder\Events\TurboSeederCompleted;
 
class SendTurboSeederCompletedNotification
{
    /**
     * Handle the event.
     */
    public function handle(TurboSeederCompleted $event): void
    {
        // $event->table  - the seeded table name
        // $event->result - SeederResultDTO (includes isDryRun flag)

        if ($event->result->isDryRun) {
            return; // no rows were committed
        }
    }
}
```

> **Note:** Always check `$event->result->isDryRun` before acting on the assumption that rows were committed. The event is **not** dispatched when seeding fails.

---

### TurboData Helpers

`TurboData` is a Faker-free data generation utility designed for high-volume seeding. Every method is safe to call 1M+ times.
> All returned values are automatically formatted via the internal **ValueFormatter**.

**Three calling conventions** (worth knowing up front):

| Convention | Helpers | How to use |
|---|---|---|
| **Returns a closure** — call it with `$index` | `cycleFrom`, `uniqueEmail`, `uniqueUsername`, `uniqueSlug`, `uniqueUuid`, `fromTable`, `fromQuery`, `fromTableStream` | Create once **outside** the generator, call inside: `$email = TurboData::uniqueEmail(); ... 'email' => $email($index)` |
| **Returns a value** — call per row | `weightedFrom`, `randomFrom`, `randomInt`, `randomFloat`, `randomBool`, `nullable`, `dateRange`, `sequentialDate` | Call directly inside the generator: `'status' => TurboData::weightedFrom([...])` |
| **Computed once, cached** | `nowOnce`, `hashedPassword` | Call inside the generator; the value is computed a single time and reused |

```php
use IzAhmad\TurboSeeder\Helpers\TurboData;
```

#### Value Selection

```php
// Round-robin cycling
$role = TurboData::cycleFrom(['admin', 'editor', 'viewer']); // returns a closure: $role($index)

// Weighted random
$status = TurboData::weightedFrom(['active' => 70, 'pending' => 20, 'banned' => 10]); // returns value directly

// Uniform random
$method = TurboData::randomFrom(['paypal', 'bank_transfer', 'credit_card']);
```

#### Scalars

```php
$age   = TurboData::randomInt(18, 65);
$price = TurboData::randomFloat(2, 9.99, 999.99);
$flag  = TurboData::randomBool(0.8); // 80% true
```

#### Dates & Timestamps

```php
// Random date within a range
$date = TurboData::dateRange('2022-01-01', '2024-12-31');

// Sequential timestamps - good for time-series data
$ts = TurboData::sequentialDate('2024-01-01', 'hour', $index);

// Use nowOnce() inside generators for better performance - avoids calling now() 1M times
'created_at' => TurboData::nowOnce()

// Hash once, reuse across all records - never call bcrypt() inside the generator
'password' => TurboData::hashedPassword()          // default: 'password'
'password' => TurboData::hashedPassword('secret')  // custom password
```

#### Nullable Values

```php
// 15% chance of null; value only evaluated when not null
$deletedAt = TurboData::nullable(0.15, fn () => now());
```

#### Seeding Related Tables

**`fromTable()`** is the standard way to assign FK values. It plucks a column from an already-seeded table once, caches it in memory, then cycles or randomly picks from it on every generator call, ensuring zero extra DB queries after the first.

```php
use IzAhmad\TurboSeeder\Enums\FromTableMode;

$userIds     = TurboData::fromTable('users');                      // cycle (default)
$categoryIds = TurboData::fromTable('categories', 'id', 'random'); // random pick (string)
$tagIds      = TurboData::fromTable('tags', 'id', FromTableMode::RANDOM); // or the enum
$codes       = TurboData::fromTable('regions', 'code', 'cycle', 'reports'); // custom column + connection

TurboSeeder::create('posts')
    ->columns(['user_id', 'category_id', 'title'])
    ->generate(fn ($i) => [
        'user_id'     => $userIds($i),
        'category_id' => $categoryIds($i),
        'title'       => "Post {$i}",
    ])
    ->count(1_000_000)
    ->run();
```

> Seed the referenced table **before** calling `fromTable()`. The DB query fires once on the first generator call; all subsequent calls are O(1) array lookups.

**`fromQuery()`** - use this when `fromTable()` isn't enough: custom filters, joins, specific ordering, or any query that can't be expressed as a simple column pluck.

```php
// Only referencing `active` users; fromTable() can't filter, but fromQuery() can
$userIds = TurboData::fromQuery(
    fn () => DB::table('users')->where('active', 1)->orderBy('id')->pluck('id')->toArray()
);
```

`fromQuery()` accepts any callable that returns an array. Same lazy-load and cycle semantics as the `fromTable()` (loaded once, then cycled by index).

> **Huge reference tables?** `fromTable()`/`fromQuery()` load the whole pool into
> memory (and log a warning past ~500k values). For very large reference tables
> use **`TurboData::fromTableStream('users', 'id', pageSize: 10_000)`**, which
> streams IDs one page at a time and cycles through them with bounded memory.

#### Unique Values

```php
$email = TurboData::uniqueEmail();         // u_a3f9b2c1_0@turbo.test
$user  = TurboData::uniqueUsername('usr'); // usr_a3f9b2c1_0
$slug  = TurboData::uniqueSlug('My Post'); // my-post-a3f9b2c1-0
$uuid  = TurboData::uniqueUuid('ref_');    // ref_xxxxxxxx-xxxx-...
// All return closures: $email($index)
```

---

### Data Type Handling

TurboSeeder **automatically formats** all types of values returned from your generator via **ValueFormatter**. You don’t need to manually convert data types; everything is handled internally.

#### Supported Types

| Input Type               | Stored As     |
| ------------------------ | ------------- |
| `null`                   | `NULL`        |
| `bool`                   | `1` / `0`     |
| `int`, `float`, `string` | unchanged     |
| `json` (string)          | stored as-is  |
| `DateTime` / `Carbon`    | `Y-m-d H:i:s` |
| `BackedEnum`             | enum value    |
| `UnitEnum`               | enum name     |
| `array`                  | JSON string   |
| `Collection`             | JSON string   |
| `object` / `stdClass`    | JSON string   |

#### JSON Handling Example

```php
TurboSeeder::create('posts')
    ->columns(['data', 'metadata'])
    ->generate(fn ($i) => [
        // PHP array - automatically JSON encoded
        'data' => ['nested' => ['key' => 'value']],

        // JSON string - stored as-is (no double encoding)
        'metadata' => '{"source":"api"}',
    ])
    ->count(1000)
    ->run();
```

**Result in database:**

* `data` → `{"nested":{"key":"value"}}`
* `metadata` → `{"source":"api"}`

#### Custom Type Formatters

You can even register custom formatters for your own value objects, if you need to:

```php
use IzAhmad\TurboSeeder\Services\ValueFormatter;

// In a service provider
ValueFormatter::extend(
    Money::class,
    fn ($money) => $money->getAmount()
);
```

Now any `Money` object returned from your generator will be formatted automatically.

#### Manual Formatting

You won't need to manually format values, since TurboSeeder does it automatically. Only use `ValueFormatter` manually if you need to validate or format outside the generator context:

```php
use IzAhmad\TurboSeeder\Services\ValueFormatter;

ValueFormatter::format($value);
ValueFormatter::formatForCsv($value, '\\N');
```

**Key Behaviors:**

* Fully automatic - no manual conversions required
* Type-safe - preserves scalar types and safely converts complex types
* JSON-safe - no double encoding
* CSV-compatible
* Extensible for custom value objects

---

### Artisan Commands

#### Scaffold a Seeder

```bash
php artisan make:turbo-seeder UsersTurboSeeder --table=users --count=1000000
```

Generates a ready-to-edit seeder in `database/seeders`, with the columns
introspected from the given table. Options:

- `--table=` - Table to introspect for columns
- `--count=` - Number of records (default: 1000)
- `--factory` - Generate a `fromFactory()`-based stub instead of a `generate()` one
- `--force` - Overwrite an existing seeder

#### Run a Seeder

```bash
php artisan turbo-seeder:run YourSeederClass
```

**Arguments:**
- `seeder` - The seeder class name

**Options:**
- `--class=` - Seeder class name (no need if you use the `seeder` argument)

You can still use Laravel’s native `php artisan db:seed` command when using this package. 
_However_, the `turbo-seeder:run` command provided by this package offers **additional benefits**: easily **customize** options, view detailed **performance metrics**, and monitor real-time **progress**; making it ideal for large-scale or advanced seeding operations.

#### Benchmark Performance

```bash
php artisan turbo-seeder:benchmark [--connection=] [--table=] [--records=] [--force]
```

**Options:**
- `--connection=` - Database connection
- `--table=` - Table name (default: benchmark_test)
- `--records=` - Number of records (default: 50000)
- `--force` - Skip the confirmation prompt outside local/testing environments

> The benchmark **creates and drops** its own table. It refuses to run if the
> chosen `--table` already exists, and asks for confirmation when not in a
> local/testing environment, so it can never destroy a real table by accident.

#### Test Connection

```bash
php artisan turbo-seeder:test-connection
```

#### Clear Cache

```bash
php artisan turbo-seeder:clear-cache [--all]
```

**Options:**
- `--all` - Clear all temporary files including subdirectories created during seeding.

---

## Configuration Reference

We have provided an optimal configuration for you to use. Still, you can publish and customize the config for full control:

```bash
php artisan vendor:publish --tag="turbo-seeder-config"
```

### Chunk Sizes

Chunk size determines how many records are inserted (processed in memory) at once. This directly impacts memory usage and performance.

**Config Priority Order:**
1. **Custom chunk size** (set via `->chunkSize()` in the seeder class using TurboSeeder fluent API) - gets **Highest** priority
2. **Database-specific chunk size** (from `chunk_sizes.{database_driver}` config) - gets **Medium** priority
3. **Default chunk size** (from `default_chunk_size` config) - used as Fallback

```php
'default_chunk_size' => 1000, // Fallback when database-specific size not set

'chunk_sizes' => [
    'mysql' => 1000,   // Optimal for MySQL
    'pgsql' => 800,    // Optimal for PostgreSQL
    'sqlite' => 500,   // Optimal for SQLite
], // these values take priority over the default_chunk_size
```

**Why Chunk Size Matters:**

Chunk size directly affects memory consumption. Each chunk loads all records into memory before inserting them into the database. The memory usage formula is approximately:

```
Memory ≈ (chunk_size × number_of_columns × average_value_size) + overhead
```

**Key Considerations:**

- **More columns = smaller chunk size needed**: Tables with 15+ columns or large fields require smaller chunks to stay within memory limits.
- **Fewer columns = larger chunk size possible**: Simple tables (3-5 columns) can handle larger chunks efficiently.
- **Default strategy**: More memory-intensive than CSV strategy, so consider **smaller chunks for large datasets**.
- **CSV strategy**: More memory-efficient, can handle larger chunks even with many columns. Because it uses the database's **native CSV import** command.

**Recommendations for chunk size:**

- **Simple tables (3-5 columns)**: 1000 - 5000
- **Medium tables (6-10 columns)**: ~ 1000
- **Complex tables (15+ columns, large text/JSON)**: 200 - 1000
- **For very large datasets (1M+ records)**: Consider CSV strategy or reduce chunk size if memory limit is exhausted.

### Memory Management

Configure memory limits and garbage collection:

```php
'memory' => [
    'limit_mb' => 256,              // Memory limit in MB
    'gc_threshold_percent' => 80,   // Trigger GC at 80% memory usage
    'force_gc_after_chunks' => 10,  // Force GC every 10 chunks
],
```

### Performance Optimizations

Enable/disable various performance features:

```php
'performance' => [
    'disable_query_log' => true,       // Disable Laravel query logging (recommended)
    'disable_foreign_keys' => true,    // Disable foreign key checks during seeding
    'disable_unique_checks' => false,  // MySQL only, opt-in: see disableUniqueChecks()
    'use_transactions' => true,        // Wrap the default-strategy run in a transaction
],
```

> These keys are honoured when you publish the config. Per-seeder builder methods
> (e.g. `withoutTransactions()`, `disableForeignKeyChecks()`) override them for a
> single run. The CSV strategy ignores `use_transactions` by default (native
> imports are atomic per statement).

### CSV Strategy Configuration

Settings for CSV-based seeding:

```php
'csv_strategy' => [
    'temp_path' => storage_path('app/turbo-seeder'),     // Directory for temporary CSV files
    'buffer_size' => 8192,                               // File write buffer size (bytes)
    'field_delimiter' => ',',                            // CSV field separator
    'field_enclosure' => '"',                            // CSV field enclosure
    'batch_size' => 10000,                               // Records per CSV batch
    'gc_frequency' => 5,                                 // Run GC every N batches
    'reader_chunk_size_for_sqlite' => 500,               // SQLite CSV read chunk size
    'fallback_to_default_strategy_on_config_error' => true, // Auto fallback to default strategy (bulk insert) if CSV fails due to missing configuration.
    'null_marker' => '\\N',                              // Sentinel used for NULL values in CSV files
],
```

**Key Settings:**
- `fallback_to_default_strategy_on_config_error` - Automatically switches to bulk insert if CSV import fails due to missing database configuration. Ensures seeding completes successfully.
- `null_marker` - The string written to CSV for `null` values. The default `\N` matches MySQL and PostgreSQL native CSV null conventions. If a **non-null** value your generator produces is exactly equal to the marker, TurboSeeder **fails loudly** (rather than silently importing it as `NULL`) and asks you to change this setting - so set it to a string your data never contains if you hit that.

### Progress Tracking

Configure progress bar display:

```php
'progress' => [
    'enabled' => true,           // Enable progress tracking by default
],
```

### Error Handling

Configure error reporting:

```php
'get_error_trace_on_console' => false, // Show full stack trace in console on errors, note that errors are always fully logged to Laravel logs regardless of this setting.
'max_error_message_length_in_console' => 600, // Max characters of error message shown in console before truncation
```

### Seeder Namespace

Default namespace for seeder classes:

```php
'seeder_classes_namespace' => 'Database\\Seeders\\', // Auto-resolve seeder class names
```

**Usage:** Allows using short class names in commands. For example, `php artisan turbo-seeder:run UserSeeder` instead of `php artisan turbo-seeder:run Database\\Seeders\\UserSeeder`.

---

## Architecture Overview

Turbo Seeder follows a clean and efficient execution flow:

**Data Generator → Chunk Builder → Seeding Strategy → Database**

1. `generate()` produces row data.
2. Rows are grouped into memory-controlled chunks.
3. The selected strategy (Bulk Insert or CSV) processes each chunk.
4. Data is written using optimized native database operations.

Memory is controlled at the **chunk level**, with automatic garbage collection.
With the **CSV strategy**, rows are streamed to temporary files (`storage/app/turbo-seeder/`) and imported via native commands (`LOAD DATA` / `COPY`), avoiding large in-memory payloads.

---

## Performance Benchmarks

Measured on a modern local machine with **MySQL** and default chunk sizes.

### Default Strategy (Bulk Insert)

| Table complexity | Records | Time | Peak memory |
|---|---|---|---|
| Simple (~5 cols) | 1M | ~16s | ~50 MB |
| Complex (~15–20 cols) | 1M | ~60s | ~160 MB |

Best for: general use, remote databases, when CSV import isn't available.

### CSV Strategy (File Import)

| Table complexity | Records | Time | Additional memory |
|---|---|---|---|
| Simple (~5 cols) | 1M | ~9s | ~0 MB |
| Complex (~15–20 cols) | 1M | ~40s | ~0 MB |

Best for: local **MySQL/PostgreSQL** databases, maximum throughput where `LOAD DATA` / `COPY` can be enabled. On **SQLite**, the CSV strategy may be comparable or slower than the default strategy due to file I/O overhead.

> Results vary by hardware, DB engine/version, network latency, and chunk size.
> Reproduce them on your own machine with `php artisan turbo-seeder:benchmark`.

### A note on `fromFactory()` throughput

The factory path calls your factory's `definition()` (and therefore Faker) once per
row, so it is **CPU-bound on Faker**, not on the database. Expect it to be markedly
slower than the raw `generate()` numbers above (typically minutes, not seconds, for
1M rows) — still far faster than `Model::factory()->create()` because it skips
Eloquent, model events and per-row inserts. Use `fromFactory()` for convenience at
moderate volumes, and the raw generator when you need maximum throughput.

---

## Testing

Run the test suite to ensure everything is working correctly:

```bash
composer test
```

**Test Framework:** Pest PHP with SQLite, MySQL, and PostgreSQL support

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover a security issue, please email `n.ahmad.web.cit22@gmail.com` instead of opening a public issue.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for recent changes.

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.

## Credits

- All Contributors

**Made with ❤️ for the Laravel community**
