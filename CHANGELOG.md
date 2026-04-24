# Changelog

All notable changes to `laravel-turbo-seeder` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [Unreleased] — PR #11

### Added
- `->dryRun()` — generate and validate data without committing; result carries `$isDryRun` flag
- `->upsert(array $uniqueBy)` — native upsert per driver (`ON DUPLICATE KEY UPDATE` / `ON CONFLICT DO UPDATE SET`)
- `->retryAttempts(int $n)` — retry on deadlock / lock-timeout (SQLSTATE 40001, MySQL 1205) with exponential backoff (1–10 attempts)
- `->withoutColumnValidation()` — skip pre-seed schema check
- `TurboSeederCompleted` event — dispatched after every successful seed (including dry-run); carries table name and full result DTO
- Column name regex validation on `columns()` and `upsert()`
- Upsert key subset validation — keys must be declared columns
- `hasTable()` check before seeding — throws clear error for non-existent tables
- `SeederConfigurationDTO::shouldUseTransactions()` accessor

### Fixed
- SQL placeholder string cached per strategy instance instead of rebuilt per chunk
- CSV temp directory resolved via `realpath()` to block path traversal
- `dryRun()` docblock warns against combining with `withoutTransactions()`
- `TurboSeederCompleted` docblock clarifies event fires on dry-run; listeners must check `isDryRun`

### Changed
- `ValueFormatter`: dead `is_bool` branch removed from `formatForCsv()`; custom formatters loop guarded with `empty()` check
- `TurboData`: pool count cached in `fromPool()` closure; `dateRange()` validates argument order; `mt_rand()` replaced with `random_int()` throughout for CSPRNG consistency
- CI matrix updated: PHP 8.2–8.5 × Laravel 11–13; excluded known unstable combos

---

## [1.1.0] — 2026-04-16 — PR #10

### Added
- `TurboData` helper class — Faker-free static helpers for high-volume seeding: `cycleFrom`, `weightedFrom`, `randomFrom`, `randomInt`, `randomFloat`, `randomBool`, `nullable`, `dateRange`, `sequentialDate`, `nowOnce`, `fromPool`, `uniqueEmail`, `uniqueUsername`, `uniqueSlug`, `uniqueUuid`
- `ValueFormatter` service — unified value formatting with `BackedEnum`, `UnitEnum`, `Collection`, and custom type support via `ValueFormatter::extend()`
- `UniqueValueGenerator` deprecated in favour of `TurboData`

### Fixed
- CSV generator no longer calls user closure twice when inferring columns
- CSV file handle now closed after reading; `GenerateCsvAction` wraps write loop in `try-finally`
- SQLite CSV null marker (`\N`) now configurable
- `MemoryManager` GC counter increments in `maybeCleanup()` — fixes "GC fires once then never again"
- Transaction tracking records whether the strategy started the transaction before commit/rollback
- `CsvImportFailedException` now carries driver, table, and filepath for debuggable logs
- CSV strategies catch `Throwable` instead of `Exception`

### Security
- Temp CSV files created with `0600` permissions (previously world-readable)
- Temp filenames now use `bin2hex(random_bytes(16))` — replaces predictable `uniqid()+time()`

### Changed
- Test matrix expanded to PHP 8.5 × Laravel 13
- ExampleSeeder rewritten to demonstrate `TurboData` patterns

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
