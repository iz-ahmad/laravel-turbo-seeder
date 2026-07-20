# Laravel Turbo Seeder

[![Tests](https://github.com/iz-ahmad/laravel-turbo-seeder/actions/workflows/run-tests.yml/badge.svg)](https://github.com/iz-ahmad/laravel-turbo-seeder/actions/workflows/run-tests.yml) [![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-blue.svg)](https://php.net) [![Laravel Version](https://img.shields.io/badge/laravel-10--13-red.svg)](https://laravel.com)
<!-- [![Latest Stable Version](https://img.shields.io/packagist/v/iz-ahmad/laravel-turbo-seeder.svg)](https://packagist.org/packages/iz-ahmad/laravel-turbo-seeder) -->
<!-- [![Total Downloads](https://img.shields.io/packagist/dt/iz-ahmad/laravel-turbo-seeder.svg)](https://packagist.org/packages/iz-ahmad/laravel-turbo-seeder) -->
<!-- [![License](https://img.shields.io/packagist/l/iz-ahmad/laravel-turbo-seeder.svg)](LICENSE.md) -->

**Blazing fast database seeder for Laravel - seed millions of records in seconds; not minutes.**

Laravel Turbo Seeder is a high-performance database seeder package built for production-scale data generation (1M+ records) with minimal time and memory. Ideal for testing applications with production-sized datasets.

![Laravel Turbo Seeder Demo](images/banner.png)

---

## Why Turbo Seeder?

Default Laravel seeders don't scale well. Seeding 500K-1M+ records for realistic load testing can take ~30+ minutes and unbounded memory usage, thus slowing down development.

**Turbo Seeder eliminates that bottleneck.**

| | Standard Laravel | Turbo Seeder |
|---|---|---|
| 1M records (simple table) | ~30 min | **~20s** |
| 1M records (complex table) | ~40-50 min | **~60s** |
| 1M records (CSV strategy) | - | **~10-45s** |
| Peak Memory | unbounded | **< 50-200 MB** |

No more coffee breaks, tab-switching, or "I'll test later"! So you can:

- Test against production-scale datasets in seconds
- Detect slow queries and indexing issues early
- Iterate faster without waiting on long seeding cycles

## How It's So Fast

1. **No Eloquent overhead** - raw SQL only; no model events, no Observers.
2. **Bulk inserts** - multi-row `INSERT` instead of row-by-row
3. **Native CSV imports** - `LOAD DATA LOCAL INFILE` (MySQL) / `COPY FROM STDIN` (PostgreSQL) for maximum throughput
4. **Smart configurable chunking** - lazy generators keep memory flat; automatic GC between chunks
5. **Minimal overhead** - FK checks and query logging disabled automatically during the seed
6. **Streaming I/O** - CSV is piped as a stream and never fully loaded into memory

---

## Features At A Glance

- **Lightning Fast**: millions of rows in seconds, not minutes
- **Memory Efficient**: bounded and predictable memory usage
- **Two Data Generation Paths**: `fromFactory()` (reuse your existing factory) or `generate()` (Faker-free raw closure with built-in helpers)
- **Two Seeding Strategies**: bulk INSERT or native CSV import
- **Multi-Database**: MySQL, PostgreSQL, SQLite
- **TurboData Helpers**: Faker-free data generation helpers for weighted picks, date ranges, unique values, FK assignment
- **Data Type Formatting**: automatically handles enums, JSON, dates, arrays, collections
- **Relational Seeding**: load FK values from seeded tables in one line, zero extra queries
- **Upsert Support**: duplicate-key conflict resolution per driver with pre-flight index validation
- **Progress Tracking**: real-time progress bar with metrics
- **Dry Run**: validate data generation without committing
- **Highly Configurable**: chunk size, transactions, retries, FK/unique checks, and more
- **Laravel 10-13 Compatible**

### Ideal For
* Performance and load testing with large datasets
* Dev environments needing production-scale data generation
* CI/CD pipelines requiring fast seeding
* Query and database performance benchmarking

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
  - [Scaffold a seeder](#scaffold-a-seeder-recommended)
  - [Basic Usage](#basic-usage)
  - [Advanced Configuration](#advanced-configuration)
- [Choices You Make](#choices-you-make)
  - [Two Data Generation Paths](#two-data-generation-paths)
  - [Two Seeding Strategies](#two-seeding-strategies)
- [Migration from Standard Seeders](#migration-from-standard-seeders)
- [Common Use Cases](#common-use-cases)
- [CSV Strategy Setup](#csv-strategy-setup)
- [API Documentation](#api-documentation)
  - [Fluent Builder Methods](#fluent-builder-methods)
  - [What `run()` Returns](#what-run-returns)
  - [Events](#events)
  - [TurboData Helpers](#turbodata-helpers)
  - [Data Type Handling](#data-type-handling)
  - [Artisan Commands](#artisan-commands)
- [Configuration Reference](#configuration-reference)
- [Architecture Overview](#architecture-overview)
- [Performance Benchmarks](#performance-benchmarks)

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

---

## Quick Start

### Scaffold a seeder

```bash
# generate()-based seeder
php artisan make:turbo-seeder UserTurboSeeder --table=users --count=1000000

# fromFactory()-based seeder
php artisan make:turbo-seeder UserTurboSeeder --table=users --factory=UserFactory
```

This introspects your table's columns and generates a ready-to-edit seeder in `database/seeders/`. 
No extra configuration required to get started. The default strategy works out of the box with sensible, performance-optimized settings **already configured** for you. So you just have to: 

Install → Scaffold/Write the seeder → run.

### Basic Usage

#### Basic seeding

```php
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

// 1. Using your existing factory for data generation
TurboSeeder::fromFactory(User::factory())
            ->count(50_000)
            ->run();

// 2. Using package's generator closure for data gen with more speed
$uniqueEmail = TurboData::uniqueEmail();

TurboSeeder::forTable('users')
    ->columns(['name', 'email', 'password', 'created_at'])
    ->generate(fn ($index) => [
        'name'       => "User {$index}",
        'email'      => $uniqueEmail($index),
        'password'   => TurboData::hashedPassword(),
        'created_at' => TurboData::nowOnce(),
    ])
    ->count(100000)
    ->run();
```

`forTable()`/`table()` also accepts an Eloquent Model class or instance instead of a table/string name - the table (and connection, unless overridden by `connection()`) are resolved from the model:

```php
// These are equivalent when User's table is 'users':
TurboSeeder::forTable('users')->...
TurboSeeder::forTable(User::class)->...
TurboSeeder::forTable(new User)->...
```

#### Max speed with the CSV strategy

Add one method - `useCsvStrategy()`. It uses `LOAD DATA LOCAL INFILE` (MySQL) or `COPY FROM STDIN` (PostgreSQL), typically 2-4× faster than bulk INSERT for large datasets.

```php
// with fromFactory()...
TurboSeeder::fromFactory(Post::factory())
    ->count(1_000_000)
    ->useCsvStrategy()
    ->run();

// ...and with generate()
TurboSeeder::forTable(Post::class)
    ->columns(['user_id', 'title', 'content'])
    ->generate(fn ($index) => [...])
    ->count(1_000_000)
    ->useCsvStrategy()
    ->run();
```

### Advanced Configuration

```php
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

TurboSeeder::forTable(Order::class)
    ->columns(['user_id', 'total', 'status', 'created_at'])
    ->generate(fn ($index) => [
        'user_id'    => TurboData::randomInt(1, 10000),
        'total'      => TurboData::randomFloat(2, 10.00, 999.99),
        'status'     => TurboData::weightedFrom(['pending' => 50, 'completed' => 40, 'cancelled' => 10]),
        'created_at' => TurboData::dateRange('2023-01-01', '2024-12-31'),
    ]) // Also you can use `fromFactory()` with the same options below
    ->truncate()
    ->withTimestamps()
    ->count(50000)
    ->chunkSize(3000)
    ->withProgressTracking()
    ->disableForeignKeyChecks()
    ->connection('mysql')
    ->run();
```

Know more about the two **data generation paths** and **seeding strategies** in the below [Choices You Make](#choices-you-make) section. And for converting your existing seeders, check [Migration from Standard Seeders](#migration-from-standard-seeders).

> See [examples/ExampleSeeder.php](examples/ExampleSeeder.php) and [Common Use Cases](#common-use-cases) for more examples.

---

## Choices You Make

Seeding with TurboSeeder comes down to **two independent choices**:

1. **How you generate data for each row** - Two paths: `fromFactory()` or `generate()` methods
2. **How those rows are written to the database** - Two strategies: the default bulk-`INSERT`, or the CSV strategy

These two are orthogonal: **any data path can be combined with any strategy**. Both paths run through the same high-performance engine, so pick whichever seems most convenient for your use case.

### Two Data Generation Paths

#### Path A - `fromFactory()` · reuse your existing factory

No new data definitions needed. Your existing factory is the single source of truth.

```php
use App\Models\User;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

// Exactly like User::factory()->create() - but ~100× faster at scale
TurboSeeder::fromFactory(User::factory())
    ->count(100_000)
    ->run();
```

Factory **states** work the same way:

```php
TurboSeeder::fromFactory(User::factory()->unverified()->suspended())
    ->count(5_000)
    ->run();
```

Table name and columns are auto-inferred from the model. `created_at`/`updated_at` are filled automatically when the model uses timestamps.

> **Watch out for `fake()->unique()` at large counts.** Faker's `unique()` modifier keeps every value it has generated in memory for the life of the request. So, on a factory field like `fake()->unique()->safeEmail()`, memory grows the whole run, exhausting the `memory_limit`. This is independent of TurboSeeder's own chunking/GC - it's Faker's internal state, and TurboSeeder can't reclaim it.
>
> **Way around it:** for large runs (500k+), switch to the `generate()` path where you can use `TurboData` helpers (like `uniqueEmail()`), which is index-based (O(1) memory, no growing set) and thus keeps memory usage in-limit.

---

#### Path B - `generate()` · raw closure for maximum speed

No Faker, no model instantiation - just a closure that returns an array. Use the package's built-in `TurboData` helpers for fast, easy, and deterministic data generation.

```php
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

$uniqueEmail = TurboData::uniqueEmail();

TurboSeeder::forTable(User::class)
    ->columns(['name', 'email', 'password', 'role'])
    ->generate(fn ($i) => [
        'name'     => "User {$i}",
        'email'    => $uniqueEmail($i),
        'password' => TurboData::hashedPassword(),
        'role'     => TurboData::weightedFrom(['user' => 85, 'admin' => 15]),
    ])
    ->withTimestamps()
    ->count(1_000_000)
    ->run();
```

---

#### Which one should I use?

| | `fromFactory()` | `generate()` |
|---|---|---|
| **You write** | Nothing new - reuse the factory | A small closure returning an array |
| **Data source** | Your factory `definition()` | `TurboData` helpers or your own logic |
| **Faker** | Yes (one call per row) | No - Faker-free |
| **Throughput for 1M rows** | Fast (~60-120s - Faker-bound) | Fastest (~10–60s) |
| **Factory states** | ✅ | - |
| **Best for** | <= 500k rows, or when data realism matters | Huge (>= 500k rows) dataset, maximum speed |

> **Skipped on both paths:** model events, observers, and accessors/mutators. Anything those compute (slugs, hashes, derived columns) must live in the factory definition or the generator closure.
> See [Performance Benchmarks](#performance-benchmarks) for detailed benchmark.

---

### Two Seeding Strategies

This is choice #2 - determines *how* the generated rows are written to the database. The default strategy works everywhere with zero additional configuration; while CSV requires a bit of setup but delivers maximum speed.

| Feature | Default Strategy | CSV Strategy |
|---|---|---|
| **Speed** | Fast (~20-60s for 1M) | Fastest (~10-45s for 1M)¹ |
| **Memory** | Moderate (~50-200 MB) | Minimal (~0 MB additional) |
| **Setup** | None | MySQL needs `local_infile`; PostgreSQL needs nothing |
| **Best for** | General use, remote databases | Local MySQL/PostgreSQL databases, Maximum speed |

¹ **SQLite:** CSV strategy may be _slower_ than default strategy on SQLite due to file I/O overhead. CSV shines mainly on MySQL (`LOAD DATA`) and PostgreSQL (`COPY`).

**Switching strategy** needs just a single method call - `useCsvStrategy()` / `useDefaultStrategy()`. Both seeding strategy can be combined with either data generation path.

> See [CSV Strategy Setup](#csv-strategy-setup) for the one-time MySQL configuration for the CSV strategy.

### Quick Recommendation

- Wanna use existing factory? → use `fromFactory()`
- Need 500k+ rows and maximum speed? → use `generate()`
- MySQL/PostgreSQL + 1M+ rows? → try `useCsvStrategy()`
- using SQLite? → use default strategy.
- Not sure / General use? → just start with defaults, you can always switch strategies or paths later.

---

## Migration from Standard Seeders

Converting an existing seeder takes just one or two lines.

### Before

```php
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->count(10_000)->create(); // slow, unbounded memory
    }
}
```

### After

```php
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // get the speed, keeping the factory as it is
        TurboSeeder::fromFactory(User::factory())
            ->count(10_000)
            ->run(); // and that's it!

        // Or, go with the generator path with raw closure for max speed
        $uniqueEmail = TurboData::uniqueEmail();

        TurboSeeder::forTable(User::class)
            ->columns(['name', 'email', 'password'])
            ->generate(fn ($i) => [
                'name'     => "User {$i}",
                'email'    => $uniqueEmail($i),
                'password' => TurboData::hashedPassword(),
            ])
            ->withTimestamps()
            ->count(10_000)
            ->run();
    }
}
```

---

## Common Use Cases

### Seeding Tables with Relationships

TurboSeeder supports relationship seeding in different ways depending on the relationship type:

- belongsTo / morphTo → can be resolved during generation
- hasOne / hasMany / belongsToMany / morphMany → seed tables separately

#### BelongsTo / MorphTo - using factory path

Pre-load the parent models once using the `recycle()`; the factory picks from the pool on every row with no extra DB calls:

```php
// 1. Seed parents first
TurboSeeder::fromFactory(User::factory())->count(10_000)->run();

// 2. Load parent models into a recycle pool
$users = User::all(); // or User::select('id')->get()

// 3. Seed children - recycle() replaces the factory's for() create() calls
TurboSeeder::fromFactory(Post::factory()->recycle($users))
    ->count(1_000_000)
    ->run();
```

> If your factory uses `->for(User::factory())` without `->recycle()`, TurboSeeder will emit a warning in the console output and log it - because every row would trigger an individual `User::create()` query behind the scenes.

#### BelongsTo - using generator path

Lighter on memory than loading full Eloquent models - stores only raw IDs:

```php
// Seed parents first (forTable() also accepts a Model class/instance - see note above)
TurboSeeder::forTable('users')
    ->columns(['name', 'email', 'created_at'])
    ->generate(fn ($i) => [
        'name'       => "User {$i}",
        'email'      => "user{$i}@example.com",
        'created_at' => TurboData::nowOnce(),
    ])
    ->count(50_000)
    ->run();

// fromTable() loads IDs once from the DB, then cycles - zero extra queries
$userIds = TurboData::fromTable('users');

TurboSeeder::forTable('posts')
    ->columns(['user_id', 'title', 'created_at'])
    ->generate(fn ($i) => [
        'user_id'    => $userIds($i),
        'title'      => "Post {$i}",
        'created_at' => TurboData::dateRange('2023-01-01', '2024-12-31'),
    ])
    ->count(1_000_000)
    ->useCsvStrategy()
    ->run();
```

#### HasOne / HasMany / BelongsToMany / MorphMany

These child relationships can't be wired inside a single bulk-seed call - the parent's auto-generated PK isn't available until after the bulk INSERT completes. So seed each table separately instead:

```php
// 1. Seed users
TurboSeeder::fromFactory(User::factory())
    ->count(10_000)
    ->run();

// 2. Seed posts referencing those users
$users = User::select('id')->get();

TurboSeeder::fromFactory(Post::factory()->recycle($users))
    ->count(100_000)
    ->run();

// 3. Seed pivot / child table separately (e.g. post_tags)
$postIds = TurboData::fromTable('posts');
$tagIds  = TurboData::fromTable('tags');

// Pivot tables rarely have a dedicated model, so a plain table name is used here
TurboSeeder::forTable('post_tag')
    ->columns(['post_id', 'tag_id'])
    ->generate(fn ($i) => ['post_id' => $postIds($i), 'tag_id' => $tagIds($i)])
    ->count(200_000)
    ->run();
```

This pattern is actually faster than letting Eloquent handle these relationships through `has()` - each table gets its own bulk INSERT.

### Performance Testing Scenarios

Test your application with real-world data volumes:

```php
TurboSeeder::forTable(Order::class)
    ->columns(['user_id', 'total', 'status', 'created_at'])
    ->generate(fn ($i) => [
        'user_id'    => TurboData::randomInt(1, 50_000),
        'total'      => TurboData::randomFloat(2, 9.99, 999.99),
        'status'     => TurboData::weightedFrom([
            'completed' => 65,
            'pending'   => 25,
            'cancelled' => 10,
        ]),
        'created_at' => TurboData::dateRange('2023-01-01', '2024-12-31'),
    ]) // Or you may use your existing factory with `fromFactory()` here
    ->count(500_000)
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

### Time-Series Data

When seeding time-series data, use `TurboData::sequentialDate()` for perfectly sequential timestamps with zero overhead:

```php
$eventType = TurboData::cycleFrom(['page_view', 'click', 'signup']);

TurboSeeder::forTable(AnalyticsEvent::class)
    ->columns(['event_type', 'value', 'recorded_at'])
    ->generate(fn ($i) => [
        'event_type'  => $eventType($i),
        'value'       => TurboData::randomInt(1, 100),
        'recorded_at' => TurboData::sequentialDate('2024-01-01', 'hour', $i),
    ])
    ->count(8_760) // one year of hourly data
    ->run();
```

### Dry Run (preview without writing)

```php
$result = TurboSeeder::forTable(User::class)
    ->columns(['name', 'email'])
    ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@test.com"])
    ->count(10_000)
    ->dryRun()
    ->run();

// $result->isDryRun === true, no rows were committed actually
echo "Would have inserted: {$result->recordsInserted} rows";
```

### Upsert (insert or update on conflict)

If a row with matching unique-key columns already exists, using upsert() it updates the row's non-key columns to new values instead of erroring.

```php
TurboSeeder::forTable(Product::class)
    ->columns(['sku', 'name', 'price'])
    ->generate(fn ($i) => [
        'sku'   => "SKU-{$i}",
        'name'  => "Product {$i}",
        'price' => TurboData::randomFloat(2, 1, 500),
    ])
    ->count(10_000)
    ->upsert(['sku'])  // conflict target - must match a unique index
    ->run();
```

### Things To Remember

- No model events or observers fired
- Factory relationships must use recycle()
- Seed parent tables before using fromTable()

---

## CSV Strategy Setup

### MySQL

For using the CSV strategy with MySQL, first you have to add `PDO::MYSQL_ATTR_LOCAL_INFILE` or `Pdo\Mysql::ATTR_LOCAL_INFILE` (for PHP 8.5+) to the connection options in `config/database.php`:

```php
'mysql' => [
    // ... other settings ...
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA     => env('MYSQL_ATTR_SSL_CA'),
        PDO::MYSQL_ATTR_LOCAL_INFILE => true,  // ← add this
    ]) : [],
],
```

MySQL also requires `local_infile` enabled **server-side** (off by default in MySQL 8.0+).

Verify server-side status using:

```bash
php artisan turbo-seeder:test-connection
```

If it reports `local_infile` is disabled, enable it based on your setup:

**Local MySQL setup**:

```sql
SET GLOBAL local_infile = 1;
```

Or add to `my.cnf` / `my.ini` under `[mysqld]` for permanent setup, then restart MySQL:

```ini
[mysqld]
local_infile = 1
```

**Docker**: add `--local-infile=1` to your MySQL container command:

```yaml
# docker-compose.yml
mysql:
  image: mysql:8
  command: --local-infile=1
```

> **MySQL pre-flight check:** Before generating the CSV file, TurboSeeder verifies that `LOCAL INFILE` is enabled on both the client and the server. If it isn't, it falls back to the **default strategy** immediately.

> **Security note:** Only enable `LOCAL INFILE` in trusted environments (dev/staging). Avoid enabling it in production unless strictly necessary.

### PostgreSQL

**Nothing to configure.** The CSV strategy uses a client-side `COPY ... FROM STDIN` (via PDO), so it works on managed/containerised PostgreSQL (RDS, Cloud SQL, Neon, Supabase, Docker) without superuser privileges. Only normal `INSERT` permissions are needed.

### SQLite

Supported, but the default strategy is usually faster for SQLite. Use it unless you specifically find CSV beneficial for your workload.

### Troubleshooting

If you see a warning about CSV falling back to default:

1. **MySQL** - Check the [CSV Strategy Setup](#csv-strategy-setup) guideline.
2. **All** - Check the Laravel log for a detailed error message

Note that the default strategy is still very fast and needs no configuration.

---

## API Documentation

### Entry Points

```php
// Generator path - explicitly name table and columns
TurboSeeder::forTable(User::class)
    ->columns(['name', 'email'])
    ->generate(fn ($i) => [...])
    ->count(100_000)
    ->run();

// Factory path - table and columns inferred from the model
TurboSeeder::fromFactory(User::factory()->unverified())
    ->count(100_000)
    ->run();
```

### Fluent Builder Methods

#### Data

| Method | Description |
|---|---|
| `table(string\|Model\|class-string<Model>)` | Table name, an Eloquent Model class, or a Model instance - also settable via `forTable()` (see note below) |
| `columns(array)` | Columns to seed |
| `columnsFromSchema()` | Derive columns from the table schema (opt-in; see note below) |
| `generate(Closure)` | Row generator closure - receives `$index`, returns an array |
| `withTimestamps()` | Auto-fill `created_at` / `updated_at` |
| `withoutTimestamps()` | Disable timestamp auto-fill |

> **`table()`/`forTable()` with a Model note:** Resolves the table name via the model's `getTable()`. The model's connection (`getConnectionName()`) is also applied unless `connection()` is called explicitly (wins regardless of call order). Passing an existing class that is not an Eloquent model throws an `InvalidArgumentException`.

> **`columnsFromSchema()` note:** Pulls every non-PK column from the schema builder. Opt-in (not default) - ensure your generator produces values for every NOT NULL column without a default.

> **NOT NULL coverage check:** Before every seed runs, TurboSeeder verifies that all `NOT NULL` columns without a DB default are covered by the seeded columns. If any are missing, an `InvalidArgumentException` is thrown before a single row is written. Use `withoutColumnValidation()` to skip this check when you know a column will be filled by a trigger or DB default not visible to the schema builder.

#### Seeding Behaviour

| Method | Description |
|---|---|
| `count(int)` | Number of records to seed |
| `chunkSize(int)` | Records per chunk - automatically clamped to the driver's bind-parameter limit (65,535 on MySQL/PostgreSQL; auto-detected on SQLite) |
| `truncate()` | Empty the target table before seeding (committed before the seed; cannot combine with `dryRun()`). MySQL resets `AUTO_INCREMENT`; PostgreSQL and SQLite use `DELETE` (FK-safe) which does **not** reset identity sequences - IDs continue from the previous high-water mark. |
| `commitEvery(int)` | Default strategy only: commit every N chunks instead of one wrapping transaction (for very large seeds). No-op on the CSV strategy. Cannot be combined with `useTransactions()` (throws). **Warning:** If seeding fails mid-run (generator exception, DB constraint violation, etc.) leaves already-committed chunks permanently in the table - there is no rollback path. So truncate the table and re-run if a clean retry is needed. |
| `upsert(array $uniqueBy)` | Insert-or-update on conflict; keys must match a unique/primary index (validated up front) |
| `dryRun()` | Generate and validate without committing - uses transaction rollback |

#### Strategy

| Method | Description |
|---|---|
| `useCsvStrategy()` | Native CSV import - fastest |
| `useDefaultStrategy()` | Bulk INSERT - default |
| `strategy(SeederStrategy)` | Set via enum directly |

#### Performance Tuning

| Method | Description |
|---|---|
| `disableForeignKeyChecks()` / `enableForeignKeyChecks()` | Toggle FK checks |
| `disableUniqueChecks()` / `enableUniqueChecks()` | MySQL only, opt-in. Speeds up bulk loads but can admit duplicates into unique secondary indexes - only use when data is known unique. Independent of `disableForeignKeyChecks()`. |
| `disableQueryLog()` / `enableQueryLog()` | Toggle Laravel query log |
| `useTransactions()` / `withoutTransactions()` | Toggle the wrapping transaction. CSV defaults to no wrapping transaction; default strategy wraps unless `commitEvery()` is used. `useTransactions()` (explicit enable) cannot be combined with `commitEvery()` (throws). |

#### Other

| Method | Description |
|---|---|
| `connection(string)` | Database connection name |
| `withProgressTracking()` / `withoutProgressTracking()` | Toggle progress bar |
| `retryAttempts(int)` | Retry on transient deadlock/lock-timeout (1-10; default: 3) |
| `withoutColumnValidation()` | Skip pre-seed column validation (existence check and NOT NULL coverage check) |
| `when(condition, callback)` / `unless(condition, callback)` | Conditional chaining |

---

### What `run()` Returns

Every `run()` returns an immutable `SeederResultDTO` (and throws a `RuntimeException` - wrapping the original exception - if the seed fails):

```php
$result = TurboSeeder::forTable(User::class)
    ->columns(['name', 'email'])
    ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@test.com"])
    ->count(100_000)
    ->run();

$result->recordsInserted;        // int
$result->durationSeconds;        // float
$result->isDryRun;               // bool
$result->success;                // bool
$result->getRecordsPerSecond();  // float - throughput
$result->getPeakMemoryInMB();    // float - peak memory
$result->toArray();              // everything above as an array
```

| Property / Method | Description |
|---|---|
| `success` | Whether the seed completed successfully |
| `recordsInserted` | Rows written (or that *would* be written on a dry run) |
| `durationSeconds` | Wall-clock time for the run |
| `peakMemoryBytes` | Peak memory used during the run |
| `isDryRun` | `true` when the run was a dry run |
| `errorMessage` | Failure message, or `null` |
| `exception` | The original `Throwable` on failure, or `null` - so you never lose the stack trace |
| `getRecordsPerSecond()` | Throughput as records/second |
| `getPeakMemoryInMB()` | Peak memory in MB |
| `toArray()` | The full result as an associative array |

---

### Events

TurboSeeder dispatches three events you can listen to in your `EventServiceProvider`:

- **`TurboSeederStarting`** - before seeding begins; carries `table`, `count`, `strategy`, `connection`
- **`TurboSeederCompleted`** - after a successful seed (including dry-runs); carries `table`, `result`
- **`TurboSeederFailed`** - when seeding throws; carries `table`, `connection`, `exception`

```php
use IzAhmad\TurboSeeder\Events\TurboSeederCompleted;
use IzAhmad\TurboSeeder\Events\TurboSeederFailed;

class TurboSeederListener
{
    public function handleCompleted(TurboSeederCompleted $event): void
    {
        if ($event->result->isDryRun) {
            return; // no rows were committed
        }

        // $event->table - the seeded table
        // $event->result->recordsInserted
        // $event->result->durationSeconds
    }

    public function handleFailed(TurboSeederFailed $event): void
    {
        // $event->exception - the original Throwable
    }
}
```

---

### TurboData Helpers

`TurboData` is a Faker-free data utility built for high-volume seeding. Every method is safe to call 1M+ times with no performance penalty.

> All returned values are automatically formatted via the internal `ValueFormatter`.

**Three calling conventions:**

| Convention | Which helpers | How to call |
|---|---|---|
| **Returns a closure** - call it with `$index` | `cycleFrom`, `uniqueEmail`, `uniqueUsername`, `uniqueSlug`, `uniqueUuid`, `fromTable`, `fromQuery`, `fromTableStream` | Create **outside** the generator, call inside: See [examples/ExampleSeeder.php](examples/ExampleSeeder.php) |
| **Returns a value** - call per row | `weightedFrom`, `randomFrom`, `randomInt`, `randomFloat`, `randomBool`, `nullable`, `dateRange`, `sequentialDate` | Call directly inside the generator: See [examples/ExampleSeeder.php](examples/ExampleSeeder.php) |
| **Computed once, cached** | `nowOnce`, `hashedPassword` | Call inside the generator; the value is computed once and reused every row |

> **Runtime guards:** Calling a closure-factory helper (`uniqueEmail`, `fromTable`, etc.) *inside* `generate()` instead of outside emits a log warning - the helper resets its state on every row, silently breaking uniqueness or reference-pool consistency. 
> And returning a raw `\Closure` value from the generator (forgetting to invoke it: `'email' => TurboData::uniqueEmail()` instead of `'email' => $fn($i)`) throws an `InvalidArgumentException` immediately with a fix message.

```php
use IzAhmad\TurboSeeder\Helpers\TurboData;
```

#### Value Selection

```php
// Round-robin cycling
$role = TurboData::cycleFrom(['admin', 'editor', 'viewer']); // closure: $role($index)

// Weighted random pick
$status = TurboData::weightedFrom(['active' => 70, 'pending' => 20, 'banned' => 10]);

// Uniform random pick
$method = TurboData::randomFrom(['paypal', 'bank_transfer', 'credit_card']);
```

#### Scalars

```php
$age   = TurboData::randomInt(18, 65);
$price = TurboData::randomFloat(2, 9.99, 999.99);
$flag  = TurboData::randomBool(0.8); // 80% chance true
```

#### Dates & Timestamps

```php
// Random date within a range
$date = TurboData::dateRange('2022-01-01', '2024-12-31');

// Sequential timestamps - great for time-series
$ts = TurboData::sequentialDate('2024-01-01', 'hour', $index);

// Cached - avoids calling now() once per row
'created_at' => TurboData::nowOnce()

// Hashed once - never put bcrypt() inside the generator closure
'password' => TurboData::hashedPassword()           // default: 'password'
'password' => TurboData::hashedPassword('secret')   // custom
```

#### Nullable Values

```php
// 15% chance of null; value only evaluated when non-null
$deletedAt = TurboData::nullable(0.15, fn () => now());
```

#### Seeding Related Tables

**`fromTable()`** - pluck a column from an already-seeded table once, cache it in memory, then cycle or randomly pick from it on every call. Zero extra DB queries after the first.

```php
use IzAhmad\TurboSeeder\Enums\FromTableMode;

$userIds     = TurboData::fromTable(User::class);                       // Model class - cycle (default)
$categoryIds = TurboData::fromTable('categories', 'id', 'random');      // table name - random pick
$tagIds      = TurboData::fromTable('tags', 'id', FromTableMode::RANDOM); // or the enum
$codes       = TurboData::fromTable('regions', 'code', 'cycle', 'reports'); // custom connection

TurboSeeder::forTable(Post::class)
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
>
> Like `forTable()`, the first argument accepts a table name, an Eloquent Model class, or a Model instance. The model's connection is used unless the `$connection` argument is passed explicitly.

**`fromQuery()`** - when `fromTable()` isn't flexible enough (filters, joins, ordering):

```php
$userIds = TurboData::fromQuery(
    fn () => DB::table('users')->where('active', 1)->orderBy('id')->pluck('id')->toArray()
);
```

**`fromTableStream()`** - for very large reference tables that would consume too much memory if loaded all at once:

```php
// Streams IDs one page at a time; cycles with bounded memory
$userIds = TurboData::fromTableStream(User::class, 'id', pageSize: 10_000);
```

> `fromTable()`/`fromQuery()` log a warning past ~500k values. Use `fromTableStream()` for huge reference pools.

#### Unique Values

```php
$email    = TurboData::uniqueEmail();          // u_a3f9b2c1_0@turbo.test
$username = TurboData::uniqueUsername('usr');  // usr_a3f9b2c1_0
$slug     = TurboData::uniqueSlug('My Post'); // my-post-a3f9b2c1-0
$uuid     = TurboData::uniqueUuid('ref_');    // ref_xxxxxxxx-xxxx-...
// All return closures: $email($index)
```

---

### Data Type Handling

TurboSeeder automatically formats all values returned from your generator. You never need manual type conversions.

| Input Type | Stored As |
|---|---|
| `null` | `NULL` |
| `bool` | `1` / `0` |
| `int`, `float`, `string` | unchanged |
| `DateTime` / `Carbon` | `Y-m-d H:i:s` |
| `BackedEnum` | enum value |
| `UnitEnum` | enum name |
| `array` | JSON string |
| `Collection` | JSON string |
| `object` / `stdClass` | JSON string |

```php
TurboSeeder::forTable(Product::class)
    ->columns(['status', 'metadata', 'published_at'])
    ->generate(fn ($i) => [
        'status'       => ProductStatus::Active,    // BackedEnum → raw value
        'metadata'     => ['source' => 'import'],   // array → JSON
        'published_at' => now(),                    // Carbon → Y-m-d H:i:s
    ])
    ->count(10_000)
    ->run();
```

#### Custom Type Formatters

```php
use IzAhmad\TurboSeeder\Services\ValueFormatter;

// Register in a service provider
ValueFormatter::extend(
    Money::class,
    fn ($money) => $money->getAmount()
);
```

---

### Artisan Commands

#### Scaffold a Seeder

```bash
# Generator-based seeder (columns introspected from the table)
php artisan make:turbo-seeder UsersTurboSeeder --table=users --count=1000000

# Factory-based seeder
php artisan make:turbo-seeder UsersTurboSeeder --table=users --factory=UserFactory --count=50000

# Overwrite an existing file
php artisan make:turbo-seeder UsersTurboSeeder --table=users --force
```

#### Run a Seeder

```bash
php artisan turbo-seeder:run UsersTurboSeeder
```

Shows real-time progress, detailed metrics, and errors. You can still use `php artisan db:seed` as well.

#### Benchmark

```bash
php artisan turbo-seeder:benchmark --records=1000000 --connection=mysql
```

Creates and drops its own temporary table. Refuses to run if the target table already exists, and asks for confirmation outside local/testing environments.

#### Utilities

```bash
php artisan turbo-seeder:test-connection    # verify the DB connection and check which strategies are supported
php artisan turbo-seeder:clear-cache        # remove temporary CSV files
php artisan turbo-seeder:clear-cache --all  # including subdirectories
```

---

## Configuration Reference

```bash
php artisan vendor:publish --tag="turbo-seeder-config"
```

### Chunk Sizes

```php
'default_chunk_size' => 1000,

'chunk_sizes' => [
    'mysql'  => 1000,
    'pgsql'  => 800,
    'sqlite' => 500,
],
```

Chunk size controls how many records are held in memory at once. The builder's `chunkSize()` method overrides the config and is automatically clamped to the driver's bind-parameter limit (65,535 on MySQL/PostgreSQL; auto-detected on SQLite).

**Rule of thumb:**
- Simple tables (3-5 columns): 1000-5000
- Medium tables (6-10 columns): ~1000
- Complex tables (15+ columns / large JSON): 200-1000

### Seeder Resolution

```php
'seeder_classes_namespace' => 'Database\\Seeders\\',
```

When you run `php artisan turbo-seeder:run UsersTurboSeeder` with a bare (non-qualified) class name, this namespace is prepended to resolve the class. Pass a fully-qualified name to bypass it.

### Performance

```php
'performance' => [
    'disable_query_log'    => true,   // recommended - prevents memory growth
    'disable_foreign_keys' => true,   // disable FK checks during seeding
    'disable_unique_checks' => false, // MySQL only, opt-in - see disableUniqueChecks()
    'use_transactions'     => true,   // wraps the default-strategy run
],
```

> Per-seeder builder methods (`disableForeignKeyChecks()`, `withoutTransactions()`, etc.) override these for a single run. The CSV strategy ignores `use_transactions` by default.

### Memory

```php
'memory' => [
    'limit_mb'              => 256,
    'gc_threshold_percent'  => 80,   // trigger GC at 80% usage
    'force_gc_after_chunks' => 10,   // force GC every 10 chunks
],
```

### CSV Strategy

```php
'csv_strategy' => [
    'temp_path'     => storage_path('app/turbo-seeder'),
    'buffer_size'   => 8192,
    'field_delimiter' => ',',
    'field_enclosure' => '"',
    'batch_size'    => 10000,
    'gc_frequency'  => 5,
    'reader_chunk_size_for_sqlite' => 500,
    'fallback_to_default_strategy_on_config_error' => true,
    'null_marker'   => '\\N',
],
```

- `fallback_to_default_strategy_on_config_error` - auto-switches to bulk INSERT if CSV import isn't available, so seeding always completes
- `null_marker` - string written to CSV for `null` values. If a non-null value in your data equals the marker, TurboSeeder throws rather than silently importing it as `NULL`

### Progress

```php
'progress' => [
    'enabled' => true,
],
```

### Error Display

```php
'get_error_trace_on_console'         => false,
'max_error_message_length_in_console' => 600,
```

---

## Architecture Overview

```
Data Generator → Chunk Builder → Seeding Strategy → Database
```

1. `generate()` (or the factory bridge) produces row data one chunk at a time
2. Rows are batched into memory-controlled chunks with automatic GC
3. The selected strategy writes each chunk - bulk `INSERT` or native CSV import
4. CSV files land in `storage/app/turbo-seeder/`, streamed rather than loaded fully into memory

---

## Performance Benchmarks

Measured on a MacBook M1, MySQL 8, PHP 8.5 and default chunk sizes.

### Default Strategy (Bulk Insert)

| Table | Records | Time | Peak memory |
|---|---|---|---|
| Simple (~5 cols) | 1M | ~20s | <50 MB |
| Complex (~15-20 cols) | 1M | ~60s | < 200 MB |

### CSV Strategy

| Table | Records | Time | Additional memory |
|---|---|---|---|
| Simple (~5 cols) | 1M | ~10-15s | ~0 MB |
| Complex (~15-20 cols) | 1M | ~45s | ~0 MB |

### fromFactory() Path

> The factory path for data generation calls Faker's `definition()` once per row, so throughput is CPU-bound on Faker rather than on the database. Expect it to be *slower* than the raw `generate()` path (numbers shown above) - but **still far faster** than typical seeding with `Model::factory()->create()` - since it *skips* Eloquent, model events, and per-row inserts. For factory path -

| Table | Records | Time | Additional memory |
|---|---|---|---|
| Simple (~5 cols) | 1M | ~60-70s | ~0 MB |

> See [Two Data Generation Paths](#two-data-generation-paths) for more info.

Use `fromFactory()` for convenience at moderate volumes (up to ~500k rows). If needed more records than 500k, you can switch to `generate()` when you need maximum speed.

> Reproduce results on your own machine: `php artisan turbo-seeder:benchmark`

---

## Testing

```bash
composer test
```

Pest PHP - SQLite, MySQL, and PostgreSQL supported.

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
