# FEATURES — imliam/laravel-env-set-command
Source: https://github.com/imliam/laravel-env-set-command · v3.0.0 (git tag; CHANGELOG stops at 1.2.0) · MIT · group B CLI

## Invocation / entry
Auto-discovered Laravel package. `EnvironmentSetCommandServiceProvider` (registered via
`extra.laravel.providers`) binds `command.env:set` and registers the single Artisan command
`env:set`. No facade, no web routes.

## Artisan commands (verified)
- `env:set {key} {value?} {env_file?}` — set/update (or create) a variable in the `.env` file.
  Exact `$signature`:
  `env:set {key : Key or "key=value" pair} {value? : Value} {env_file? : Optional path to the .env file}`
  Behavior:
  - Two-arg form: `php artisan env:set APP_NAME Example` → key uppercased, written as `APP_NAME=Example`.
  - Shorthand `KEY=VALUE` single-arg form: `php artisan env:set editor=vscode` → key parsed from the
    `=`, becomes `EDITOR=vscode` (key is `strtoupper`'d).
  - Key validation: must match `^[a-zA-Z_]+$` (letters + underscores only, no digits, no `=`);
    invalid keys print an error and abort (e.g. `@pp_n@me`, `1test`, `test_1` all rejected — note
    digits are NOT allowed).
  - Existing key is replaced in place (case-insensitive match, preserves nesting so `APP_KEY` ≠
    `PUSHER_APP_KEY`); a non-existing key is appended as `\nKEY=value\n`.
  - Values containing whitespace or `=` get wrapped in double quotes automatically (existing quotes
    escaped as `\"`).
  - Prints which env file is used, plus a "changed from X to Y" or "new variable set" message.
  - Custom env file path supported (see Notes).

## Public API (if any)
The command class exposes several `public` methods (used by its own tests; not a documented SPI):
- `setEnvVariable(string $envFileContent, string $key, string $value): array` — returns
  `[newEnvFileContent, bool isNewVariableSet]`; does the quote-wrapping + in-place/append logic.
- `readKeyValuePair(string $envFileContent, string $key): ?string` — returns the original
  `key=value` line (case-insensitive) or null.
- `parseCommandArguments(string $_key, ?string $_value, ?string $_envFilePath): array` — returns
  `[KEY(uppercased), value, ?realpath(envFilePath)]`; parses the `key=value` shorthand and resolves
  the optional file-path argument.
- `assertKeyIsValid(string $key): bool` — throws `InvalidArgumentException` on invalid keys.
- Constants: `COMMAND_NAME='env:set'`, `ARGUMENT_KEY='key'`, `ARGUMENT_VALUE='value'`,
  `ARGUMENT_ENV_FILE='env_file'`.

## Config keys
None. No published config file; no config keys read.

## Dependencies
- `php ^8.3`
- `illuminate/support ^12.0|^13.0`
- `illuminate/console ^12.0|^13.0`
- dev: `orchestra/testbench ^10.0|^11.0`, `phpunit/phpunit ^11.0`, `roave/security-advisories dev-master`

## Unique selling points
- Single-purpose, zero-config: set a `.env` var from the CLI without hand-editing the file.
- Three input shapes: `key value`, `key=value` shorthand, plus optional explicit env-file path.
- Automatic quoting of values with spaces / `=`; nesting-safe in-place replacement (won't clobber
  `PUSHER_APP_KEY` when setting `APP_KEY`); case-insensitive key matching but keys are stored
  upper-cased.
- Writes atomically-ish with `file_put_contents(..., LOCK_EX)`.

## Tests
Y — `tests/Unit/EnvironmentSetCommandTest.php` (+ `tests/ReflectionHelper.php` trait), PHPUnit
(`PHPUnit\Framework\TestCase`, `phpunit.xml`, GitHub Actions `.github/workflows/main.yml`). Heavy
data-provider coverage of `setEnvVariable`, `readKeyValuePair`, `parseCommandArguments`,
`assertKeyIsValid`. Note: tests instantiate the command directly and exercise the parsing/replace
methods; there is no full Artisan integration test of `handle()`.

## Notes / corrections
- VERIFIED — `KEY=VALUE` shorthand (single-arg form): YES. `parseCommandArguments` regex
  `#^([^=]+)=(.*)$#umU` splits the first arg on the first `=`. `php artisan env:set editor=vscode`
  works; key is then upper-cased to `EDITOR`.
- VERIFIED — quoted values with spaces: YES, two ways. (1) Pass an already-quoted value
  (`env:set app_name "Example App"` → the shell passes `Example App`; the command stores it wrapped
  in quotes). (2) Auto-wrapping: any value containing whitespace or `=` is wrapped in double quotes
  by `setEnvVariable` / `parseCommandArguments` (e.g. value `this is a value` → `"this is a value"`,
  value `MY.NAME & C.` → `"MY.NAME & C."`). Existing `"` chars are escaped to `\"`.
- VERIFIED — custom file path argument: YES. The exact signature is
  `env:set {key : Key or "key=value" pair} {value? : Value} {env_file? : Optional path to the .env file}`.
  In two-arg form the path is the 3rd argument: `php artisan env:set APP_NAME TestApp /var/www/my_own_env.env`.
  With `key=value` shorthand the path moves to the 2nd argument:
  `php artisan env:set APP_NAME=TestApp /var/www/my_own_env.env`. Path is resolved with `realpath()`;
  if omitted, `App::environmentFilePath()` is used.
- Caveat: because `realpath()` is applied to the path argument, the target env file must already
  exist (realpath returns false for non-existent paths), and `handle()` then `file_get_contents()`
  on it — so this is best for editing an existing external `.env`, not creating a brand-new file at
  an arbitrary path.
- CHANGELOG.md is stale: it only documents up through `1.2.0` (2020), but the repo is tagged
  `v3.0.0` and `composer.json` targets Laravel 12/13 + PHP 8.3. The `[Unreleased]` section is empty.
- Key constraint is stricter than typical `.env` usage: only `[a-zA-Z_]+` — digits are rejected, so
  a key like `S3_BUCKET` or `OAUTH2` would be refused by `assertKeyIsValid`.
