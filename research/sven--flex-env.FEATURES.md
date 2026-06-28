# FEATURES — sven/flex-env
Source: sven/flex-env (github svenluijten/flex-env) · v2-era (composer requires PHP ^8.0, illuminate/laravel ^8.0||^9.0) · MIT · group B CLI

## Invocation / entry
Laravel package auto-discovered via `extra.laravel.providers` →
`Sven\FlexEnv\FlexEnvServiceProvider`. The provider's `register()` wires four
Artisan commands. No facade, no web routes. Core logic lives in the standalone
`Sven\FlexEnv\Env` class (instantiable directly, used by both commands and tests).

## Artisan commands (verified)
Registered in `FlexEnvServiceProvider::register()` (`SetEnv`, `GetEnv`, `DeleteEnv`, `ListEnv`):
- `env:set {key} {value} [--L|--line-break]` — uppercases key, casts value to string,
  sets it in `.env`; with `-L`/`--line-break` prepends a newline before the entry.
  Verifies by re-reading; on mismatch calls `rollback()` and errors.
- `env:get {key}` — uppercases key, strips quotes, prints the value or errors if missing.
- `env:delete {key}` — uppercases key, deletes the entry. (Logic is inverted/buggy — see Notes.)
- `env:list` — prints a `Key | Value` table of all parsed entries.

## Public API (`Sven\FlexEnv\Env`)
- `__construct($path)` — creates the file if absent; caches previous contents for rollback.
- `get($key)` — return value for key (string, or null/empty if absent).
- `set($key, $value, $linebreak = false)` — upsert; bool→`true`/`false`; quotes values
  matching `/\W\D/`; returns `$this`.
- `delete($key)` — remove entry; returns `$this`.
- `all()` — return assoc array of all key/value pairs.
- `copy($destination, $excludeValues = false)` — **stub: body is a no-op** (parses file, does nothing).
- `rollback()` — restore the file to its pre-command contents; returns `$this`.
- `getPath()` — full path to the `.env` file.
- `replaceInFile($old, $new, $append = 0)` — regex replace within file contents.
- const `COPY_FOR_DISTRIBUTION = true`.

## Config keys
None — no publishable config file; the `.env` path is hardcoded to `base_path('.env')`.

## Dependencies
- `php: ^8.0`
- `illuminate/support: ^8.0 || ^9.0`
- `laravel/framework: ^8.0 || ^9.0`
- dev: `graham-campbell/testbench: ^5.0`

## Unique selling points
- Dead-simple human-readable CLI to read/write/delete/list `.env` entries — "never touch the mouse again".
- Auto-quoting of values with special characters/spaces; boolean coercion.
- Rollback safety net: write commands verify the result and revert on failure.

## Tests
Y — `tests/EnvTest.php` + `tests/EnvTestCase.php`, PHPUnit (`phpunit.xml.dist`) via
`graham-campbell/testbench` (`AbstractPackageTestCase`). 10 tests, all against the
`Env` class directly (get/set/delete/all, spaces, booleans, special chars). No command-level tests.

## Notes / corrections
- COMMAND NAMES CONFIRMED EXACT. Quoting each `$signature`:
  - `SetEnv`: `'env:set ... {key ...} {value ...} {--L|line-break ...}'` (multi-line signature).
  - `GetEnv`: `'env:get {key}'`.
  - `DeleteEnv`: `'env:delete {key}'`.
  - `ListEnv`: `'env:list'`.
  So they are exactly `env:set`, `env:get`, `env:delete`, `env:list` (NOT bare `set`/`get`/...).
- `-L` / `--line-break` flag CONFIRMED. In `SetEnv`, the option is declared as
  `{--L|line-break : Whether or not the command should insert a linebreak before the entry.}`.
  Laravel reads it via `$this->option('line-break')`, cast to bool. So usage is
  `--line-break` or shortcut `-L`. (README writes it `--line-break|-L`; same flag, order flipped.)
  In `Env::set()` it produces `"\n$key=$value"` instead of `"$key=$value"` — i.e. inserts a
  leading newline before the entry.
- A FIFTH command class exists but is NOT registered: `src/Commands/ExampleEnv.php`
  (`$signature = 'env:example {--name=.env.example ...}'`, description "Generate an environment
  file for distribution"). It is absent from `FlexEnvServiceProvider`, so `env:example` is NOT
  available, and it depends on `Env::copy()` which is an unimplemented stub. Effectively dead code.
- `DeleteEnv::handle()` success/failure logic looks inverted: after `delete()->get($key)`, it
  emits the "nothing was changed / rollback" message when the result is non-empty, and "successfully
  deleted" otherwise — but a successful delete yields an empty/null value, so the success path fires
  correctly only by that coincidence; the messaging branch is fragile.
- `parseFile()` regex `([a-zA-Z_-]+)\=(.+)` won't match keys containing digits (e.g. `S3_KEY`)
  or empty values; values are split on the first `=`.
- `.env` path is always `base_path('.env')` in commands — not configurable.
