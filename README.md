# Laravel Turbo Seeder

[![Tests](https://github.com/iz-ahmad/laravel-turbo-seeder/actions/workflows/run-tests.yml/badge.svg)](https://github.com/iz-ahmad/laravel-turbo-seeder/actions/workflows/run-tests.yml) [![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-blue.svg)](https://php.net) [![Laravel Version](https://img.shields.io/badge/laravel-10--13-red.svg)](https://laravel.com)
<!-- [![Latest Stable Version](https://img.shields.io/packagist/v/iz-ahmad/laravel-turbo-seeder.svg)](https://packagist.org/packages/iz-ahmad/laravel-turbo-seeder) -->
<!-- [![Total Downloads](https://img.shields.io/packagist/dt/iz-ahmad/laravel-turbo-seeder.svg)](https://packagist.org/packages/iz-ahmad/laravel-turbo-seeder) -->
<!-- [![License](https://img.shields.io/packagist/l/iz-ahmad/laravel-turbo-seeder.svg)](LICENSE.md) -->

**Seed millions of records in seconds. Use the factory you already have - or go raw for maximum speed.**

Laravel Turbo Seeder is a high-performance database seeder built for production-scale data (1M+ records) with minimal time and memory. Both paths - Eloquent factories and raw generators - feed the same bulk-insert / native-CSV engine.

![Laravel Turbo Seeder Demo](images/banner.png)

---

## Why Turbo Seeder?

Default Laravel seeders don't scale. Seeding 500K–1M+ records for realistic load testing can take 30+ minutes and hundreds of MB of RAM.

**Turbo Seeder eliminates that bottleneck.**

What used to take **~30 minutes** for **1M records** now completes in **~15–60 seconds**.

| | Standard Laravel | Turbo Seeder |
|---|---|---|
| 1M records (simple table) | ~30 min | **~16s** |
| 1M records (complex table) | ~40-60 min | **~60s** |
| 1M records (CSV strategy) | - | **~9–40s** |
| Memory | unbounded | **< 200 MB** |

So you can:
- Test against production-scale datasets in seconds
- Detect slow queries and indexing issues early
- Keep CI pipelines fast with realistic data

---

## Two Paths for Data Generation - Pick What Fits

TurboSeeder gives you two interchangeable ways to produce rows. Both run through the same high-performance engine.

### Path A - `fromFactory()` · reuse your existing factory

No new data definitions. Your existing factory is the single source of truth.

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

---

### Path B - `generate()` · raw closure for maximum speed

No Faker, no model instantiation - just a closure that returns an array. Use the package's built-in `TurboData` helpers for fast, deterministic data generation.

```php
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

$uniqueEmail = TurboData::uniqueEmail();

TurboSeeder::create('users')
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

### Which one should I use?

| | `fromFactory()` | `generate()` |
|---|---|---|
| **You write** | Nothing new - reuse the factory | A small closure returning an array |
| **Data source** | Your factory `definition()` | `TurboData` helpers or your own logic |
| **Faker** | Yes (one call per row) | No - Faker-free |
| **Throughput for 1M rows** | Fast (minutes - Faker-bound) | Fastest (~15–60s) |
| **Factory states** | ✅ | - |
| **Best for** | ≤ 100k rows, or when the factory already exists and data realism matters | Huge datasets, maximum speed |

> **Skipped on both paths:** model events, observers, and accessors/mutators. Anything those compute (slugs, hashes, derived columns) must live in the factory definition or the generator closure.

---

## Installation

```bash
composer require iz-ahmad/laravel-turbo-seeder
```

The package auto-registers itself. Optionally publish the config:

```bash
php artisan vendor:publish --tag="turbo-seeder-config"
```

> **Note:** This package is not publicly released yet.
> For now, use it locally by cloning the repo and installing via a path repository in `composer.json`.

<details>
<summary>Local installation steps</summary>

1. Clone the repository:

```bash
git clone https://github.com/iz-ahmad/laravel-turbo-seeder.git
```

2. Add to your Laravel project's `composer.json`:

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

3. Require it:

```bash
composer require iz-ahmad/laravel-turbo-seeder:@dev
```

</details>

---

## Quick Start

### Scaffold a seeder (recommended)

```bash
# generate()-based seeder (fastest)
php artisan make:turbo-seeder UsersTurboSeeder --table=users --count=1000000

# fromFactory()-based seeder
php artisan make:turbo-seeder UsersTurboSeeder --table=users --factory=UserFactory
```

This introspects your table's columns and generates a ready-to-edit seeder in `database/seeders/`.

---

### Factory path - full example

```php
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Post;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Truncate and reseed users using the existing factory
        TurboSeeder::fromFactory(User::factory())
            ->truncate()           // empty the table first
            ->withTimestamps()     // auto-fill created_at/updated_at
            ->count(50_000)
            ->run();

        // Use a factory state for a subset of records
        TurboSeeder::fromFactory(User::factory()->admin())
            ->count(500)
            ->run();

        // Even the CSV strategy works with fromFactory()
        TurboSeeder::fromFactory(Post::factory())
            ->count(1_000_000)
            ->useCsvStrategy()
            ->run();
    }
}
```

---

### Generator path - full example

```php
use Illuminate\Database\Seeder;
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $uniqueEmail = TurboData::uniqueEmail();

        // Seed users
        TurboSeeder::create('users')
            ->columns(['name', 'email', 'password', 'status'])
            ->generate(fn ($i) => [
                'name'     => "User {$i}",
                'email'    => $uniqueEmail($i),
                'password' => TurboData::hashedPassword(),
                'status'   => TurboData::weightedFrom(['active' => 80, 'inactive' => 20]),
            ])
            ->withTimestamps()
            ->count(50_000)
            ->run();

        // Seed posts referencing the users above
        $userIds = TurboData::fromTable('users'); // loaded once, cycled forever

        TurboSeeder::create('posts')
            ->columns(['user_id', 'title', 'content', 'status'])
            ->generate(fn ($i) => [
                'user_id' => $userIds($i),
                'title'   => "Post {$i}",
                'content' => "Content for post {$i}",
                'status'  => TurboData::randomFrom(['draft', 'published']),
            ])
            ->withTimestamps()
            ->count(1_000_000)
            ->useCsvStrategy()   // fastest - native LOAD DATA / COPY
            ->run();
    }
}
```

---

### CSV Strategy (fastest)

Both paths support the CSV strategy. It uses `LOAD DATA LOCAL INFILE` (MySQL) or `COPY FROM STDIN` (PostgreSQL) - typically 2–4× faster than bulk INSERT for large datasets.

```php
// Works with fromFactory()...
TurboSeeder::fromFactory(Post::factory())
    ->count(1_000_000)
    ->useCsvStrategy()
    ->run();

// ...and with generate()
TurboSeeder::create('posts')
    ->columns(['user_id', 'title', 'content'])
    ->generate(fn ($i) => [...])
    ->count(1_000_000)
    ->useCsvStrategy()
    ->run();
```

See [CSV Strategy Setup](#csv-strategy-setup) for MySQL configuration. PostgreSQL works out of the box.

---

## Common Use Cases

### Seeding Tables with Relationships

#### BelongsTo / MorphTo - `recycle()` on the factory path

Pre-load the parent models once; the factory picks from the pool on every row with no extra DB calls:

```php
// 1. Seed parents via TurboSeeder (or they may already exist)
TurboSeeder::fromFactory(User::factory())->count(10_000)->run();

// 2. Load parent models into a recycle pool
$users = User::all(); // or User::select('id')->get() to keep memory low

// 3. Seed children - recycle() replaces the factory's for() create() calls
TurboSeeder::fromFactory(Post::factory()->recycle($users))
    ->count(1_000_000)
    ->run();
```

> If your factory uses `->for(User::factory())` without `->recycle()`, TurboSeeder will log a warning because every row would trigger an individual `User::create()` call behind the scenes.

#### BelongsTo - generator path with `fromTable()`

Lighter on memory than loading full Eloquent models - stores only raw IDs:

```php
use IzAhmad\TurboSeeder\Facades\TurboSeeder;
use IzAhmad\TurboSeeder\Helpers\TurboData;

// Seed parents
TurboSeeder::create('users')
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

TurboSeeder::create('posts')
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

These child relationships can't be wired inside a single bulk-seed call - the parent's auto-generated PK isn't available until after the bulk INSERT completes. Seed each table separately instead:

```php
// 1. Seed users
TurboSeeder::fromFactory(User::factory())->count(10_000)->run();

// 2. Seed posts referencing those users
$users = User::select('id')->get();
TurboSeeder::fromFactory(Post::factory()->recycle($users))->count(100_000)->run();

// 3. Seed pivot / child table separately (e.g. post_tags)
$postIds = TurboData::fromTable('posts');
$tagIds  = TurboData::fromTable('tags');
TurboSeeder::create('post_tag')
    ->columns(['post_id', 'tag_id'])
    ->generate(fn ($i) => ['post_id' => $postIds($i), 'tag_id' => $tagIds($i)])
    ->count(200_000)
    ->run();
```

This pattern is actually faster than letting Eloquent handle these relationships through `has()` - each table gets its own bulk INSERT.

### Seeding with Real-World Data Distribution

```php
TurboSeeder::create('orders')
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
    ])
    ->count(500_000)
    ->withProgressTracking()
    ->run();
```

### Time-Series Data

```php
TurboSeeder::create('analytics_events')
    ->columns(['event_type', 'value', 'recorded_at'])
    ->generate(fn ($i) => [
        'event_type'  => TurboData::cycleFrom(['page_view', 'click', 'signup'])($i),
        'value'       => TurboData::randomInt(1, 100),
        'recorded_at' => TurboData::sequentialDate('2024-01-01', 'hour', $i),
    ])
    ->count(8_760) // one year of hourly data
    ->run();
```

### Dry Run (preview without writing)

```php
$result = TurboSeeder::create('users')
    ->columns(['name', 'email'])
    ->generate(fn ($i) => ['name' => "User {$i}", 'email' => "u{$i}@test.com"])
    ->count(10_000)
    ->dryRun()
    ->run();

// $result->isDryRun === true, no rows were committed
echo "Would have inserted: {$result->recordsInserted} rows";
```

### Upsert (insert or update on conflict)

```php
TurboSeeder::create('products')
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

---

## Migration from Standard Seeders

Converting an existing seeder takes one or two lines.

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

### After - keep the factory, get the speed

```php
use IzAhmad\TurboSeeder\Facades\TurboSeeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        TurboSeeder::fromFactory(User::factory())
            ->count(10_000)
            ->run();
    }
}
```

### After - raw generator for maximum throughput

```php
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
            ->count(10_000)
            ->run();
    }
}
```

---

## Strategy Comparison

| | Default Strategy | CSV Strategy |
|---|---|---|
| **Speed** | Fast (~15–60s for 1M) | Fastest (~9–40s for 1M)¹ |
| **Memory** | ~50–160 MB | ~0 MB additional |
| **Setup** | None | MySQL needs `local_infile`; PostgreSQL needs nothing |
| **Works with** | `generate()` + `fromFactory()` | `generate()` + `fromFactory()` |
| **Best for** | General use, remote databases | Maximum throughput on MySQL/PostgreSQL |

¹ **SQLite:** CSV may be slower than default on SQLite due to file I/O overhead. For SQLite, stick to the default strategy.

**Quick guide:**
- **MySQL/PostgreSQL + 1M+ rows** → CSV strategy
- **SQLite** → default strategy
- **Any size, convenience first** → default strategy to start, switch later if needed

> **MySQL pre-flight check:** Before generating the CSV file, TurboSeeder verifies that `LOCAL INFILE` is actually enabled on both the client and the server. If it isn't, it falls back to the default strategy immediately - no wasted time writing a file that couldn't be imported.

---

## CSV Strategy Setup

### MySQL

Add `PDO::MYSQL_ATTR_LOCAL_INFILE` to your connection options in `config/database.php`:

```php
'mysql' => [
    // ... other settings ...
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA     => env('MYSQL_ATTR_SSL_CA'),
        PDO::MYSQL_ATTR_LOCAL_INFILE => true,  // ← add this
    ]) : [],
],
```

MySQL also requires `local_infile` enabled server-side (off by default in MySQL 8.0+):

```sql
SET GLOBAL local_infile = 1;
```

Or permanently in `my.cnf` under `[mysqld]`: `local_infile = 1`.

> **Security note:** Only enable `LOCAL INFILE` in trusted environments (dev/staging). Avoid enabling it in production unless strictly necessary.

### PostgreSQL

**Nothing to configure.** The CSV strategy uses a client-side `COPY ... FROM STDIN` (via PDO), so it works on managed/containerised PostgreSQL (RDS, Cloud SQL, Neon, Supabase, Docker) without superuser privileges. Only normal `INSERT` permissions are needed.

### SQLite

Supported, but the default strategy is usually faster for SQLite. Use it unless you specifically find CSV beneficial for your workload.

### Troubleshooting

If you see a warning about CSV falling back to default:

1. **MySQL** - Verify `PDO::MYSQL_ATTR_LOCAL_INFILE => true` is in `config/database.php` and `local_infile = 1` is enabled server-side
2. **All** - Check the Laravel log for a detailed error message

The default strategy is still very fast and needs no configuration.

---

## Features At A Glance

- **Lightning Fast**: 1M records in 15–60 seconds
- **Memory Efficient**: under 200 MB peak
- **Two Data Paths**: `fromFactory()` (reuse your existing factory) or `generate()` (raw closure, Faker-free)
- **Two Strategies**: bulk INSERT or native CSV import (`LOAD DATA` / `COPY`)
- **Multi-Database**: MySQL, PostgreSQL, SQLite
- **Scaffolder**: `php artisan make:turbo-seeder` generates a ready-to-edit seeder
- **TurboData Helpers**: Faker-free helpers for weighted picks, date ranges, unique values, FK assignment
- **Automatic Type Formatting**: enums, JSON, dates, arrays, collections - handled automatically
- **Relational Seeding**: load FK values from seeded tables in one line, zero extra queries
- **Events**: `TurboSeederStarting`, `TurboSeederCompleted`, `TurboSeederFailed`
- **Upsert Support**: conflict resolution per driver with pre-flight index validation
- **Progress Tracking**: real-time progress bar with metrics
- **Dry Run**: validate data generation without committing
- **Highly Configurable**: chunk size, transactions, retries, FK/unique checks, and more
- **Laravel 10–13 compatible**

---

## API Documentation

### Entry Points

```php
// Generator path - explicitly name table and columns
TurboSeeder::create('users')
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
| `columns(array)` | Columns to seed |
| `columnsFromSchema()` | Derive columns from the table schema (opt-in; see note below) |
| `generate(Closure)` | Row generator closure - receives `$index`, returns an array |
| `withTimestamps()` | Auto-fill `created_at` / `updated_at` |
| `withoutTimestamps()` | Disable timestamp auto-fill |

