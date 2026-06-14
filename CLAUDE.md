# CLAUDE.md — TurboSeeder Project Guidelines

## Project Overview

`iz-ahmad/laravel-turbo-seeder` is a high-performance bulk database seeding package for Laravel. Its single purpose is speed: seeding millions of rows in seconds via chunked bulk INSERT, MySQL `LOAD DATA LOCAL INFILE`, and PostgreSQL `COPY FROM STDIN`. Every design decision must weigh performance impact.

- **PHP**: 8.2 – 8.5
- **Laravel**: 10 – 13
- **Test runner**: Pest v2/v3/v4
- **Static analysis**: PHPStan level 5 + Larastan
- **Code style**: Laravel Pint

---

## PHP & Laravel Conventions

### Strict Types

Every PHP file must open with:

```php
<?php

declare(strict_types=1);
```

### Namespaces & Autoloading

- Production code: `IzAhmad\TurboSeeder\` → `src/`
- Tests: `IzAhmad\TurboSeeder\Tests\` → `tests/`
- PSR-4 throughout; no global functions.

### Type Declarations

- Always use typed properties, parameter types, and return types.
- Prefer `array<K, V>` generics in PHPDoc over bare `array`.
- Use nullable types (`?string`) rather than `mixed` where possible.
- Use `readonly` properties wherever mutability is not required.

### Class Design

- `final` by default for concrete classes; only open a class for extension when explicitly designed for it.
- Prefer constructor promotion for simple value-only classes.
- DTOs are immutable value objects — no setters.
- Enums over class constants for closed sets of values.
- Thin controllers/commands; push logic into dedicated action/service classes.

### Error Handling

- Use specific exception types (not bare `\RuntimeException` for everything).
- Only catch `\Throwable` as a last resort, always with a documented reason.
- Never swallow exceptions silently — either rethrow, log, or emit a structured result.

### PHP Version Compatibility

- Use `PHP_VERSION_ID` guards when branching on PHP version behaviour.

---

## Code Style

Run before every commit:

```bash
vendor/bin/pint
vendor/bin/phpstan analyse
vendor/bin/pest
```

All three must pass clean before pushing.

### Formatting (enforced by Pint)

- 4-space indentation; no tabs.
- Opening braces on the same line for control structures, new line for class/method bodies.
- Trailing commas in multiline arrays and argument lists.
- `use` statements sorted: PHP built-ins → vendor → local; one blank line between groups.
- No unused imports.

---

## Comment Rules

**Default: write no inline comments.**

Add a comment only when the **why** is non-obvious from reading the code — a hidden constraint, a subtle invariant, a workaround for a specific external behaviour, or something that would surprise a future reader. If removing the comment wouldn't confuse anyone, don't write it.

**Never:**
- Comment what the code does (well-named identifiers already do that).
- Reference the current task, PR, or issue number in source comments.
- Write multi-paragraph docblocks for simple getters/setters.
- Add `// Added for X`, `// Handles Y case`, `// Used by Z` comments.

**Keep** docblocks on public API methods that have non-obvious parameters or return contracts, and on `private` methods that contain a genuine non-obvious invariant.

---

## Testing

- Tests live in `tests/Feature/` (integration, hits DB) and `tests/Unit/` (pure logic, no DB).
- Use Pest's functional style; no class-based tests.
- Test file names match the class under test: `FooBar` → `FooBatTest.php`.
- Each test asserts one logical behaviour.
- Use `DB::table()->count()` / `DB::table()->first()` for integration assertions — avoid ORM overhead in seeder tests.
- Driver-specific tests must skip gracefully when running on the wrong driver:
  ```php
  test('...mysql only...', function () {
      // ...
  })->skip(fn () => DB::getDriverName() !== 'mysql', 'MySQL-specific');
  ```
- Mock only at system boundaries (external services, the logger). Don't mock internal classes.

---

## Security

- Any value interpolated into a raw SQL string must pass through an explicit allow-list validator — no exceptions.
- Table and column names: use `SqlIdentifier::quoteTable()` / `SqlIdentifier::quoteColumn()`.
- CSV file paths: use `assertSafeCsvPath()` before interpolating into `LOAD DATA` or `COPY` SQL.
- Never accept user-controlled SQL fragments directly.

---

## Performance Principles

This package exists to be fast. Evaluate every change against:

1. **Chunk size** — operations must stay within driver bind-parameter limits (MySQL/PG: 65,535; SQLite: 999/32,766 auto-detected).
2. **No per-row queries** — no `Model::save()`, no Eloquent events, no per-row INSERTs in the hot path.
3. **Bulk paths** — prefer chunked `DB::table()->insert()`, MySQL `LOAD DATA LOCAL INFILE`, or PostgreSQL `COPY FROM STDIN`.
4. **Memory** — generators are lazy; never collect all rows into memory at once.
5. **Transaction cost** — the CSV strategy intentionally skips a wrapping transaction (drivers handle that in bulk-load mode).

---

## Git Commit Rules

### Format

```
<type>: <short imperative description>
```

Types: `feat` · `fix` · `refactor` · `test` · `ci` · `docs` · `perf` · `security` · `chore`

### Rules

- One file per commit (exceptions: tightly coupled changes that make no sense split up).
- Commit message describes the **why or what changes**, not the how.
- No co-author lines (`Co-authored-by`), no AI attribution in commit messages.
- Commit author: `iz-ahmad <n.ahmad.web.cit22@gmail.com>`
- Never amend a pushed commit — create a new one.
- Never skip hooks (`--no-verify`).
- Never force-push `main` or `dev`.

### Branch Strategy

- Feature work: `feat/<short-slug>`
- Target: `dev` branch (not `main`)
- PRs merge `dev` → `main` for releases only

### Before Pushing

```bash
vendor/bin/pint          # style
vendor/bin/phpstan analyse   # static analysis (level 5)
vendor/bin/pest          # all tests green
git status               # no untracked files you meant to stage
```

---

## Directory Structure

```
src/
  Actions/          # Single-method action classes (ExecuteSeederAction, etc.)
  Builder/          # TurboSeederBuilder — fluent API
  Commands/         # Artisan commands
  Concerns/         # Shared traits
  Contracts/        # Interfaces
  DTOs/             # Immutable data transfer objects
  Enums/            # Backed enums
  Events/           # Laravel events (TurboSeederStarting, etc.)
  Exceptions/       # Domain exceptions
  Facades/          # TurboSeeder facade
  Services/         # Stateless service classes
  Strategies/       # Seeding strategies (Default, MySqlCsv, PostgreSql)
    Concerns/       # Strategy-shared traits
  Support/          # Framework bridges (FactoryDataGenerator, TurboData)
config/
  turbo-seeder.php  # Published config
database/
  migrations/       # Package migrations (test fixtures only in tests/)
tests/
  Feature/          # Integration tests
  Unit/             # Pure unit tests
  Fixtures/         # Test models, factories, migrations
examples/           # Usage examples (outside autoload, for documentation only)
```
