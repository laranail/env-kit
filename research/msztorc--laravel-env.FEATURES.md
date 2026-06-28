# FEATURES — msztorc/laravel-env
Source: https://github.com/msztorc/laravel-env · v1.0.0 (CHANGELOG; no `version` key in composer.json — `renameVariable` added post-1.0.0, unreleased in changelog) · MIT · group A (programmatic)

## Invocation
- **Primary: direct instantiation.** `new msztorc\LaravelEnv\Env()` — the constructor reads `app()->environmentFilePath()` and loads/parses the `.env` immediately. All README examples and tests use `new Env()`.
- **Auto-discovery** via `extra.laravel.providers` → `msztorc\LaravelEnv\LaravelEnvServiceProvider` (registers 5 Artisan commands).
- **Facade** `LaravelEnv` (alias → `msztorc\LaravelEnv\LaravelEnvFacade`, accessor string `'laravel-env'`) is declared in composer `extra.laravel.aliases`, BUT the service provider never binds the `'laravel-env'` container key — it only binds the `command.env:*` keys. So resolving the facade would throw "a facade root has not been set"/binding-resolution error. The facade is effectively dead; use `new Env()`.
- No global helper function shipped.

## Public API (verified signatures)
All on `msztorc\LaravelEnv\Env` (the only public class with state). Verified from `src/Env.php`:

- `__construct()` — loads `app()->environmentFilePath()`, `file_get_contents()` into `$_envContent`, calls `_parse()` — none (read into memory)
- `exists(string $key): bool` — true if key present in parsed vars (re-parses if null) — none
- `getValue(string $key): string` — returns the variable's value or `''` if absent (re-parses if null) — none
- `getKeyValue(string $key): array` — returns `[$key => value]` (note: buggy `?? []` applied to a literal array, so never returns `[]`; throws on missing key value being null only via array build) — none
- `setValue(string $key, string $value, $write = true): string` — prepares/escapes value, regex-replaces existing line or appends `KEY=value`, sets `_changed=true`/`_saved=false`, re-parses, and **writes to disk immediately when `$write` is truthy (default)**; returns `getValue($key)` — persists by default (auto-save unless `$write=false`)
- `renameVariable(string $currentKey, string $newKey, bool $write = true): bool` — returns false if key absent; regex-renames the `KEY=` prefix (preserves value), sets changed/unsaved, re-parses, writes if `$write` (default true); returns true — persists by default
- `deleteVariable(string $key, bool $write = true): bool` — if key exists, regex-removes the line, sets changed/unsaved, writes if `$write` (default true); always returns true — persists by default
- `getVariables(): array` — returns the in-memory parsed `$_envVars` associative array — none
- `getEnvContent(): string` — returns the raw in-memory `$_envContent` string — none
- `write(): bool` — `file_put_contents($_envPath, $_envContent)`, sets and returns `$_saved` — explicit save (flushes buffered in-memory content to disk)
- `isSaved(): bool` — returns `$_saved` flag — none
- `wasChanged(): bool` — returns `$_changed` flag — none

(Private helpers, not public: `_parse`, `_preg_quote_except`, `_prepareValue`, `_stripQuotes`, `_stripValue`.)

### Exact signatures requested — CONFIRMED present
- `public function getValue(string $key): string` ✅
- `public function setValue(string $key, string $value, $write = true): string` ✅ (note: `$write` is untyped, defaults `true`; `$key`/`$value` are typed `string`)
- `public function deleteVariable(string $key, bool $write = true): bool` ✅
- `public function renameVariable(string $currentKey, string $newKey, bool $write = true): bool` ✅
- `public function write(): bool` ✅
- `public function isSaved(): bool` ✅
- `public function wasChanged(): bool` ✅

## Artisan commands
Registered in `LaravelEnvServiceProvider::register()`; each command does `new Env()` per run.
- `env:get {key?} {--key-value} {--json}` — print one var value, or `KEY=value` with `--key-value`, or `{"KEY":"value"}` with `--json`; no key → dump entire content (raw, or vars as JSON with `--json`)
- `env:set {key} {value?}` — set/update a variable (accepts `KEY=value` as a single arg); validates key, writes to `.env`
- `env:del {key}` — delete a variable from `.env`
- `env:list {key?} {--json}` — list all variables (raw content, or JSON of parsed vars with `--json`)
- `env:rename {key} {new-key}` — rename a variable key, preserving value; validates both keys

