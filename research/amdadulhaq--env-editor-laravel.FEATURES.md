# FEATURES — amdadulhaq/env-editor-laravel
Source: https://github.com/amdad121/env-editor-laravel · unreleased (no git tags; CHANGELOG `## Unreleased`, dev-main) · MIT · group A (programmatic)

## Invocation
**Service class via DI / container singleton — NO Facade.** Concrete class
`AmdadulHaq\EnvEditor\EnvEditor`. The service provider
(`AmdadulHaq\EnvEditor\EnvEditorServiceProvider`) is auto-discovered (composer
`extra.laravel.providers`) and binds a `singleton(EnvEditor::class)` constructed
with `base_path('.env')`. Consume by type-hinting `EnvEditor` for constructor
injection, resolving `app(EnvEditor::class)`, or `new EnvEditor('/path/.env')`
with a custom path.

> CORRECTION: the README shows static facade-style calls (`EnvEditor::set(...)`,
> `EnvEditor::get(...)`), but **all methods are non-static instance methods and
> there is no Facade class in the package**. The README examples would fatal
> ("non-static method called statically"). Real usage is instance/DI based, as
> the tests demonstrate (`$this->editor->set(...)`).

## Public API (verified signatures)
- `__construct(?string $envFile = null)` — sets `$envFile` (defaults to `base_path('.env')` or `.env`) and derives `$backupDir = dirname($envFile).'/.env.backup'` — n/a
- `set(string $key, $value): bool` — if key exists delegates to `update()`, else `append()`s a new formatted line — auto-save (writes file)
- `update(string $key, $value): bool` — `throw_unless` key exists (Exception), then `preg_replace` the `KEY=...` line — auto-save (writes file)
- `setOrUpdate(string $key, $value): bool` — if key exists `update()`, else `set()` — auto-save (writes file)
- `remove(string $key): bool` — returns `true` if key absent; else strips the line + blank lines, writes trimmed content — auto-save (writes file)
- `get(string $key, $default = null)` — regex-reads & `parseValue()`s the value; returns `$default` if not found — none (read; return type untyped, effectively `string|mixed`)
- `getAll(): array` — parses all non-empty/non-comment `KEY=VALUE` lines into an assoc array of strings — none (read)
- `has(string $key): bool` — wraps protected `keyExists()` — none (read)
- `backup(?string $name = null): string` — `mkdir` backup dir if missing, copies `.env` to `$backupDir/$name` (default `Y-m-d_H-i-s.env`), returns full backup path — writes a backup file (does not modify `.env`)
- `restore(string $backupFile): bool` — `throw_unless` backup exists (Exception), `copy()`s backup over `.env`, returns `true` — auto-save (overwrites `.env`)
- `listBackups(): array` — `scandir` of backup dir minus `.`/`..`, reindexed; `[]` if dir missing — none (read)
- `getEnvFile(): string` — returns the resolved `.env` path — none

Protected (not public API): `keyExists`, `append`, `readFile`, `writeFile`, `formatValue`, `parseValue`, `escapeKey`.

## Artisan commands
- none (no `src/Commands/`, no console registration in the service provider)

## Config keys
- none (no config file published; service provider only registers the singleton)

## Dependencies (composer require)
- `php`: `^8.2|^8.3|^8.4|^8.5`
- `illuminate/support`: `^10.0|^11.0|^12.0|^13.0`
- (dev) larastan, pint, orchestra/testbench, pestphp/pest, pest-plugin-laravel, driftingly/rector-laravel

## Persistence model
**auto-save** — every mutating method (`set`/`update`/`setOrUpdate`/`remove`/`restore`)
writes the `.env` file immediately via `file_put_contents` (or `copy` for
restore). There is no buffer and no explicit `save()` method; each call reads the
file fresh, mutates, and rewrites.

## Unique vs jackiedo base
- Not a fork of jackiedo/dotenv-editor — independent, much smaller (~215-line
  single class). No fluent buffer, no `save()`, no method chaining (mutators
  return `bool`, not `$this`).
- Built-in **backup/restore/listBackups** into a sibling `.env.backup/` dir
  (jackiedo also has backups but with a different API/config surface).
- No comment manipulation, no key-line introspection (`getKeys` with metadata),
  no setKeys/setKey arrays, no autobackup config — far thinner surface.
- Values are always returned as **strings** (`parseValue` only unquotes); no
  type casting to bool/int/null.

## Tests
present? **Y** — Pest framework.
- `tests/ExampleTest.php` — 24 feature tests covering get/getAll/has/set/update/setOrUpdate/remove, spaces/quotes/empty/numeric values, comments, backup/restore/listBackups, and the two exception paths. Uses `new EnvEditor($tempFile)` directly (instance API).
- `tests/ArchTest.php` — arch test forbidding `dd`/`dump`/`ray`.
- `tests/TestCase.php` (Orchestra Testbench), `tests/Pest.php`.

## Notes / corrections to the plan
- **No Facade.** Plan should not assume facade access; it is service-class /
  DI only. The README's static examples are wrong.
- **Auto-save, not buffer+save().** No `save()` method exists; do not document one.
- **Return types differ from a typical typed contract:** `get()` and the
  constructor `$value` params are untyped (`mixed`); `get()` returns the parsed
  string or `$default`. Mutators return `bool` (success of file write), not the
  written value or `$this`.
- `update()` and `restore()` throw bare `\Exception` (via `throw_unless`) — no
  custom exception types.
- `remove()` is idempotent: returns `true` when the key is already absent
  (no-op), so a `true` does not guarantee a line was removed.
- `backup()` does **not** touch `.env`; it only copies it aside. Backups live in
  `<dir-of-.env>/.env.backup/`.
- No version tag has been cut (Packagist version would be `dev-main`); CHANGELOG
  is entirely under `## Unreleased`.
