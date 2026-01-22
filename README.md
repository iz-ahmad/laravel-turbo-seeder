# Laravel Turbo Seeder

<!-- [![Latest Version on Packagist](https://img.shields.io/packagist/v/iz-ahmad/laravel-turbo-seeder.svg?style=flat-square)](https://packagist.org/packages/iz-ahmad/laravel-turbo-seeder)
[![Total Downloads](https://img.shields.io/packagist/dt/iz-ahmad/laravel-turbo-seeder.svg?style=flat-square)](https://packagist.org/packages/iz-ahmad/laravel-turbo-seeder)
[![Tests](https://github.com/iz-ahmad/laravel-turbo-seeder/actions/workflows/run-tests.yml/badge.svg)](https://github.com/iz-ahmad/laravel-turbo-seeder/actions/workflows/run-tests.yml)
[![License](https://img.shields.io/packagist/l/iz-ahmad/laravel-turbo-seeder.svg?style=flat-square)](LICENSE.md) -->

**Blazing fast database seeder for Laravel - seed millions of records in minutes, or even seconds!**

Laravel Turbo Seeder is a high-performance database seeding package that allows you to seed massive amounts of data (1M+ records) in just 2-3 minutes, compared to the traditional 40+ minutes. It's perfect for testing applications with realistic data volumes and ensuring your app performs smoothly with large datasets.

## 💡 Why Turbo Seeder?

Because waiting **40+ minutes** for seeders to finish is not “testing” - it’s nearly a **punishment**!

When you need to test your app with **real, production-scale data** (hundreds of thousands or millions of rows), traditional Laravel seeders crawl… and your flow dies with them.

**Turbo Seeder makes seeding great again (in a good way) ;-).**
What used to take **40+ minutes** now finishes in **2–3 minutes** for ~**1M records**.
And what used to take 5-10 minutes now finishes in **1-2 minutes** for ~**100K records**. ...Approximately.

No coffee breaks. No tab-switching. No “I’ll test later”. So you can:

* test performance like it’s production,
* catch slow queries before users do
* iterate without killing your dev flow

And here's how we are achieving this:

1. **Bypassing Eloquent**: Uses raw database queries instead of ORM overhead
2. **Bulk Operations**: Multi-row INSERT statements instead of individual inserts
3. **Database Optimizations**: Disables constraints, uses transactions efficiently
4. **CSV Import**: For maximum speed, uses native database CSV import commands. This is even faster than the default strategy.
5. **Memory Management**: Efficient chunking and garbage collection
6. **Streaming**: CSV files are written/read in streams, not loaded into memory

Below are some performance benchmarks for different strategies:
- **Default Strategy (Bulk Insert)**: ~2-3 minutes for 1M records
- **CSV Strategy (File Import)**: ~1-2 minutes for 1M records

## 🎯 Perfect for:

Perfect for:
- ✅ Performance testing with realistic data volumes
- ✅ Development environments needing large datasets
- ✅ CI/CD pipelines requiring fast seeding
- ✅ Testing database performance and queries
- ✅ Generating test data for load testing

---

## 🚀 Main Features

- ⚡ **Lightning Fast**: Seed 1M records in 2-3 minutes
- 💾 **Memory Efficient**: Uses less than 256MB peak memory
- 🗄️ **Multi-Database Support**: MySQL, PostgreSQL, SQLite
- 📊 **Two Strategies**: Default (bulk insert) and CSV (file-based import)
- 🎯 **Fluent API**: Beautiful, chainable interface
- 📈 **Progress Tracking**: Real-time progress bars with metrics
- 🔧 **Highly Configurable**: Fine-tune performance settings
- ✅ **Fully Tested**: 120+ tests with Pest PHP
- 🎨 **Laravel 11/12 Compatible**: Works with latest Laravel versions

## 📋 Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x, or 12.x
- MySQL 5.7+, PostgreSQL 9.6+, or SQLite 3.8+

## 📦 Installation

You can install the package via Composer:

```bash
composer require iz-ahmad/laravel-turbo-seeder
```

The package will automatically register itself.

### Publish Configuration (Optional)

You can publish the configuration file to customize settings:

```bash
php artisan vendor:publish --tag="turbo-seeder-config"
```

This will create a `config/turbo-seeder.php` file in your project.

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
You can find more examples in the [examples](#-examples) section.

## 📚 Documentation

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

You can use the `UsesTurboSeeder` trait in your seeders:

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

See [src/Examples/ExampleSeeder.php](src/Examples/ExampleSeeder.php) for more examples.

### Artisan Commands

#### Run Seeder

```bash
php artisan turbo-seeder:run YourSeederClass
```

Options:
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

Options:
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

Options:
- `--all` - Clear all temporary files including subdirectories

## ⚙️ Configuration

### Chunk Sizes

```php
'chunk_sizes' => [
    'mysql' => 4000,   // Optimal for MySQL
    'pgsql' => 3000,   // Optimal for PostgreSQL
    'sqlite' => 2000,  // Optimal for SQLite
],
```

### Memory Management

```php
'memory' => [
    'limit_mb' => 256,              // Memory limit in MB
    'gc_threshold_percent' => 80,   // GC threshold percentage
    'force_gc_after_chunks' => 10,  // Force GC after N chunks
],
```

### CSV Strategy

```php
'csv_strategy' => [
    'enabled' => true,
    'temp_path' => storage_path('app/turbo-seeder'),
    'buffer_size' => 8192,
    'batch_size' => 10000,
],
```

## 📊 Performance Benchmarks (approximate)

### Default Strategy (Bulk Insert)

- **1M simple records**: ~2-3 minutes
- **1M records with 10 columns**: ~3-4 minutes
- **Memory usage**: < 256MB

### CSV Strategy (File Import)

- **1M simple records**: ~1-2 minutes
- **1M records with 10 columns**: ~2-3 minutes
- **Memory usage**: < 200MB
- **Speedup**: 2-3x faster than default

## 🧪 Testing

Run the test suite:

```bash
composer test
```

Run with coverage:

```bash
composer test-coverage
```

## 🤝 Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## 🔒 Security

If you discover any security-related issues, please email `n.ahmad.web.cit22@gmail.com` instead of creating a public issue, so that users can't exploit the vulnerability until a fix is released.

## 📝 Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## 📄 License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.

## 🙏 Credits

<!-- - [iz-ahmad](https://github.com/iz-ahmad) -->
- All Contributors

**Made with ❤️ for the Laravel community**
