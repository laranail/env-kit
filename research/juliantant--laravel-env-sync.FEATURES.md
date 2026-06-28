# FEATURES — jtant/laravel-env-sync
Source: github.com/JulienTant/Laravel-Env-Sync · no git tags in local clone (composer.json has no `version`; Laravel 7-era release) · MIT · group E/F reference

## What it is / entry
A Laravel package (auto-discovered ServiceProvider `Jtant\LaravelEnvSync\EnvSyncServiceProvider`) that keeps `.env` in sync with `.env.example`. It ships three Artisan commands (`env:sync`, `env:check`, `env:diff`). The provider binds `ReaderInterface → EnvFileReader` and `WriterInterface → EnvFileWriter` and registers the three commands. No facade, no config file.

## Public API or plugin surface (verified signatures)
- `Jtant\LaravelEnvSync\SyncService::__construct(ReaderInterface $reader)`
- `Jtant\LaravelEnvSync\SyncService::getDiff($source, $destination): array` — returns `[key => defaultValue]` of keys present in `$source` but missing from `$destination`. Throws `Jtant\LaravelEnvSync\FileNotFound` if either file is missing (via private `ensureFileExists`).
- `Jtant\LaravelEnvSync\Reader\ReaderInterface::read($resource = null): array` — impl `Reader\File\EnvFileReader` parses an env file into `[name => value]` using `Dotenv::createImmutable(dirname, basename)->load()` (vlucas/phpdotenv ^4). Throws `Reader\File\FileRequired` if `$resource` is null.
- `Jtant\LaravelEnvSync\Writer\WriterInterface::append($resource, $key, $value): void` — impl `Writer\File\EnvFileWriter` appends `KEY=value` to the file.
- `Jtant\LaravelEnvSync\Events\MissingEnvVars` (uses `Dispatchable`) — constructed with `$diffs`; stored in protected `$diffs` (no public getter/property exposed).
- `Reader\File\Loader extends Dotenv\Loader` — a legacy/unused alternate loader (not wired into the container; `EnvFileReader` uses `Dotenv::createImmutable` instead).
- `Console\BaseCommand::getSrcAndDest(): array` — shared `--src`/`--dest` resolution (see below).

## Artisan commands (if any)
- `env:sync {--reverse} {--src=} {--dest=}` — Synchronise `.env` & `.env.example`. For each key in src missing from dest, interactively prompts (choice: `y` copy default / `c` change value / `n` skip; default `y`) then appends. With `--no-interaction` it warns and copies all new keys with their default values unattended.
- `env:check {--src=} {--dest=} {--reverse}` — Check if env files are in sync. Prints missing keys; returns exit code `0` if in sync, `1` if not (script-friendly). Dispatches `MissingEnvVars($diffs)` event ONLY when there are missing vars. Suggests running `php artisan env:sync` (appends ` --reverse` if `--reverse` was used).
- `env:diff {--src=} {--dest=}` — Prints a 3-column table (`Key`, dest basename, src basename) of the union of all keys from both files, sorted; cells for absent keys render `<error>NOT FOUND</error>` and the absence sets the command's return code to `1` (else `0`). Note: `env:diff` does NOT support `--reverse`.

## Config keys
- none (no published config file). Defaults are hardcoded in `BaseCommand::getSrcAndDest()`: src = `base_path('.env.example')`, dest = `base_path('.env')`.

## Patterns to mine
- **env-sync (diff/check) — exact mechanism:**
  - **Source of truth = src = `.env.example`; dest = `.env`** (defaults). `--reverse` swaps them (`[$src,$dest]=[$dest,$src]`) so `.env` becomes the source filling `.env.example`. `--src`/`--dest` override the defaults but are all-or-nothing: supplying one without the other prints `"You must use either both src and dest options, or none."` and `exit(1)`.
  - **Parsing:** each file is read into an associative array `[KEY => value]` by `EnvFileReader::read()` → `Dotenv::createImmutable($dir,$name)->load()`. Only assignment lines become keys; comments/blank lines are skipped by phpdotenv.
  - **Diff detection:** `SyncService::getDiff($source,$destination)` computes `array_diff(array_keys($sourceValues), array_keys($destinationValues))` — i.e. keys present in source but absent in destination (KEY presence only, by name; **values are NOT compared**, so a key with a different value is NOT flagged). It then `array_filter`s the source array (USE_KEY) to return `[missingKey => sourceDefaultValue]`. "Extra" keys in dest that aren't in src are NOT reported by sync/check (they only show as `NOT FOUND` in the src column of `env:diff`).
  - **Writing (sync only):** `EnvFileWriter::append($dest,$key,$value)` appends `KEY=value` to the destination file via `file_put_contents(..., FILE_APPEND)`. Before writing it inspects the file's last char and prepends `PHP_EOL` if the file doesn't already end in `\n`/`\r` (avoids gluing onto the last line). If the value contains a space and no `"`, it wraps the value in double quotes. It appends only — never rewrites, reorders, or removes existing keys, and writes no trailing newline after the pair.
  - **check vs sync:** `env:check` calls the same `getDiff` but never writes — it reports + exit code + event. `env:sync` writes (interactive prompt per key, or bulk via `--no-interaction`).

## Dependencies
- `php >=7.2.5`
- `vlucas/phpdotenv ^4.0` (env parsing via `Dotenv::createImmutable`)
- `illuminate/console ^7.0`, `illuminate/support ^7.0`, `illuminate/events ^7.0`
- dev: `phpunit/phpunit ^8.3`, `mikey179/vfsstream ^1.6`, `mockery/mockery ^1.0`, `orchestra/testbench ^5.0`

## Tests
Y — `tests/SyncServiceTest.php`, `tests/Console/{SyncCommandTest,CheckCommandTest,DiffCommandTest}.php`, `tests/Reader/File/EnvFileReaderTest.php`, `tests/Writer/File/EnvFileWriteTest.php` (uses vfsStream + Orchestra Testbench). `phpunit.xml` at root; CI in `.github/workflows/php.yml`.

## Notes / corrections
- README says the event "will contain the missing env variables" — true at construction (`MissingEnvVars($diffs)`), but `$diffs` is stored in a **protected** property with no accessor, so consumers must add their own listener that reflects/extends it or rely on Laravel's event payload wiring; the class exposes no public getter.
- `Reader\File\Loader` extends `Dotenv\Loader` (phpdotenv internals) but is dead code — the active reader path uses `Dotenv::createImmutable`. It also collides in spirit with the phpdotenv ^4 API (where `Loader` semantics changed), so treat it as legacy.
- Targets Laravel 7 / PHP 7.2 — far below laranail's PHP ^8.3 / Laravel ^13 floor; values use string-only KEY presence diffing. For a laranail re-implementation, mine the clean Reader/Writer/Service interface split and the swap-able `--src`/`--dest` + `--reverse` source-of-truth model, but modernize the phpdotenv usage and consider value-level (not just key-presence) diffing.
- `FileNotFound` (src root) and `Reader\File\FileRequired` are two distinct exceptions: the former from `SyncService` when a path doesn't exist, the latter from the reader when called with a null resource.
