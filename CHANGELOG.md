# Changelog

All notable changes to `laravel-turbo-seeder` will be documented in this file over time.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [Unreleased]

### Added
- `TurboSeeder::fromFactory()` — generate rows directly from an existing Laravel
  model factory (reuses its definition, states and Faker; auto-fills timestamps).
- `withTimestamps()` / `withoutTimestamps()` to auto-fill `created_at`/`updated_at`.
- `truncate()` to empty the target table before seeding.
- `commitEvery(int $chunks)` for the default strategy — periodic commits instead
  of one wrapping transaction on very large seeds.
- `disableUniqueChecks()` / `enableUniqueChecks()` — opt-in MySQL `unique_checks`
  toggle (no longer bundled with the foreign-key flag).
- `columnsFromSchema()` to derive the seed columns from the table schema.
- `TurboData::fromTableStream()` — memory-bounded, paged reference-pool reader.
- `FromTableMode` enum for `fromTable()` (legacy `'cycle'`/`'random'` strings
  still accepted).
- `make:turbo-seeder` Artisan generator (with `--table`, `--count`, `--factory`).
- `TurboSeederStarting` and `TurboSeederFailed` events.
- MySQL and PostgreSQL service jobs in CI so the native import paths are tested.

### Changed
- **PostgreSQL CSV import now uses client-side `COPY ... FROM STDIN`** instead of
  server-side `COPY FROM '<path>'`, so it works on managed/containerised
  PostgreSQL without server filesystem access or superuser.
- The CSV strategy no longer wraps the whole import in a single transaction by
  default (`LOAD DATA`/`COPY` are atomic per statement). Dry runs always use one.
- Published `performance.*` and `progress.enabled` config keys are now honoured.
- Errors are classified by SQLSTATE / driver error number instead of matching
  English error text.
- MySQL/PostgreSQL chunk size is clamped to the 65,535 bind-parameter limit;
  SQLite uses the engine's real variable limit (999 or 32766).
- Upsert where every column is a key now does nothing on conflict across MySQL,
  PostgreSQL and SQLite (was a plain INSERT on MySQL/SQLite).
- Seeding failures rethrow with the original exception preserved as `previous`.
- `ExampleSeeder` moved out of the autoloaded source tree into `examples/`.

### Fixed
- **MySQL CSV NULL handling**: `NULL` values were imported as the literal string
  `\N`; they now import as real `NULL` (via user variables + `NULLIF`).
- CSV null-marker collisions now fail loudly instead of silently corrupting data.
- `dryRun()` combined with `withoutTransactions()` now throws instead of silently
  committing rows.
- `make`/benchmark: `turbo-seeder:benchmark` refuses to drop an existing table and
  prompts for confirmation outside local/testing environments.
- Upsert keys are validated against a real unique/primary index before seeding.
- Removed dead config keys (`csv_strategy.enabled`, `csv_strategy.line_terminator`,
  `progress.update_frequency`).

### Security
- Hardened the CSV file path interpolated into MySQL `LOAD DATA` with an
  allow-list of path-safe characters.
