# FEATURES — tamer-dev/laravel-env-cli
Source: https://github.com/tamer-dev/laravel-env-cli · dev-only (minimum-stability: dev, no tagged version in source) · MIT · group B CLI

## Invocation / entry
No facade. Pure Artisan commands, auto-registered via Laravel package discovery
(`extra.laravel.providers` → `EnvironmentCommandsServiceProvider`). The provider
binds and registers four commands in `register()`:
`command.env:set`, `command.env:read`, `command.env:backup`, `command.env:restore`.
No web routes.

## Artisan commands (verified)
Exact `$signature` quoted from each command class:

- `env:set {key} {value?} {--file=} {--b|backup}` — `EnvironmentSetCommand`.
  Sets/updates a key in the env file. Accepts `key value`, or a single
  `key=value` argument. Values containing a space are wrapped in double quotes.
  Key is upper-cased. Updates in place if found, otherwise appends `KEY=value\r\n`.
  `--file` selects a custom env file; when omitted it uses
  `app()->environmentFilePath()` (the real `.env`). `-b`/`--backup` writes a
  backup (`<path>.backup_<YmdHis>`) BEFORE writing changes. Output:
  "Environment variable with key '{KEY}' has been changed from '{old}' to '{new}'"
  or "A new environment variable with key '{KEY}' has been set to '{value}'".
- `env:read {key} {--file=.env}` — `EnvironmentReadCommand`. Reads a key's value
  line-by-line from the env file. Key upper-cased; rejects keys containing `=`.
  `--file` defaults to `.env`. Output: "Environment variable with key [KEY] have
  value [value] file used is {file}" or "...not found".
- `env:backup {--file=.env}` — `EnvironmentBackupCommand`. Copies the env file to
  `<path>.backup_<YmdHis>`. `--file` defaults to `.env`. Output: "A new
  environment backup file has been created in this path '{newPath}'".
- `env:restore {backupedFileName} {--file=.env}` — `EnvironmentRestoreCommand`.
  Overwrites the env file with the contents of the named backup file. Both files
  must already exist (else throws). `--file` defaults to `.env`. Output: "the env
  file '{env}' has been restored from this file path '{backup}'".

## Public API (if any)
None intended for programmatic use. Command classes expose `handle()` plus
`protected`/`public` helpers (`getEnvFilePath()`, `readValue()`,
`getProcessedFileContent()`, `openFile()`, `makeBackup()`, `makeRestore()`,
`writeFile()`) — internal, not a documented API.

## Config keys
None. No config file, no `mergeConfigFrom`, no publishable assets. Behavior is
driven entirely by command arguments/options.

## Dependencies
- `php`: `^7.1|^8.0|^8.1`
- `illuminate/support`: `^5.7|^6.0|^7.0|^8.0|^9.0|^10.0`
- dev: `phpunit/phpunit`: `8.5.x-dev` (declared but unused — no tests shipped)

## Unique selling points
- Four-in-one env CLI: set, read, backup, restore in a single tiny package.
- Built-in timestamped backups (`.backup_<YmdHis>`), inline `-b` backup on set,
  and one-command restore.
- `--file` option on every command lets you target alternate env files
  (`.env.example`, `.env.testing`, etc.).
- APP_KEY safety guard on `env:set` (see Notes).
- Inspired by `imliam/laravel-env-set-command` but adds read/backup/restore.

## Tests
N — no tests in the repo. `phpunit/phpunit 8.5.x-dev` is in `require-dev` but
there is no `tests/` directory and no PHPUnit config. README "Contribution"
section explicitly lists "add tests" as a wanted task.

## Notes / corrections
- READ COMMAND IS `env:read`, NOT `env:get`. Verified directly:
  `EnvironmentReadCommand::$signature = 'env:read {key} {--file=.env}'`. There is
  no `env:get` anywhere in the source.
- All four command signatures (verbatim):
  - set:     `env:set {key} {value?} {--file=} {--b|backup}`
  - read:    `env:read {key} {--file=.env}`
  - backup:  `env:backup {--file=.env}`
  - restore: `env:restore {backupedFileName} {--file=.env}`
- `--file` flag: present on ALL FOUR commands. Default differs by command:
  read/backup/restore default to `.env`; SET's `--file=` has NO default — when
  omitted, set resolves the path via `app()->environmentFilePath()` instead of
  `base_path('.env')`. read/backup/restore always resolve via
  `base_path($this->option('file'))`.
- `-b`/`--backup` flag: ONLY on `env:set` (`{--b|backup}`). Not on read/backup/
  restore. (`env:backup` is itself the standalone backup command.)
- APP_KEY GUARD: CONFIRMED, only in `env:set`. In
  `EnvironmentSetCommand::isValidKey()`:
  ```php
  if($key =="APP_KEY" ){
      throw new InvalidArgumentException('Environment {APP_KEY} should not be set by this command. it is better to use the native one {php artisan key:generate}');
  }
  ```
  It throws (refuses the write) when the key equals exactly `APP_KEY`. Note the
  check runs BEFORE upper-casing, so lowercase `app_key` would NOT be caught by
  this branch — but it would still be rejected earlier because `isValidKey`
  enforces `/^[a-zA-Z_]+$/` and the value-less form requires a `=`. (`env:read`,
  `env:backup`, `env:restore` have no APP_KEY guard — they can read/restore it.)
- `env:set` key validation: rejects keys containing `=` and keys not matching
  `^[a-zA-Z_]+$` ("Only use letters and underscores" — so digits/hyphens are
  rejected). `env:read` only rejects keys containing `=`.
- Line endings: writes use `\r\n` (CRLF) hardcoded for new/updated lines.
- No tagged release in this source clone; `minimum-stability: dev`. Supports
  Laravel 5.7–10 per README/composer (not Laravel 11+).
