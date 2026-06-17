# CLAUDE.md — TurboSeeder Package Project Guidelines

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

Default to writing **no inline comments**. Well-named identifiers, small functions, and clear control flow are the real documentation — they can't drift out of sync with the code because they *are* the code. A comment is a second source of truth competing with the first, and the first will win every time a future change forgets to update the second. Only add one when the *why* is non-obvious from reading the code, or something that would surprise a future reader. If removing the comment wouldn't confuse anyone, don't write it.

**Never:**
- Comment what the code does (well-named identifiers already do that).
- Reference the current task, PR, or issue number in source comments.
- Write multi-paragraph docblocks for simple getters/setters.
- Add `// Added for X`, `// Handles Y case`, `// Used by Z` comments.

**Keep** docblocks on public API methods that have non-obvious parameters or return contracts, and on `private` methods that contain a genuine non-obvious invariant.

### Example:

#### Bad commenting (Commenting what the code does, when it can be understood from reading the code):
```php
    // Inspect the original factory before count(null) — some Laravel versions
    // do not propagate the recycle pool through newInstance(), so checking
    // $this->factory after count() would always see an empty pool.
    $this->warnIfParentRelationshipsUnrecycled($factory);

    // Normalise count to null so raw() yields a single row per call; the row
    // count is driven by the builder's count() instead of the factory's.
    $this->factory = $factory->count(null);
```

#### Good commenting:
```php
    $this->warnIfParentRelationshipsUnrecycled($factory);

    // Normalise count to null so raw() yields a single row per call
    $this->factory = $factory->count(null);
```

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
- Table and column names: use `SqlIdentifier::quoteTable()` / `SqlIdentifier::quoteColumn()`, or how it suits best.
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

- One file per commit, to keep commits atomic and easy to review (exceptions: tightly coupled changes that make no sense split up. in that case, can keep them in the same commit).
- Commit message shhould be one-liner in general and professional. 
- Add body explanation only when really necessary or asked explicitly.
- Commit message describes the **what changes in general**, not the why or how.
- No co-author lines (`Co-authored-by`), no AI attribution in commit messages.
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

## Destructive Operations

**Always ask for explicit confirmation before executing any destructive action**, including:
- Deleting files or directories
- Pushing
- Force-pushing
- Resetting commits
- Overwriting changes
- Any operation that cannot be undone

Do not assume a question like "should I delete X?" implies permission. Wait for explicit "yes" or approval before proceeding. When in doubt, ask first.

---
