# Laravel Turbo Seeder

<!-- [![Latest Version on Packagist](https://img.shields.io/packagist/v/iz-ahmad/laravel-turbo-seeder.svg?style=flat-square)](https://packagist.org/packages/iz-ahmad/laravel-turbo-seeder)
[![Total Downloads](https://img.shields.io/packagist/dt/iz-ahmad/laravel-turbo-seeder.svg?style=flat-square)](https://packagist.org/packages/iz-ahmad/laravel-turbo-seeder)
[![Tests](https://github.com/iz-ahmad/laravel-turbo-seeder/actions/workflows/run-tests.yml/badge.svg)](https://github.com/iz-ahmad/laravel-turbo-seeder/actions/workflows/run-tests.yml)
[![License](https://img.shields.io/packagist/l/iz-ahmad/laravel-turbo-seeder.svg?style=flat-square)](LICENSE.md) -->

**Blazing fast database seeder for Laravel - seed millions of records in minutes, or even seconds!**

Laravel Turbo Seeder is a high-performance database seeding package that allows you to seed massive amounts of data (1M+ records) in just 2-3 minutes, compared to the traditional 40+ minutes. Perfect for testing applications with realistic data volumes.

---

## 📑 Table of Contents

- [Why Turbo Seeder?](#-why-turbo-seeder)
- [Main Features](#-main-features)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Quick Start](#-quick-start)
  - [Basic Usage](#basic-usage)
  - [CSV Strategy (Fastest)](#using-csv-strategy-fastest)
  - [Advanced Configuration](#advanced-configuration)
- [API Documentation](#-api-documentation)
  - [Fluent API Methods](#fluent-api-methods)
  - [Using in Seeders](#using-in-seeders)
    - [Generating Unique Values](#generating-unique-values)
  - [Artisan Commands](#artisan-commands)
- [Configuration Reference](#%EF%B8%8F-configuration-reference)
  - [Chunk Sizes](#chunk-sizes)

  - [Memory Management](#memory-management)
  - [Performance Optimizations](#performance-optimizations)
  - [CSV Strategy](#csv-strategy-configuration)
  - [Progress Tracking](#progress-tracking)
  - [Error Handling](#error-handling)
  - [Seeder Namespace](#seeder-namespace)
- [CSV Strategy Setup](#-csv-strategy-setup)
  - [MySQL Configuration](#mysql-configuration)
  - [PostgreSQL Configuration](#postgresql-configuration)
  - [Automatic Fallback](#automatic-fallback)
  - [Troubleshooting](#troubleshooting)
- [Performance Benchmarks](#-performance-benchmarks)
- [Testing](#-testing)
- [Contributing](#-contributing)
- [Security](#-security)
- [Changelog](#-changelog)
- [License](#-license)
- [Credits](#-credits)

---

## 💡 Why Turbo Seeder?

Because waiting **40+ minutes** for seeders to finish is not "testing" - it's nearly a **punishment**!

When you need to test your app with **real, production-scale data** (hundreds of thousands or millions of rows), traditional Laravel seeders crawl… and your flow dies with them.

**Turbo Seeder makes seeding great again (in a good way) ;-).**
What used to take **40+ minutes** now finishes in **2–3 minutes** for ~**1M records**.
And what used to take 5-10 minutes now finishes in **1-2 minutes** for ~**100K records**. ...Approximately.

No coffee breaks. No tab-switching. No "I'll test later". So you can:

* Test performance like it's production
* Catch slow queries before users do
* Iterate without killing your dev flow

### How We Achieve This Speed:

1. **Bypassing Eloquent** - Uses raw database queries instead of ORM overhead
2. **Bulk Operations** - Multi-row INSERT statements instead of individual inserts
3. **Database Optimizations** - Disables constraints, uses transactions efficiently
4. **CSV Import** - For maximum speed, uses native database CSV import commands
5. **Memory Management** - Efficient chunking and garbage collection
6. **Streaming** - CSV files are written/read in streams, not loaded into memory

### Performance Comparison:

- **Default Strategy (Bulk Insert)**: ~2-3 minutes for 1M records
- **CSV Strategy (File Import)**: ~1-2 minutes for 1M records

---

## 🚀 Main Features

- ⚡ **Lightning Fast** - Seed 1M records in 2-3 minutes
- 💾 **Memory Efficient** - Uses less than 256MB peak memory
- 🗄️ **Multi-Database Support** - MySQL, PostgreSQL, SQLite
- 📊 **Two Strategies** - Default (bulk insert) and CSV (file-based import)
- 🎯 **Fluent API** - Beautiful, chainable interface
- 📈 **Progress Tracking** - Real-time progress bars with metrics
- 🔧 **Highly Configurable** - Fine-tune performance settings
- ✅ **Fully Tested** - 110+ tests with Pest PHP
- 🎨 **Laravel 11/12 Compatible** - Works with latest Laravel versions

**Perfect for:**
- ✅ Performance testing with realistic data volumes
- ✅ Development environments needing large datasets
- ✅ CI/CD pipelines requiring fast seeding
- ✅ Testing database performance and queries
- ✅ Generating test data for load testing

---

## 📋 Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x, or 12.x
- MySQL 5.7+, PostgreSQL 9.6+, or SQLite 3.8+

---

## 📦 Installation

Install via Composer:

```bash
composer require iz-ahmad/laravel-turbo-seeder
```

The package will automatically register itself.

### Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag="turbo-seeder-config"
```

This creates `config/turbo-seeder.php` in your project.

---

## 🎯 Quick Start

### Basic Usage

```php
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

TurboSeeder::create('users')
    ->columns(['name', 'email', 'created_at'])
    ->generate(fn ($index) => [
        'name' => "User {$index}",
        'email' => "user{$index}@example.com",
        'created_at' => now(),
    ])
    ->count(100000)
    ->run();
```

### Using CSV Strategy (Fastest)

```php
TurboSeeder::create('posts')
    ->columns(['user_id', 'title', 'content', 'created_at'])
    ->generate(fn ($index) => [
        'user_id' => ($index % 10000) + 1,
        'title' => "Post {$index}",
        'content' => "Content for post {$index}",
        'created_at' => now(),
    ])
    ->count(1000000)
    ->useCsvStrategy()
    ->run();
```

### Advanced Configuration

```php
TurboSeeder::create('orders')
    ->columns(['user_id', 'total', 'status', 'created_at'])
    ->generate(fn ($index) => [
        'user_id' => ($index % 10000) + 1,
        'total' => random_int(1000, 99999) / 100,
        'status' => ['pending', 'completed', 'cancelled'][random_int(0, 2)],
        'created_at' => now(),
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
<summary><h2>📚 API Documentation</h2></summary>

### Fluent API Methods

#### Core Methods

- `table(string $table)` - Set the table name
- `columns(array $columns)` - Set columns to seed
- `generate(Closure $generator)` - Set data generator function
- `count(int $count)` - Set number of records to seed
- `run()` - Execute the seeding operation

#### Strategy Methods

- `useCsvStrategy()` - Use CSV-based import (fastest)
- `useDefaultStrategy()` - Use bulk insert (default)
- `strategy(SeederStrategy $strategy)` - Set specific strategy

#### Configuration Methods

- `connection(string $connection)` - Set database connection
- `chunkSize(int $size)` - Custom chunk size
- `withProgressTracking()` / `withoutProgressTracking()` - Progress bar control
- `disableForeignKeyChecks()` / `enableForeignKeyChecks()` - FK checks control
- `disableQueryLog()` / `enableQueryLog()` - Query log control
- `useTransactions()` / `withoutTransactions()` - Transaction control
- `options(array $options)` - Set custom options

#### Conditional Methods

- `when(bool|callable $condition, callable $callback, ?callable $default = null)` - Conditional execution
- `unless(bool|callable $condition, callable $callback, ?callable $default = null)` - Inverse conditional

### Using in Seeders

Use the `UsesTurboSeeder` trait in your seeders:

```php
use Illuminate\Database\Seeder;
use IzAhmad\TurboSeeder\Traits\UsesTurboSeeder;

class DatabaseSeeder extends Seeder
{
    use UsesTurboSeeder;

    public function run(): void
    {
        // Quick seed helper
        $this->quickSeed(
            'users',
            ['name', 'email'],
            fn ($i) => [
                'name' => "User {$i}",
                'email' => "user{$i}@test.com"
            ],
            10000
        );

        // Quick CSV seed
        $this->quickCsvSeed(
            'posts',
            ['user_id', 'title'],
            fn ($i) => [
                'user_id' => ($i % 10000) + 1,
                'title' => "Post {$i}"
            ],
            100000
        );
    }
}
```

### Generating Unique Values

When seeding tables with unique constraints (like `email`, `username`, etc.), you need to ensure values are unique. TurboSeeder provides helper methods to generate unique values:

**Available Methods:**

- `uniqueEmail(?string $prefix = null)` - Generates unique email addresses
- `uniqueValue(?string $prefix = null)` - Generates unique string values
- `uniqueUuid(string $prefix = '')` - Generates unique UUID-based values

**Example Usage:**

```php
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use IzAhmad\TurboSeeder\Traits\UsesTurboSeeder;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

class UserSeeder extends Seeder
{
    use UsesTurboSeeder;

    public function run(): void
    {
        // You can clear table before seeding to avoid duplicate entry errors
        DB::table('users')->delete();

        // Generate unique email generator
        $uniqueEmail = $this->uniqueEmail('user');
        $uniqueUsername = $this->uniqueValue('username');

        TurboSeeder::create('users')
            ->columns(['name', 'email', 'username', 'created_at'])
            ->generate(fn($index) => [
                'name' => "User {$index}",
                'email' => $uniqueEmail($index),        // Always unique!
                'username' => $uniqueUsername($index),   // Always unique!
                'created_at' => now(),
            ])
            ->count(100000)
            ->run();
    }
}
```

**How It Works:**

- `uniqueEmail()` generates emails like: `user0_1234567890_abcd@test.com`
- `uniqueValue()` generates values like: `unique_0_1234567890_abcd`
- Both use timestamp + random string to ensure uniqueness
- `uniqueUuid()` generates full UUIDs for maximum uniqueness, with or without prefix

**Tip:** You can also clear the table before seeding using `DB::table('table_name')->delete()` or `DB::table('table_name')->truncate()` to avoid duplicate entry errors, especially useful when re-running seeders during development.

### Artisan Commands

#### Run Seeder

```bash
php artisan turbo-seeder:run YourSeederClass
```

**Options:**
- `--class=` - Seeder class name
- `--connection=` - Database connection
- `--strategy=` - Strategy (default or csv)
- `--count=` - Number of records
- `--chunk=` - Custom chunk size
- `--no-progress` - Disable progress bar
- `--no-fk-checks` - Disable foreign key checks
- `--no-transactions` - Disable transactions

#### Benchmark Performance

```bash
php artisan turbo-seeder:benchmark
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

Clear all temporary files made by the package:

```bash
php artisan turbo-seeder:clear-cache
```

**Options:**
- `--all` - Clear all temporary files including subdirectories

</details>

---

<details>
<summary><h2>⚙️ Configuration Reference</h2></summary>

### Chunk Sizes

Chunk size determines how many records are inserted (processed in memory) at once. This directly impacts memory usage and performance.

**Config Priority Order:**
1. **Custom chunk size** (set via `->chunkSize()` in the seeder class using our fluent API) - Highest priority
2. **Database-specific chunk size** (from `chunk_sizes.{database_driver}` config) - Medium priority
3. **Default chunk size** (from `default_chunk_size` config) - Fallback

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

- **More columns = smaller chunk size needed**: Tables with 15+ columns or large fields require smaller chunks to stay within memory limits
- **Fewer columns = larger chunk size possible**: Simple tables (3-5 columns) can handle larger chunks efficiently
- **Default strategy**: More memory-intensive than CSV strategy, so consider smaller chunks for large datasets
- **CSV strategy**: More memory-efficient, can handle larger chunks even with many columns. Because it uses the database's native CSV import command.

**Recommendations for chunk size:**

- **Simple tables (3-5 columns)**: 1000 - 5000
- **Medium tables (6-10 columns)**: ~ 1000
- **Complex tables (15+ columns, large text/JSON)**: 200 - 1000
- **For very large datasets (1M+ records)**: Consider CSV strategy or reduce chunk size to smaller values if memory limit is exhausted.

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
    'enabled' => true,                                    // Enable CSV strategy
    'temp_path' => storage_path('app/turbo-seeder'),     // Directory for temporary CSV files
    'buffer_size' => 8192,                               // File write buffer size (bytes)
    'line_terminator' => "\n",                           // CSV line ending
    'field_delimiter' => ',',                            // CSV field separator
    'field_enclosure' => '"',                            // CSV field enclosure
    'batch_size' => 10000,                               // Records per CSV batch
    'gc_frequency' => 5,                                 // Run GC every N batches
    'reader_chunk_size_for_sqlite' => 500,               // SQLite CSV read chunk size
    'fallback_to_default_strategy_on_config_error' => true, // Auto fallback to default strategy (bulk insert) if CSV fails due to missing configuration.
],
```

**Key Settings:**
- `fallback_to_default_strategy_on_config_error` - Automatically switches to bulk insert if CSV import fails due to missing database configuration. Ensures seeding completes successfully.

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

</details>

---

<details>
<summary><h2>🔧 CSV Strategy Setup</h2></summary>

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

**Security Note:** `LOAD DATA LOCAL INFILE` allows MySQL to read files from the client machine. Only enable this in trusted environments (development, staging). Consider disabling in production unless absolutely necessary.

### PostgreSQL Configuration

For PostgreSQL, the CSV strategy uses the `COPY` command which requires:

1. **File Access** - PostgreSQL server must have read access to `storage/app/turbo-seeder/`
2. **User Privileges** - Database user must have `COPY` privileges on target tables
3. **Server Location** - For remote servers, ensure CSV file path is accessible

**Note:** For local PostgreSQL installations, CSV strategy typically works without additional configuration. For remote servers, you may need network file sharing or use the default strategy.

### Troubleshooting

If you see a warning about CSV strategy falling back to default:

1. **MySQL** - Verify `PDO::MYSQL_ATTR_LOCAL_INFILE => true` is in `config/database.php`
2. **PostgreSQL** - Check file permissions and COPY privileges
3. **Both** - Review application logs for detailed error messages

The default strategy works without any additional configuration and is still very fast (2-3 minutes for 1M records).

</details>

---

<details>
<summary><h2>📊 Performance Benchmarks</h2></summary>

All benchmarks are approximate and may vary based on hardware and configuration.

### Default Strategy (Bulk Insert)

- **1M simple records**: ~2-3 minutes
- **1M records with 10 columns**: ~3-4 minutes
- **Memory usage**: < 256MB
- **Best for**: General use, remote databases

### CSV Strategy (File Import)

- **1M simple records**: ~1-2 minutes
- **1M records with 10 columns**: ~2-3 minutes
- **Memory usage**: < 200MB
- **Speedup**: 2-3x faster than default
- **Best for**: Local databases, maximum speed

</details>

---

<details>
<summary><h2>🧪 Testing</h2></summary>

Run the test suite:

```bash
composer test
```

Run with coverage:

```bash
composer test-coverage
```

</details>

---

<details>
<summary><h2>🤝 Contributing</h2></summary>

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

</details>

---

<details>
<summary><h2>🔒 Security</h2></summary>

If you discover any security-related issues, please email `n.ahmad.web.cit22@gmail.com` instead of creating a public issue.

</details>

---

<details>
<summary><h2>📝 Changelog</h2></summary>

Please see [CHANGELOG.md](CHANGELOG.md) for more information on recent changes.

</details>

---

<details>
<summary><h2>📄 License</h2></summary>

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.

</details>

---

<details>
<summary><h2>🙏 Credits</h2></summary>

<!-- - [iz-ahmad](https://github.com/iz-ahmad) -->
- All Contributors

**Made with <3 for the Laravel community**

</details>