> **`columnsFromSchema()` note:** Pulls every non-PK column from the schema builder. Opt-in (not default) because it skips NOT NULL columns the generator doesn't produce, which can cause insert failures on strict schemas.

#### Seeding Behaviour

| Method | Description |
|---|---|
| `count(int)` | Number of records to seed |
| `chunkSize(int)` | Records per chunk - automatically clamped to the driver's bind-parameter limit (65,535 on MySQL/PostgreSQL; auto-detected on SQLite) |
| `truncate()` | Empty the target table before seeding (committed before the seed; cannot combine with `dryRun()`). **Driver note:** on MySQL, `TRUNCATE` resets `AUTO_INCREMENT`. On PostgreSQL and SQLite a `DELETE` is used instead (FK-safe), which does **not** reset identity sequences — IDs will continue from the previous high-water mark after a truncate+reseed. |
| `commitEvery(int)` | Default strategy only: commit every N chunks instead of one wrapping transaction (for very large seeds). Has no effect on the CSV strategy. **Warning:** combining `commitEvery()` with `truncate()` leaves no rollback path — if seeding fails mid-run, already-committed chunks remain in a truncated table. |
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
| `useTransactions()` / `withoutTransactions()` | Toggle the wrapping transaction. CSV defaults to no wrapping transaction; default strategy wraps unless `commitEvery()` is used. |

