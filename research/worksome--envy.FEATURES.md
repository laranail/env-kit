# FEATURES — worksome/envy
Source: https://github.com/worksome/envy (homepage repo: worksome/envsync) · v1.5.0 (git tag; CHANGELOG.md only tracks up to v1.1.0) · MIT · group E/F reference

## What it is / entry
Artisan commands + library. A Laravel package that keeps `.env`/`.env.example` files in sync with the `env()` calls made in your config files. It statically parses config PHP (via `nikic/php-parser`), collects every `env('KEY', default)` call, then **syncs** (adds missing keys) or **prunes** (removes orphaned keys) from configured environment files. Registered through `spatie/laravel-package-tools` (`EnvyServiceProvider`). PHP `^8.4`, Laravel `^12.0 || ^13.0`.

## Public API or plugin surface (verified signatures)
Core service class `Worksome\Envy\Envy` (resolved from container; no facade) — all return `Illuminate\Support\Collection`:
- `environmentCalls(bool $excludeCallsWithDefaults = false): Collection<int, EnvironmentCall>` — parse all config files, return sorted env() calls found.
- `pendingUpdates(Collection $environmentCalls, array|null $environmentFilePaths = null): Collection<string, Collection<int, EnvironmentCall>>` — per env-file, the calls that are missing from that file (and not excluded).
- `updateEnvironmentFiles(Collection $pendingUpdates): void` — append missing keys to each env file.
- `updateExclusionsWithPendingUpdates(Collection $pendingUpdates): void` — write the missing keys into the config `exclusions` list instead.
- `pendingPrunes(Collection $environmentCalls, array|null $environmentFilePaths = null): Collection<string, Collection<int, string>>` — per env-file, the variable names present in the file but with no matching env() call (and not in inclusions).
- `pruneEnvironmentFiles(Collection $pendingPrunes): void` — remove those keys from each env file.
- `updateInclusionsWithPendingPrunes(Collection $pendingPrunes): void` — write prunable keys into config `inclusions` list instead.
- `hasPublishedConfigFile(): bool` — whether `config/envy.php` is published.

Supporting/extensible surface:
- Contracts (all rebindable): `Contracts\Finder`, and `Contracts\Actions\*` (FindsEnvironmentCalls, FiltersEnvironmentCalls, FindsEnvironmentVariablesToPrune, ReadsEnvironmentFile, UpdatesEnvironmentFile, PrunesEnvironmentFile, FormatsEnvironmentCall, AddsEnvironmentVariablesToList, ParsesFilterList).
- `Contracts\Filter` with `check(string $environmentVariable): bool` — implementations `Support\Filters\{EqualityFilter, WildcardFilter, RegexFilter, Filter}`. exclusions/inclusions config entries may be plain strings (wrapped as `EqualityFilter`) or `Filter` instances.
- DTOs: `Support\EnvironmentCall` (file, line, key, default, comment), `Support\EnvironmentVariable` (key, value).
- Command classes: `Commands\{InstallCommand, SyncCommand, PruneCommand}`.

## Artisan commands (if any)
- `envy:install` — purpose: publish Envy's config (`vendor:publish --tag=envy-config`), then print getting-started hint. No args/flags.
- `envy:sync {--path=} {--dry} {--force}` — Sync configured `.env` files based on `env()` calls found in config files. `--path` targets one specific env file; `--dry` reports missing vars and exits FAILURE without writing (CI gate); `--force` skips the interactive choice and writes to the env file. Interactive choice: "Add to environment file" / "Add to exclusions" (only if config published) / "Cancel".
- `envy:prune {--path=} {--dry} {--force}` — Prune env variables not found in config files. Same flags. Interactive choice: "Prune environment file" / "Add to inclusions" (only if config published) / "Cancel".

