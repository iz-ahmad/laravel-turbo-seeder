# Changelog

All notable changes to `laravel-turbo-seeder` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

Here is a slightly more concise version while keeping all core information intact:

---

## [Unreleased] — PR #11

### Added
* `->dryRun()` — generate/validate without committing; result includes `$isDryRun`
* `->upsert(array $uniqueBy)` — native driver support (`ON DUPLICATE KEY UPDATE` / `ON CONFLICT DO UPDATE SET`)
* `->retryAttempts(int $n)` — exponential backoff retry (1–10 attempts) for deadlock/lock-timeout (SQLSTATE 40001, MySQL 1205)
* `->withoutColumnValidation()` — skip schema pre-check
* `TurboSeederCompleted` event — dispatched after every successful seed (including dry-run); includes table + result DTO
* Column regex validation for `columns()` and `upsert()`
* Upsert key subset validation (must exist in declared columns)
* `hasTable()` existence check before seeding
* `SeederConfigurationDTO::shouldUseTransactions()` accessor

### Fixed
* SQL placeholder string cached per strategy instance (not per chunk)
* CSV temp directory resolved via `realpath()` (path traversal protection)
* `dryRun()` docblock warns against `withoutTransactions()`
* `TurboSeederCompleted` docblock clarifies dry-run behavior

### Changed

* `ValueFormatter`: removed dead `is_bool` branch; guarded custom formatter loop with `empty()`
* `TurboData`: cached pool count in `fromPool()`, validated `dateRange()` order, replaced `mt_rand()` with `random_int()`
* CI matrix: PHP 8.2–8.5 × Laravel 11–13 (unstable combos excluded)

---

## [1.1.0] — 2026-04-16 — PR #10

### Added
* `TurboData` — Faker-free helpers for high-volume seeding: `cycleFrom`, `weightedFrom`, `randomFrom`, `randomInt`, `randomFloat`, `randomBool`, `nullable`, `dateRange`, `sequentialDate`, `nowOnce`, `fromPool`, `uniqueEmail`, `uniqueUsername`, `uniqueSlug`, `uniqueUuid`
* `ValueFormatter` — unified formatting for `BackedEnum`, `UnitEnum`, `Collection`, plus extensibility via `extend()`
* Deprecated `UniqueValueGenerator` (replaced by `TurboData`)

### Fixed
* CSV generator no longer invokes closure twice for column inference
* CSV file handle properly closed; write loop wrapped in `try-finally`
* SQLite CSV null marker (`\N`) configurable
* `MemoryManager` GC counter increment moved to `forceCleanup()` (fixes one-time GC trigger)
* Transaction tracking records whether strategy initiated transaction
* `CsvImportFailedException` now includes driver, table, filepath
* CSV strategies now catch `Throwable`

### Security
* Temp CSV files use `0600` permissions
* Filenames use `bin2hex(random_bytes(16))` (replaces predictable `uniqid()+time()`)

### Changed
* CI expanded to PHP 8.5 × Laravel 13
* ExampleSeeder updated to demonstrate `TurboData` patterns

---

## [1.0.0] — Initial Release

### Added
- Fluent builder API with chainable configuration
- Two seeding strategies: bulk INSERT (default) and native CSV file import
- MySQL, PostgreSQL, and SQLite support
- Memory-efficient chunking with automatic garbage collection
- Real-time progress tracking with metrics
- Artisan commands: `turbo-seeder:run`, `turbo-seeder:benchmark`, `turbo-seeder:test-connection`, `turbo-seeder:clear-cache`
- `UsesTurboSeeder` trait with `quickSeed()` and `quickCsvSeed()` helpers
- Publishable config (`turbo-seeder.php`)
- Full Pest PHP test suite
