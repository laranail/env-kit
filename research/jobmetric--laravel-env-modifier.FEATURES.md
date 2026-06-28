# FEATURES — jobmetric/laravel-env-modifier
Source: https://github.com/jobmetric/laravel-env-modifier · no tagged version in composer.json (require `jobmetric/laravel-package-core ^1.26`) · MIT · group A (programmatic)

## Invocation
- **Facade FQCN:** `JobMetric\EnvModifier\Facades\EnvModifier` (Laravel alias `EnvModifier`).
- **Service class:** `JobMetric\EnvModifier\EnvModifier` (the main class; all logic lives here, no separate manager/editor split).
- **Global helpers:** 14 procedural wrappers autoloaded via `src/helpers.php` (composer `autoload.files`), all delegating to the facade — see below.
- **Auto-discovery:** Yes. `extra.laravel.providers` registers `EnvModifierServiceProvider`; `extra.laravel.aliases` registers the `EnvModifier` facade. The provider extends `PackageCoreServiceProvider` and binds the service as a **SINGLETON** under the container key `EnvModifier` (`->registerClass('EnvModifier', EnvModifier::class, RegisterClassTypeEnum::SINGLETON())`). The facade accessor returns `'EnvModifier'`.
- Note: because the service is a singleton, the bound `$filePath` persists across calls within a request — `setPath()`/`createFile(bindToPath:true)` set state used by later calls.

## Public API (verified signatures)
All public methods are on `JobMetric\EnvModifier\EnvModifier`. There is no config-key buffer; "persists?" below means whether the call writes to disk.

- `setPath(string $path): static` — validate file exists and bind it as the working path for later ops — persists? none (state only; throws `EnvFileNotFoundException` if missing).
- `createFile(string $path, array|string|null $content = null, bool $overwrite = false, bool $bindToPath = true): static` — create dir (0775 recursive) + write a new file; array content rendered to `KEY=VALUE` lines, string written as-is (trailing newline ensured), null = empty file; throws `RuntimeException` if exists and `$overwrite` false — persists? **auto-save** (writes immediately via `file_put_contents(..., LOCK_EX)`).
- `deleteFile(bool $force = false, ?string $mainEnvAbsolutePath = null): void` — delete the bound file; if it realpath-matches `$mainEnvAbsolutePath` and `$force` is false, blocks with `RuntimeException`; throws `EnvFileNotFoundException` if unset/missing — persists? **auto-save** (unlink).
- `backup(string $suffix = '.bak'): string` — copy bound file to `{path}{suffix}.{Ymd_His}`; returns the backup absolute path — persists? **auto-save** (creates backup file).
- `restore(string $backupPath, bool $bindToPath = false): static` — read backup and write its content into the bound path; optionally rebind to backup path — persists? **auto-save** (writes to bound path via `writeContent`).
- `mergeFromPath(string $path, array $only = [], array $except = []): static` — parse a source env file, optional allow (`$only`) / deny (`$except`) filtering via `array_intersect_key`/`array_diff_key`, then `set()` onto the bound file — persists? **auto-save** (delegates to `set()`).
- `all(): array` — return all non-comment `KEY=VALUE` pairs from the bound file as assoc array — persists? none (read).
- `get(...$keys): array` — variadic/nested keys flattened; returns assoc `key=>value`, missing keys as `''` — persists? none (read).
- `set(array $data): static` — upsert each key (update existing non-comment line or append) — persists? **auto-save** (single `writeContent` after looping all keys).
- `setIfMissing(array $data): static` — write only keys whose current value is `''` (missing or empty); preserves existing non-empty values — persists? **auto-save**.
- `rename(string $from, string $to, bool $overwrite = false): static` — move value `$from`→`$to`; no-op if equal, no-op if source value is `''`; throws `RuntimeException` if `$to` exists and `$overwrite` false — persists? **auto-save** (writes if a move happened; early-returns without writing on no-op).
- `delete(...$keys): static` — remove matching non-comment lines (variadic/nested keys); collapses 3+ consecutive blank lines to a double newline afterward — persists? **auto-save**.
- `has(string $key): bool` — true if a non-commented `KEY=` line exists (key regex-escaped via `preg_quote`) — persists? none (read).