## Config keys
(`config/envy.php`)
- `environment_files` — `[base_path('.env.example')]` — env files to keep in sync.
- `config_files` — `[config_path()]` — files/dirs scanned recursively for `env()` calls (may include vendor config paths).
- `display_comments` — `false` — copy the PHP comment above a config entry into the `.env` as a `#` comment.
- `display_location_hints` — `false` — add a `# See <file>::<line>` comment above each written var.
- `display_default_values` — `true` — write the `env()` default after `KEY=` (scalars only; values with whitespace get quoted). Only takes effect when `exclude_calls_with_defaults` is `false`.
- `exclude_calls_with_defaults` — `true` (config comment/behavior) — when true, `env()` calls that already supply a default are ignored during sync. (Note: the published config does not list this key with a literal default value line — it's documented in comments; the service provider/commands read it via `config('envy.exclude_calls_with_defaults', false)`, so the effective fallback when unpublished is `false`.)
- `exclusions` — large Laravel-default list (ASSET_URL, PUSHER_*, AWS_*, REDIS_*, MAIL_*, SQS_*, SESSION_*, etc.) — keys never inserted during sync.
- `inclusions` — `['MIX_PUSHER_APP_KEY', 'MIX_PUSHER_APP_CLUSTER']` — keys never pruned.

## Patterns to mine
env-sync (diff/check) — exact mechanism:
1. **Find config files**: `LaravelFinder::configFilePaths()` expands each entry in `config_files`; a file is used as-is, a directory is walked recursively (`RecursiveDirectoryIterator`) to collect all file paths.
2. **Find env() calls (AST, not regex)**: `FindEnvironmentCalls` reads each file with `Safe\file_get_contents`, parses it with `nikic/php-parser` (`ParserFactory::createForNewestSupportedVersion()`), and traverses with `EnvCallNodeVisitor` (+ `NodeConnectingVisitor` for parent/comment access). The visitor matches `FuncCall` nodes whose name is the **unqualified** function `env` with ≥1 arg. For each it records `EnvironmentCall(file, startLine, key=print(arg0), default=getDefaultValue(arg1), comment)`. Defaults are captured only for `Scalar`/boolean `ConstFetch` nodes (unless `excludeVariablesWithDefaults` forces capture). Comments come from the previous array item's `comments` attribute. `Envy::environmentCalls()` flattens across all files and sorts by key. With `exclude_calls_with_defaults=true`, calls that have a default are rejected.
3. **Read env files**: `ReadEnvironmentFile` parses the target `.env`/`.env.example` with `vlucas/phpdotenv`'s `Dotenv\Parser\Parser` into `EnvironmentVariable` key/value DTOs.
4. **Diff for sync**: `FilterEnvironmentCalls` (per env file) takes the env() calls, `unique()` by key, **rejects keys already present** in the env file, then **rejects keys matched by any `exclusions` Filter** (`ParseFilterList` turns string entries into `EqualityFilter`; `WildcardFilter`/`RegexFilter` also supported). The remainder is "pending updates".
5. **Write for sync**: `UpdateEnvironmentFile` formats each call via `FormatEnvironmentCall` (`KEY=default`, optional location hint / config comment / quoted default) and **appends** them to the env file with `FILE_APPEND` joined by `PHP_EOL` (purely additive — never reorders/rewrites existing lines).
6. **Diff for prune**: `FindEnvironmentVariablesToPrune` (per env file) takes the keys present in the env file, `diff()` against the set of keys found in env() calls, **rejects keys matched by any `inclusions` Filter**, then unique/sort. The remainder is "pending prunes".
7. **Write for prune**: `PruneEnvironmentFile` removes each key by regex `preg_replace("/(#.*|\r\n?|\n)*^{KEY}=.*$/m", '', $content)` (also eats preceding comment lines/blank lines), then overwrites the file.
8. **Alternative to writing env**: instead of editing env files, sync can push missing keys into the config `exclusions` array and prune can push removable keys into `inclusions` — done by `AddEnvironmentVariablesToList`, which **rewrites `config/envy.php` itself** via php-parser (`AppendEnvironmentVariablesNodeVisitor` mutates the array literal, then `Standard` pretty-printer re-emits the file). Requires the config to be published (`Finder::envyConfigFile()`), else throws `ConfigFileNotFoundException`.

CI usage: `--dry` prints the diff and returns a **FAILURE** exit code when there are pending changes, so `envy:sync --dry` / `envy:prune --dry` act as drift checks; `--force` makes the non-interactive write path (e.g. a bot updating `.env.example`).

## Dependencies
- Runtime: `php ^8.4`, `illuminate/contracts ^12.0||^13.0`, `nikic/php-parser ^4.19.1||^5.0.2` (AST parsing of config), `nunomaduro/termwind ^2.0` (console UI), `spatie/laravel-package-tools ^1.16` (service provider), `thecodingmachine/safe ^3.0` (safe fs/preg wrappers). `vlucas/phpdotenv` used (via `Dotenv\Parser`) transitively through Laravel for reading env files.
- Dev: `pestphp/pest ^4.4` (+ laravel plugin), `larastan/larastan ^3.9`, `orchestra/testbench ^10||^11`, `nunomaduro/collision ^8.1`, `worksome/coding-style ^3.4`.

## Tests
Y — `tests/` (Pest). Feature tests: `tests/Feature/Commands/{SyncCommandTest,PruneCommandTest}.php`, `tests/Feature/EnvyTest.php`, `tests/Feature/Finder/LaravelFinderTest.php`. Unit tests for every Action (`tests/Unit/Actions/*`) and every Filter (`tests/Unit/Support/Filters/*`). Fixtures under `tests/Application/` (sample config + `.env.example` + `environments/` variants). Coverage gate `--min=95` in composer `test:coverage`.

## Notes / corrections
- Package slug is `worksome/envy`; the repo/homepage uses `envsync`/`worksome/envsync`. The Composer `name` keyword is `envsync`. Class/namespace is `Worksome\Envy`.
- No facade is shipped — consume the `Worksome\Envy\Envy` class via the container; the package's heavy lifting is split into single-responsibility `Actions\*` classes each behind a `Contracts\Actions\*` interface (fully rebindable).
- Sync is **append-only** (never rewrites/sorts existing env lines); prune uses a per-key regex delete. Neither command reformats the whole file.
- `exclude_calls_with_defaults` is read with a `false` fallback in code (`SyncCommand`/`config('envy.exclude_calls_with_defaults', false)`), though the published config's prose recommends/ships it effectively as `true`; the literal published array sets it via comment context — confirm the published default if precision matters.
- Filters support exact (`EqualityFilter`), wildcard (`WildcardFilter`, `*`→`\S+` regex), and `RegexFilter`; exclusions/inclusions entries can be raw strings or `Filter` objects.
- `--dry` returns exit code FAILURE (not SUCCESS) when changes are pending — intentional for CI.
- `CHANGELOG.md` is stale (last entry v1.1.0, 2023); the actual checked-out tag is `v1.5.0` and composer targets Laravel 12/13 + PHP 8.4.
