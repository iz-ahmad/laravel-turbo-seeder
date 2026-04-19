# Laravel Turbo Seeder

[![Tests](https://github.com/iz-ahmad/laravel-turbo-seeder/actions/workflows/run-tests.yml/badge.svg)](https://github.com/iz-ahmad/laravel-turbo-seeder/actions/workflows/run-tests.yml)

**Seed millions of Laravel database records in seconds — not minutes.**

![Laravel Turbo Seeder Demo](https://raw.githubusercontent.com/iz-ahmad/laravel-turbo-seeder/master/images/banner.png)

---

## 📑 Table of Contents

- [Why Turbo Seeder?](#-why-turbo-seeder)
- [Features](#-features)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Quick Start](#-quick-start)
  - [Basic Usage](#basic-usage)
  - [CSV Strategy (Fastest)](#csv-strategy-fastest)
  - [Advanced Configuration](#advanced-configuration)
- [API Documentation](#-api-documentation)
  - [Fluent API Methods](#fluent-api-methods)
  - [Using in Seeders](#using-in-seeders)
  - [TurboData Helpers](#turbodata-helpers)
  - [Artisan Commands](#artisan-commands)
- [Configuration Reference](#%EF%B8%8F-configuration-reference)
- [CSV Strategy Setup](#-csv-strategy-setup)
- [Performance Benchmarks](#-performance-benchmarks)
- [Testing](#-testing)
- [Contributing](#-contributing)
- [Security](#-security)
- [Changelog](#-changelog)
- [License](#-license)
- [Credits](#-credits)

---

## 💡 Why Turbo Seeder?

Traditional Laravel seeders crawl at scale. When you need 500K–1M rows for realistic performance testing, they become a productivity killer — think 20–30 minutes per run.

**Turbo Seeder brings that down to 15–60 seconds for ~1M records**, so you can:

- Test at production-scale data volumes
- Catch slow queries before users do
- Iterate fast without seeding being a bottleneck

### How It Achieves This Speed

1. **No Eloquent overhead** — raw queries only; no model events, no Faker
2. **Bulk operations** — multi-row `INSERT` statements instead of row-by-row
3. **CSV import** — native `LOAD DATA` / `COPY` commands for maximum throughput
4. **Smart chunking** — controlled memory use with automatic garbage collection
5. **Minimal setup** — foreign key checks and query logging disabled automatically

---

## 🚀 Features

- ⚡ **Lightning Fast** — ~1M records in 15–60 seconds
- 💾 **Memory Efficient** — under 200 MB peak
- 🗄️ **Multi-Database** — MySQL, PostgreSQL, SQLite
- 📊 **Two Strategies** — bulk insert and native CSV file import
- 🎯 **Fluent API** — clean, chainable interface
- 🧩 **TurboData Helpers** — Faker-free data generation: weighted picks, date ranges, FK pools, unique values
- 📈 **Progress Tracking** — real-time progress bars with metrics
- 🔧 **Highly Configurable** — chunk sizes, transactions, upserts, retries, dry-run, and more
- ✅ **Fully Tested** — Pest PHP test suite
- 🎨 **Laravel 11–13 Compatible**

---

## 📋 Requirements

- PHP 8.2+
- Laravel 11.x, 12.x, or 13.x
- MySQL 5.7+, PostgreSQL 9.6+, or SQLite 3.24+

---

## 📦 Installation

```bash
composer require iz-ahmad/laravel-turbo-seeder
```

The package auto-registers itself. Optionally publish the config:

```bash
php artisan vendor:publish --tag="turbo-seeder-config"
```

---

## 🎯 Quick Start

### Basic Usage

```php
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

TurboSeeder::create('users')
    ->columns(['name', 'email', 'created_at'])
    ->generate(fn ($index) => [
        'name'       => "User {$index}",
        'email'      => "user{$index}@example.com",
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

See [src/Examples/ExampleSeeder.php](src/Examples/ExampleSeeder.php) for more examples.

---

<details>
<summary><h3>📚 API Documentation</h3></summary>

### Fluent API Methods

#### Core Methods

- `table(string $table)` — Set the table name
- `columns(array $columns)` — Set columns to seed
- `generate(Closure $generator)` — Set data generator (receives `$index`)
- `count(int $count)` — Number of records to seed
- `run()` — Execute and return a `SeederResultDTO`

#### Strategy Methods

- `useCsvStrategy()` — Native CSV file import (fastest)
- `useDefaultStrategy()` — Bulk INSERT (default)
- `strategy(SeederStrategy $strategy)` — Set via enum directly

#### Configuration Methods

- `connection(string $connection)` — Database connection to use
- `chunkSize(int $size)` — Records per chunk
- `withProgressTracking()` / `withoutProgressTracking()` — Toggle progress bar
- `disableForeignKeyChecks()` / `enableForeignKeyChecks()` — Toggle FK checks
- `disableQueryLog()` / `enableQueryLog()` — Toggle query logging
- `useTransactions()` / `withoutTransactions()` — Toggle transactions
- `options(array $options)` — Merge custom options
- `when(bool|callable $condition, callable $callback, ?callable $default)` — Conditional chaining
- `unless(bool|callable $condition, callable $callback, ?callable $default)` — Inverse conditional

#### Advanced Methods

- `dryRun(bool $enabled = true)` — Generate and validate data without committing. Uses transaction rollback; `$result->isDryRun` will be `true`. **Do not combine with `withoutTransactions()`** — without a transaction there is nothing to roll back and rows will be permanently written.

- `upsert(array $uniqueBy)` — On conflict, update non-key columns. Uses `ON DUPLICATE KEY UPDATE` (MySQL), `ON CONFLICT DO UPDATE SET` (PostgreSQL / SQLite 3.24+). Keys must be a subset of declared columns and must form a unique constraint on the table.

- `retryAttempts(int $attempts)` — Retry on transient deadlock / lock-timeout failures (SQLSTATE 40001, MySQL 1205) with exponential backoff. Accepts 1–10; defaults to 3.

- `withoutColumnValidation()` — Skip the pre-seed schema check that validates declared columns exist on the table.

#### Events

`TurboSeederCompleted` is dispatched after every successful seed, including dry-runs:

```php
use IzAhmad\TurboSeeder\Events\TurboSeederCompleted;

Event::listen(TurboSeederCompleted::class, function (TurboSeederCompleted $event) {
    // $event->table  — the seeded table name
    // $event->result — SeederResultDTO (includes isDryRun flag)

    if ($event->result->isDryRun) {
        return; // no rows were committed
    }

    Cache::forget("table:{$event->table}");
});
```

> **Note:** Always check `$event->result->isDryRun` before acting on the assumption that rows were committed. The event is **not** dispatched when seeding fails.

---

### Using in Seeders

Use the `UsesTurboSeeder` trait for quick helpers inside standard Laravel seeders:

```php
use Illuminate\Database\Seeder;
use IzAhmad\TurboSeeder\Traits\UsesTurboSeeder;

class DatabaseSeeder extends Seeder
{
    use UsesTurboSeeder;

    public function run(): void
    {
        $this->quickSeed(
            'users',
            ['name', 'email'],
            fn ($i) => ['name' => "User {$i}", 'email' => "user{$i}@test.com"],
            10000
        );

        $this->quickCsvSeed(
            'posts',
            ['user_id', 'title'],
            fn ($i) => ['user_id' => ($i % 10000) + 1, 'title' => "Post {$i}"],
            100000
        );
    }
}
```

---

### TurboData Helpers

`TurboData` is a Faker-free data generation utility designed for high-volume seeding. Every method is safe to call 1M+ times.

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

// Sequential timestamps — good for time-series data
$ts = TurboData::sequentialDate('2024-01-01', 'hour', $index);

// Use nowOnce() inside generators — avoids calling now() 1M times
'created_at' => TurboData::nowOnce()
```

#### Nullable Values

```php
// 15% chance of null; value only evaluated when not null
$deletedAt = TurboData::nullable(0.15, fn () => now());
```

#### Foreign Key Pools

```php
// Loads IDs once from DB, cycles deterministically — works with UUID PKs and ID gaps
$userIds = TurboData::fromPool(
    fn () => DB::table('users')->pluck('id')->toArray()
);

TurboSeeder::create('posts')
    ->columns(['user_id', 'title'])
    ->generate(fn ($i) => [
        'user_id' => $userIds($i),
        'title'   => "Post {$i}",
    ])
    ->count(1_000_000)
    ->run();
```

#### Unique Values

```php
$email = TurboData::uniqueEmail();         // u_a3f9b2c1_0@turbo.test
$user  = TurboData::uniqueUsername('usr'); // usr_a3f9b2c1_0
$slug  = TurboData::uniqueSlug('My Post'); // my-post-a3f9b2c1-0
$uuid  = TurboData::uniqueUuid('ref_');    // ref_xxxxxxxx-xxxx-...
// All return closures: $email($index)
```

#### Custom Type Formatters

Register custom handlers for your own value objects:

```php
use IzAhmad\TurboSeeder\Services\ValueFormatter;

ValueFormatter::extend(Money::class, fn ($money) => $money->getAmount());
```

---

### Artisan Commands

#### Run a Seeder

```bash
php artisan turbo-seeder:run YourSeederClass
```

Compatible with Laravel's native `php artisan db:seed`. The `turbo-seeder:run` command additionally shows real-time progress and detailed performance metrics.

#### Benchmark

```bash
php artisan turbo-seeder:benchmark [--connection=] [--table=] [--records=]
```

#### Test Connection

```bash
php artisan turbo-seeder:test-connection
```

#### Clear Cache

```bash
php artisan turbo-seeder:clear-cache [--all]
```

Removes temporary CSV files created during seeding.

</details>

---

<details>
<summary><h3>⚙️ Configuration Reference</h3></summary>

Publish the config for full control:

```bash
php artisan vendor:publish --tag="turbo-seeder-config"
```

### Chunk Sizes

Determines how many records are held in memory per insert batch.

**Priority order:** `chunkSize()` method → driver-specific config → `default_chunk_size`

```php
'default_chunk_size' => 1000,

'chunk_sizes' => [
    'mysql'  => 1000,
    'pgsql'  => 800,
    'sqlite' => 500,
],
```

**Guidelines:**
- Simple tables (3–5 cols): 1000–5000
- Medium tables (6–10 cols): ~1000
- Complex tables (15+ cols / large text/JSON): 200–1000
- 1M+ records with high memory pressure: use CSV strategy or reduce chunk size

### Memory Management

```php
'memory' => [
    'limit_mb'              => 256,
    'gc_threshold_percent'  => 80,
    'force_gc_after_chunks' => 10,
],
```

### Performance

```php
'performance' => [
    'disable_query_log'    => true,
    'disable_foreign_keys' => true,
    'use_transactions'     => true,
],
```

### CSV Strategy

```php
'csv_strategy' => [
    'enabled'              => true,
    'temp_path'            => storage_path('app/turbo-seeder'),
    'buffer_size'          => 8192,
    'line_terminator'      => "\n",
    'field_delimiter'      => ',',
    'field_enclosure'      => '"',
    'batch_size'           => 10000,
    'gc_frequency'         => 5,
    'reader_chunk_size_for_sqlite'                 => 500,
    'fallback_to_default_strategy_on_config_error' => true,
    'null_marker'          => '\\N',
],
```

- `fallback_to_default_strategy_on_config_error` — auto-switches to bulk insert if CSV import fails due to missing DB config.
- `null_marker` — sentinel written for `null` values. Default `\N` matches MySQL/PostgreSQL CSV conventions. Only change if your data contains the literal string `\N`.

### Progress Tracking

```php
'progress' => [
    'enabled'          => true,
    'update_frequency' => 1000,
],
```

### Error Handling

```php
'get_error_trace_on_console'          => false,
'max_error_message_length_in_console' => 600,
```

### Seeder Namespace

```php
'seeder_classes_namespace' => 'Database\\Seeders\\',
```

Allows short names: `php artisan turbo-seeder:run UserSeeder` instead of the fully qualified class.

</details>

---

<details>
<summary><h3>🔧 CSV Strategy Setup</h3></summary>

The CSV strategy uses native database import commands (`LOAD DATA`, `COPY`) for maximum throughput. It requires a small one-time setup.

### Automatic Fallback

If CSV strategy is not configured, TurboSeeder automatically falls back to the default bulk insert strategy with a warning message. Seeding always completes.

### MySQL

Add `PDO::MYSQL_ATTR_LOCAL_INFILE` to your database connection in `config/database.php`:

```php
'mysql' => [
    // ...
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA       => env('MYSQL_ATTR_SSL_CA'),
        PDO::MYSQL_ATTR_LOCAL_INFILE => true,
    ]) : [],
],
```

> **Security note:** `LOAD DATA LOCAL INFILE` allows MySQL to read local files. Only enable this in trusted environments (local/staging). Avoid in production unless strictly necessary.

### PostgreSQL

The CSV strategy uses the `COPY` command. Requirements:

1. PostgreSQL server must have read access to `storage/app/turbo-seeder/`
2. Database user must have `COPY` privileges on target tables

For local installations this typically works out of the box. For remote servers, use the default strategy or set up network file access.

### Troubleshooting

If you see a CSV fallback warning:

1. **MySQL** — verify `PDO::MYSQL_ATTR_LOCAL_INFILE => true` in `config/database.php`
2. **PostgreSQL** — check file permissions and `COPY` privileges
3. **Both** — check application logs for the underlying error

</details>

---

<details>
<summary><h3>📊 Performance Benchmarks</h3></summary>

Measured on a modern local machine with MySQL and default chunk sizes.

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

Best for: local databases, maximum throughput where `LOAD DATA` / `COPY` can be enabled.

> Results vary by hardware, DB engine/version, network latency, and chunk size.

</details>

---

<details>
<summary><h3>🧪 Testing</h3></summary>

```bash
composer test
```

```bash
composer test-coverage
```

</details>

---

<details>
<summary><h3>🤝 Contributing</h3></summary>

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

</details>

---

<details>
<summary><h3>🔒 Security</h3></summary>

If you discover a security issue, please email `n.ahmad.web.cit22@gmail.com` instead of opening a public issue.

</details>

---

<details>
<summary><h3>📝 Changelog</h3></summary>

Please see [CHANGELOG.md](CHANGELOG.md) for recent changes.

</details>

---

<details>
<summary><h3>📄 License</h3></summary>

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.

</details>

---

<details>
<summary><h3>🙏 Credits</h3></summary>

- All Contributors

**Made with ❤️ for the Laravel community**

</details>