Note: commands use plain `env:` (colon) names, NOT the `laranail::package-tools.` shape — this is the upstream third-party package, not a laranail package.

## Config keys
none — no published config file; `.env` path comes from `app()->environmentFilePath()`, nothing publishable.

## Dependencies (composer require)
- `php`: `^7.3 || ^7.4 || ^8.0 || ^8.1 || ^8.2 || ^8.3 || ^8.4`
- `illuminate/support`: `^6.0 ... ^13.0`
- `ext-json`: `*`
- require-dev: `friendsofphp/php-cs-fixer ^2||^3`, `orchestra/testbench ^4..^11`, `phpunit/phpunit ^8..^11`

## Persistence model
**hybrid (`$write`)**. The mutators (`setValue`, `renameVariable`, `deleteVariable`) take a `$write` flag defaulting to `true`:
- `$write = true` (default) → the change is applied to the in-memory `$_envContent` AND `write()` is called immediately → **auto-save to disk**.
- `$write = false` → the change is buffered in `$_envContent` only (flags `_changed=true`, `_saved=false`); nothing hits disk until you call `write()` explicitly. This lets you batch multiple edits and flush once.
- `write()` does the single disk flush: `file_put_contents($_envPath, $_envContent)`.
- `isSaved()` tracks whether the current in-memory content has been flushed (`$_saved`, reset to false on every mutation, set true by `write()`). `wasChanged()` tracks whether any mutation occurred this object's lifetime (`$_changed`, latching to true, never reset).
- Caveat: each Artisan command constructs a fresh `Env` and uses the default `$write=true`, so CLI usage is always immediate-write.

## Unique vs jackiedo base
- This is **msztorc/laravel-env**, a standalone package — NOT built on jackiedo/dotenv-editor. No shared lineage; no `jackiedo` dependency anywhere.
- Distinctive vs the jackiedo editor: single flat `Env` class with regex-based line replacement (not a buffer/entry-object model); the hybrid `$write` flag instead of a strict buffer+`save()`; mutators auto-save by default. No backup/restore, no key-existence-aware multi-line formatting/comments preservation, no `setKeys()`/`deleteKeys()` batch helpers — comments and blank lines are dropped from the in-memory parse (though raw `$_envContent` preserves them for `getEnvContent()` output).
- Value handling: `setValue` auto-quotes values containing spaces and `preg_quote`-escapes (excluding `:.-+=`); `getValue` strips quotes and inline `#` comments. Does NOT expand `${VAR}` references — they're returned literally (test asserts `MAIL_FROM_NAME` === `'${APP_NAME}'`).

## Tests
Y — PHPUnit via Orchestra Testbench (`Orchestra\Testbench\TestCase`).
- `tests/EnvClassTest.php` — exercises the programmatic `Env` API (get/set/del/rename, `wasChanged`/`isSaved`, hyphen/URL/empty values, rename-preserves-value, rename-nonexistent).
- `tests/EnvArtisanTest.php` — exercises the Artisan commands.
- `tests/.env.example` — fixture copied to `.env` in `setUp`.
- Config: `phpunit.xml.dist`. CI: `.github/workflows/tests.yml`.

## Notes / corrections to the plan
- **All requested signatures confirmed verbatim** (see CONFIRMED block). One nuance: in `setValue`, `$write` is **untyped** (just `$write = true`), while `renameVariable`/`deleteVariable` type it as `bool $write = true`.
- **The facade is broken/non-functional.** `LaravelEnvFacade::getFacadeAccessor()` returns `'laravel-env'`, but `LaravelEnvServiceProvider` never binds that key — only `command.env:*`. Any plan that documents `LaravelEnv::setValue(...)` facade usage is wrong; the supported path is `new Env()`.
- **No singleton / DI binding for `Env`** — it is always `new Env()`, re-reading the `.env` from disk on every construction. Two `Env` instances do not share state.
- **Comments & blank lines are not round-tripped through the parsed array** — `getVariables()`/`--json` lose them; only the raw `getEnvContent()` keeps the original text (minus whatever the regex mutators alter).
- `getKeyValue()` has a latent bug: `return [$key => $this->_envVars[$key]] ?? [];` — the `?? []` is dead (a literal array is never null), and if the key is missing it emits an undefined-index notice rather than returning `[]`.
- `version` is absent from composer.json; CHANGELOG's only entry is `1.0.0 (2020-05-16)`, yet `renameVariable` + its tests exist — so the installed source is ahead of the documented changelog. Treat "version" as the git/tag state, not a composer field.