#### Other

| Method | Description |
|---|---|
| `connection(string)` | Database connection name |
| `withProgressTracking()` / `withoutProgressTracking()` | Toggle progress bar |
| `retryAttempts(int)` | Retry on transient deadlock/lock-timeout (1–10; default: 3) |
| `withoutColumnValidation()` | Skip the pre-seed column existence check |
| `when(condition, callback)` / `unless(condition, callback)` | Conditional chaining |

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

> `$result->exception` on `SeederResultDTO` carries the original `Throwable` when seeding fails, so you never lose the stack trace.

---

### TurboData Helpers

`TurboData` is a Faker-free data utility built for high-volume seeding. Every method is safe to call 1M+ times with no performance penalty.

> All returned values are automatically formatted via the internal `ValueFormatter`.

**Three calling conventions:**

| Convention | Which helpers | How to call |
|---|---|---|
| **Returns a closure** - call it with `$index` | `cycleFrom`, `uniqueEmail`, `uniqueUsername`, `uniqueSlug`, `uniqueUuid`, `fromTable`, `fromQuery`, `fromTableStream` | Create **outside** the generator, call inside: `$fn = TurboData::uniqueEmail(); ... 'email' => $fn($i)` |
| **Returns a value** - call per row | `weightedFrom`, `randomFrom`, `randomInt`, `randomFloat`, `randomBool`, `nullable`, `dateRange`, `sequentialDate` | Call directly inside the generator: `'status' => TurboData::weightedFrom([...])` |
| **Computed once, cached** | `nowOnce`, `hashedPassword` | Call inside the generator; the value is computed once and reused every row |

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