Private helpers (not API, for completeness): `getContent`, `writeContent`, `ensurePathSet`, `getKeyFromContent`, `setKeyIntoContent`, `deleteKeyFromContent`, `parseAllFromContent`, `flattenKeys`, `normalizeValueForWrite`, `stripQuotes`, `renderKeyValueLine`, `safeRealpath`.

### Global helper functions (src/helpers.php → facade)
- `env_modifier_use(string $path): void` → `setPath`
- `env_modifier_create(string $path, array|string|null $content = null, bool $overwrite = false, bool $bindToPath = true): void` → `createFile`
- `env_modifier_delete_file(bool $force = false, ?string $mainEnvAbsolutePath = null): void` → `deleteFile`
- `env_modifier_all(): array` → `all`
- `env_modifier_get(...$keys): array` → `get`
- `env_modifier_has(string $key): bool` → `has`
- `env_modifier_put(string $key, mixed $value): void` → `set([$key => $value])`
- `env_modifier_set(array $data): void` → `set`
- `env_modifier_set_if_missing(array $data): void` → `setIfMissing`
- `env_modifier_rename(string $from, string $to, bool $overwrite = false): void` → `rename`
- `env_modifier_forget(...$keys): void` → `delete`
- `env_modifier_backup(string $suffix = '.bak'): string` → `backup`
- `env_modifier_restore(string $backupPath, bool $bindToPath = false): void` → `restore`
- `env_modifier_merge_from(string $path, array $only = [], array $except = []): void` → `mergeFromPath`

## Artisan commands
none — no `src/Commands/` directory, no console commands registered in the provider. Programmatic-only package.

## Config keys
none — no config file is shipped or published. The only "config" is the runtime-bound `$filePath` (private property set via `setPath`/`createFile`).

