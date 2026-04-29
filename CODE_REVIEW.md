# Code Review: laravel-turbo-seeder

**Date:** 2026-04-29
**Reviewer:** Claude (Sonnet 4.6)
**Skills applied:** php-best-practices, clean-code-principles, code-review

Overall architecture is solid — good use of DTOs, enums, strategy pattern, contracts, and proper separation of concerns. The following are real issues to fix before releasing.

---

## Issues

### 1. BUG — `ConsoleProgressTracker::start()` never fires (dead code)

**File:** `src/Services/ConsoleProgressTracker.php:60`

```php
if ($this->progressBar->getProgress() > 0) {
    $this->progressBar->start();
}
```

A freshly created `ProgressBar` always has `getProgress() === 0`, so `start()` is **never called**. After `reset()`, `setProgress(0)` sets it back to 0, so `start()` still never fires. The progress bar still renders through `advance()` calls (Symfony triggers `display()` internally), but:

- The initial 0% state is never displayed to the user
- Symfony's internal timer (`getStartTime()`) is never initialized — `calculateRate()` and `calculateRemaining()` use `getStartTime()`, which returns 0, causing incorrect rate/ETA values

**Fix:** Replace `> 0` with `=== 0` (start on fresh bar) or call `$this->progressBar->start()` unconditionally.

---

### 2. BUG — `PostgreSqlCsvStrategy::isCopyCommandError` pattern too broad

**File:** `src/Strategies/PostgreSqlCsvStrategy.php:84`

```php
$copyErrorPatterns = [
    'permission denied',
    'could not open file',
    'must be superuser',
    'COPY',            // matches ANY error containing "COPY"
    'access denied',
    'file not found',
];
```

The `'COPY'` pattern matches **any** PostgreSQL error containing "COPY" — including data format errors like _"invalid input syntax for type integer in COPY"_ or _"extra data after last expected column in COPY"_. These are **data errors**, not permission errors, and should not trigger fallback to the INSERT strategy. The fallback will silently re-seed from scratch using INSERT, which will also fail (same bad data), producing a confusing double failure.

**Fix:** Remove the bare `'COPY'` pattern. The other patterns (`permission denied`, `must be superuser`, `could not open file`) are sufficient to detect the real permission/access case.

---

### 3. BUG — `SqliteCsvStrategy::parseValue` corrupts string columns

**File:** `src/Strategies/SqliteCsvStrategy.php:108`

```php
if ($value === '0' || $value === '1') {
    return (int) $value;
}
```

Any string column storing `'0'` or `'1'` (e.g. status codes, reference numbers) gets silently cast to `int`. The original data type cannot be recovered from a CSV string, so this heuristic will corrupt valid string data.

**Fix:** Remove this cast entirely. PDO handles binding correctly without it — let the DB cast as needed by the schema. If booleans need special handling, it should be done explicitly in `ValueFormatter::format()`, not during CSV re-reading.

---

### 4. INCONSISTENCY — `CleanupCsvAction::cleanupDirectory` ignores `unlink` failures

**File:** `src/Actions/CleanupCsvAction.php:57`

```php
foreach ($files as $file) {
    if (is_file($file)) {
        unlink($file);  // return value ignored
        $deleted++;
    }
}
```

`__invoke()` correctly checks `unlink()` and throws on failure. `cleanupDirectory()` increments `$deleted` even when `unlink()` returns `false` (e.g. permission error), misreporting the deleted count and leaving temp files on disk silently.

**Fix:** Check `unlink()` return value — either throw or at minimum log the failure, consistent with `__invoke()`.

---

### 5. MINOR — `DatabaseConnectionDTO::fromName` uses undefined `\DB` facade

**File:** `src/DTOs/DatabaseConnectionDTO.php:23`

```php
$connection = \DB::connection($name);
```

The file imports nothing for `DB`. Using `\DB::` works via Laravel's global aliases, but it is inconsistent with every other file in the codebase which properly imports `use Illuminate\Support\Facades\DB`. May also cause PHPStan to flag it depending on config.

**Fix:** Add `use Illuminate\Support\Facades\DB;` and use `DB::connection($name)`.

---

### 6. MINOR — Exception path leaves progress bar in dirty terminal state

**File:** `src/Actions/ExecuteSeederAction.php:64`

```php
} catch (\Throwable $e) {
    $strategy->cleanup(fromException: true);
    // progress tracker is never finished
```

On exception, the progress bar is never finished. Symfony's `ProgressBar` leaves the cursor mid-line if not finished, which garbles subsequent terminal output from Laravel's error handler.

**Fix:** Call `$this->progressTracker->finish()` inside the `catch` block before cleanup (or move it to a `finally` block).