$userIds     = TurboData::fromTable('users');                           // cycle (default)
$categoryIds = TurboData::fromTable('categories', 'id', 'random');      // random pick
$tagIds      = TurboData::fromTable('tags', 'id', FromTableMode::RANDOM); // or the enum
$codes       = TurboData::fromTable('regions', 'code', 'cycle', 'reports'); // custom connection

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

**`fromQuery()`** - when `fromTable()` isn't flexible enough (filters, joins, ordering):

```php
$userIds = TurboData::fromQuery(
    fn () => DB::table('users')->where('active', 1)->orderBy('id')->pluck('id')->toArray()
);
```

**`fromTableStream()`** - for very large reference tables that would consume too much memory if loaded all at once:

```php
// Streams IDs one page at a time; cycles with bounded memory
$userIds = TurboData::fromTableStream('users', 'id', pageSize: 10_000);
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
TurboSeeder::create('products')
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
php artisan turbo-seeder:test-connection    # verify the DB connection
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
- Simple tables (3–5 columns): 1000–5000
- Medium tables (6–10 columns): ~1000
- Complex tables (15+ columns / large JSON): 200–1000

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

Measured on a modern local machine with MySQL and default chunk sizes.

### Default Strategy (Bulk Insert)

| Table | Records | Time | Peak memory |
|---|---|---|---|
| Simple (~5 cols) | 1M | ~16s | ~50 MB |
| Complex (~15–20 cols) | 1M | ~60s | ~160 MB |

### CSV Strategy

| Table | Records | Time | Additional memory |
|---|---|---|---|
| Simple (~5 cols) | 1M | ~9s | ~0 MB |
| Complex (~15–20 cols) | 1M | ~40s | ~0 MB |

### fromFactory() throughput

The factory path calls Faker's `definition()` once per row, so throughput is CPU-bound on Faker rather than on the database. Expect it to be markedly slower than the raw `generate()` numbers above (typically minutes, not seconds, for 1M rows) - but still far faster than `Model::factory()->create()` because it skips Eloquent, model events, and per-row inserts.

Use `fromFactory()` for convenience at moderate volumes (up to ~100k rows). Switch to `generate()` when you need maximum throughput.

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