## Dependencies (composer require)
- `php` `>=8.0.1`
- `laravel/framework` `>=9.19`
- `jobmetric/laravel-package-core` `^1.26` (provides `PackageCoreServiceProvider`, `PackageCore`, `RegisterClassTypeEnum`)
- dev: `orchestra/testbench` (used by tests; not pinned in the composer.json shown — pulled transitively/in autoload-dev namespace `JobMetric\EnvModifier\Tests\`)

## Persistence model
**auto-save** — every mutating method writes to disk immediately; there is no buffer and no explicit `save()`. Reads (`get`/`all`/`has`) re-read the file each call. The atomic write is centralized in two spots:

`writeContent()` (used by set/setIfMissing/rename/delete/restore):
```php
file_put_contents($this->filePath, $content, LOCK_EX);
```
and `createFile()`:
```php
file_put_contents($path, $payload, LOCK_EX);
```
**CONFIRMED: uses `file_put_contents` with `LOCK_EX`** (exclusive lock, in-place — not a temp-file-rename atomic swap, despite the "atomically" docblock wording). No `rename()`/tmpfile staging is used.

### Value normalization (write) — CONFIRMED
From `normalizeValueForWrite(mixed $value): string`:
```php
if ($value === null) { return ''; }
if (is_bool($value)) { return $value ? 'true' : 'false'; }
if (is_array($value) || is_object($value)) {
    $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
$str = (string) $value;
$str = str_replace(["\r\n", "\r", "\n"], '\n', $str);   // newlines → literal \n
$needsQuotes =
    preg_match('/\s/', $str) ||      // any whitespace
    str_contains($str, '#') ||       // comment char
    str_contains($str, '=') ||       // equals
    $str !== trim($str);             // leading/trailing ws
if ($needsQuotes) {
    $escaped = str_replace('"', '\"', $str);   // escape inner double-quotes
    return '"' . $escaped . '"';               // wrap in double quotes
}
return $str;
```
Rules: null→`''`; bool→`true`/`false`; array/object→JSON (unescaped unicode+slashes); CR/LF→literal `\n`; wraps value in double quotes (escaping inner `"` as `\"`) if it contains whitespace, `#`, `=`, or has leading/trailing whitespace; otherwise written bare.

Read-side counterpart `stripQuotes()`: strips one layer of surrounding `"`/`'`, unescapes `\"`/`\'`, and converts literal `\n` back to real newlines; bare values still get `\n`→newline conversion.

Key handling: keys are `preg_quote($key, '/')`-escaped in all read/write/delete regexes to prevent pattern injection. Line matching ignores commented lines via the `(?!\s*#)` negative lookahead.

## Unique vs jackiedo base
(jackiedo/dotenv-editor is the common reference base for this category.)
- **No load/save buffer model.** jackiedo uses `load()` → in-memory buffer → `save()`. Here every op is auto-save; no `getBuffer`/`save`/`getKeys(parsed-with-comments)` introspection.
- **No comment-aware key API.** jackiedo can set/get a key's inline comment and read empty/comment lines as structured entries; this package only preserves comments/blank lines passively (regex line-targeting) and never parses or writes comments.
- **File-level lifecycle is richer here:** `createFile`, `deleteFile` (with main-`.env` realpath protection + `force`), `backup` (timestamped), `restore`, `mergeFromPath` (with `only`/`except` filters). jackiedo has backup/restore but not the merge-with-filter or main-env deletion guard.
- **`setIfMissing`** (fill-only-blanks) and **`rename`** (with overwrite guard) are first-class methods.
- **Atomic-ish write via `LOCK_EX`** (jackiedo writes without the exclusive-lock flag).
- **Rich value normalization**: bool/array/object/null coercion + auto-quote heuristic + newline escaping, applied uniformly on write.
- **Variadic + nested key flattening** for `get`/`delete` (`get('A', ['B','C'])`).
- **Procedural global helper layer** (14 `env_modifier_*` functions) in addition to the facade.

## Tests
Y — `tests/` directory, **PHPUnit via Orchestra Testbench** (`TestCase extends Orchestra\Testbench\TestCase`, registers `EnvModifierServiceProvider`). Three suites, 16 `public function test*` methods total:
- `/Users/imanimanyara/Artisan/projects/opensource/laranail/env-tools/headless/research/_src/jobmetric--laravel-env-modifier/tests/EnvModifierTest.php` (6)
- `/Users/imanimanyara/Artisan/projects/opensource/laranail/env-tools/headless/research/_src/jobmetric--laravel-env-modifier/tests/EnvModifierFileOpsTest.php` (5)
- `/Users/imanimanyara/Artisan/projects/opensource/laranail/env-tools/headless/research/_src/jobmetric--laravel-env-modifier/tests/EnvModifierEdgeCasesTest.php` (5)
- `/Users/imanimanyara/Artisan/projects/opensource/laranail/env-tools/headless/research/_src/jobmetric--laravel-env-modifier/tests/TestCase.php` (base)
CI: `.github/workflows/tests.yml`.

## Notes / corrections to the plan
- **"Atomic" is overstated.** The docblock says "Writes atomically using LOCK_EX," but it's an in-place `file_put_contents` with an advisory exclusive lock — NOT a write-to-temp + atomic `rename()`. A crash mid-write can still truncate/corrupt the file; `LOCK_EX` only serializes concurrent writers (and only against other `flock`-respecting writers). If the plan needs true atomicity, this package does not provide it.
- **Read methods normalize values lossily.** `get`/`all` strip quotes and convert `\n`→newline, so round-tripping a value that literally contained the two-char sequence `\n` is not preserved.
- **Singleton state is shared per-request.** Because the service is a container singleton and `$filePath` is instance state, calling `setPath`/`createFile(bindToPath:true)` mutates shared state for all later facade/helper calls in the same request — relevant for multi-file workflows.
- **`createFile` writes even when `$bindToPath` is false** and does NOT require a prior path; `setPath` requires the file to already exist (so the typical bootstrap for a brand-new file is `createFile`, not `setPath`).
- **`rename`/`set` treat empty-string as "absent":** `rename` no-ops if the source value reads as `''`, and `setIfMissing` overwrites any key whose value is `''`. An intentionally-empty existing key is therefore indistinguishable from a missing one.
- **`delete` post-processes blank lines** (`preg_replace("/\R{3,}/", PHP_EOL.PHP_EOL, ...)`) — collapses 3+ blank lines to one blank line; other formatting preserved.
- **Exception model is thin:** only `EnvFileNotFoundException` (custom, code 400); all other failures throw raw `\RuntimeException`.
- **Version:** composer.json carries no `version` field and the repo default branch is `master` (per README badge URLs); pin by commit when vendoring.
