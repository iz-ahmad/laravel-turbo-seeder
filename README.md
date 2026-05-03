# Laravel Turbo Seeder

[![Tests](https://github.com/iz-ahmad/laravel-turbo-seeder/actions/workflows/run-tests.yml/badge.svg)](https://github.com/iz-ahmad/laravel-turbo-seeder/actions/workflows/run-tests.yml) [![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-blue.svg)](https://php.net) [![Laravel Version](https://img.shields.io/badge/laravel-10--13-red.svg)](https://laravel.com)
<!-- [![Latest Stable Version](https://img.shields.io/packagist/v/iz-ahmad/laravel-turbo-seeder.svg)](https://packagist.org/packages/iz-ahmad/laravel-turbo-seeder) -->
<!-- [![Total Downloads](https://img.shields.io/packagist/dt/iz-ahmad/laravel-turbo-seeder.svg)](https://packagist.org/packages/iz-ahmad/laravel-turbo-seeder) -->
<!-- [![License](https://img.shields.io/packagist/l/iz-ahmad/laravel-turbo-seeder.svg)](LICENSE.md) -->

**Blazing fast database seeder for Laravel - seed millions of records in seconds; not minutes.**

Laravel Turbo Seeder is a high-performance seeding package built for large-scale data generation (1M+ records) with very minimal time and memory usage. Ideal for testing applications with production-sized datasets.

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

1. **No Eloquent overhead**: raw queries only (no model events, no Faker)
2. **Bulk inserts**: multi-row `INSERT` instead of row-by-row
3. **Native CSV imports**: `LOAD DATA` / `COPY` for maximum throughput
4. **Smart chunking**: controlled memory with automatic garbage collection
5. **Minimal overhead**: foreign key checks & query logging disabled automatically
6. **Streaming I/O**: CSV handled via streams, not loaded fully into memory

---

## Features At A Glance

* **Lightning Fast**: 1M records in 15–60 seconds (table-complexity dependent)
* **Memory Efficient**: under 200MB peak
* **Multi-Database**: MySQL, PostgreSQL, SQLite
* **Two Strategies**: bulk insert or native CSV import
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
- [Quick Start](#quick-start)
  - [Basic Usage](#basic-usage)
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

## Quick Start

No extra configuration required to get started.  
The default strategy works out of the box with sensible, performance-optimized settings **already configured** for you. So you just have to: 

Install → write your generator → run.

### Basic Usage

```php
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

$uniqueEmail = TurboData::uniqueEmail();

TurboSeeder::create('users')
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

See [src/Examples/ExampleSeeder.php](src/Examples/ExampleSeeder.php) and [Common Use Cases](#common-use-cases) for more examples.

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
| **Setup** | No configuration required | Requires some database config |
| **Best For** | Remote databases, general use | Local databases, max speed |
| **Compatibility** | All databases | MySQL, PostgreSQL, SQLite |

¹ **SQLite Note:** CSV strategy may be _slower_ than default strategy on SQLite due to file I/O overhead. CSV shines mainly on MySQL (`LOAD DATA`) and PostgreSQL (`COPY`).

**Recommendation**: 
- **MySQL/PostgreSQL**: Use CSV strategy for 1M+ records
- **SQLite**: Use default strategy
- **General use**: Start with default. Switch to CSV strategy for maximum speed on local databases.

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

For PostgreSQL, the CSV strategy uses the `COPY` command which requires:

1. **File Access** - PostgreSQL server must have read access to `storage/app/turbo-seeder/`
2. **User Privileges** - Database user must have `COPY` privileges on target tables
3. **Server Location** - For remote servers, ensure CSV file path is accessible

**Note:** For local PostgreSQL installations, CSV strategy typically works without additional configuration. For remote servers, you may need network file sharing or use the default strategy.

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

### After (Turbo Seeder)

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
            ->columns(['name', 'email', 'password', 'created_at'])
            ->generate(fn ($i) => [
                'name'       => "User {$i}",
                'email'      => $uniqueEmail($i),
                'password'   => TurboData::hashedPassword(),
                'created_at' => TurboData::nowOnce(),
            ])
            ->count(10000)
            ->run();
    }
}
```

---

## API Documentation

### Fluent API Methods

#### Core Methods

- `table(string $table)` - Set the table name. Accepts plain names (`users`) and schema-qualified names (`public.users`, `myschema.my_table`). Names must start with a letter or underscore and contain only letters, digits, and underscores. **SQLite note:** schema-qualified names require an [ATTACHed database](https://www.sqlite.org/lang_attach.html) alias; without ATTACH the query will fail at runtime with "no such table".
- `columns(array $columns)` - Set columns to seed
- `generate(Closure $generator)` - Set data generator function
- `count(int $count)` - Set number of records to seed
- `run()` - Execute the seeding operation

#### Strategy Methods

- `useCsvStrategy()` - Native CSV file import (fastest)
- `useDefaultStrategy()` - Bulk INSERT (default)
- `strategy(SeederStrategy $strategy)` - Set via enum directly

#### Configuration Methods

- `connection(string $connection)` - Database connection to use
- `chunkSize(int $size)` - Records per chunk
- `withProgressTracking()` / `withoutProgressTracking()` - Toggle progress bar
- `disableForeignKeyChecks()` / `enableForeignKeyChecks()` - Toggle FK checks
- `disableQueryLog()` / `enableQueryLog()` - Toggle query logging
- `useTransactions()` / `withoutTransactions()` - Toggle transactions
- `options(array $options)` - Merge custom options
- `when(bool|callable $condition, callable $callback, ?callable $default)` - Conditional chaining
- `unless(bool|callable $condition, callable $callback, ?callable $default)` - Inverse conditional

#### Advanced Methods

- `dryRun(bool $enabled = true)` - Generate and validate data without committing. Uses transaction rollback; `$result->isDryRun` will be `true`.
> **Do not combine this with `withoutTransactions()`**; because without a transaction, there is nothing to roll back and rows will be permanently written.

- `upsert(array $uniqueBy)` - On conflict, update non-key columns. Uses `ON DUPLICATE KEY UPDATE` (MySQL), `ON CONFLICT DO UPDATE SET` (PostgreSQL / SQLite 3.24+). Keys must be a subset of declared columns and must form a unique constraint on the table.

- `retryAttempts(int $attempts)` - Retry on transient deadlock / lock-timeout failures (SQLSTATE 40001, MySQL 1205) with exponential backoff. Accepts 1–10; defaults to 3.

- `withoutColumnValidation()` - Skip the pre-seed schema check that validates declared columns exist on the table.

#### Events

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
$userIds     = TurboData::fromTable('users');                      // cycle (default)
$categoryIds = TurboData::fromTable('categories', 'id', 'random'); // random pick
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
php artisan turbo-seeder:benchmark [--connection=] [--table=] [--records=]
```

**Options:**
- `--connection=` - Database connection
- `--table=` - Table name (default: benchmark_test)
- `--records=` - Number of records (default: 10000)

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
    'disable_query_log' => true,      // Disable Laravel query logging (recommended)
    'disable_foreign_keys' => true,   // Disable foreign key checks during seeding
    'use_transactions' => true,       // Wrap operations in transactions
],
```

### CSV Strategy Configuration

Settings for CSV-based seeding:

```php
'csv_strategy' => [
    'enabled' => true,                                   // Enable CSV strategy
    'temp_path' => storage_path('app/turbo-seeder'),     // Directory for temporary CSV files
    'buffer_size' => 8192,                               // File write buffer size (bytes)
    'line_terminator' => "\n",                           // CSV line ending
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
- `null_marker` - The string written to CSV for `null` values. The default `\N` matches MySQL and PostgreSQL native CSV null conventions. Only change this if your data legitimately contains the literal string `\N`.

### Progress Tracking

Configure progress bar display:

```php
'progress' => [
    'enabled' => true,           // Enable progress tracking by default
    'update_frequency' => 1000,  // Update progress every 1000 records
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
