# FEATURES — brotzka/laravel-dotenv-editor
Source: github.com/Brotzka/laravel-dotenv-editor · v2.x (no version in composer.json; `minimum-stability: stable`) · MIT · group C web-UI

## Invocation / entry
- **Facade**: `DotenvEditor` (alias `Brotzka\DotenvEditor\DotenvEditorFacade`), facade accessor `'brotzka-dotenveditor'`, bound in the service provider to a fresh `DotenvEditor` instance.
- **Direct instantiation**: `new Brotzka\DotenvEditor\DotenvEditor()` (README's documented usage; constructor takes no args, reads config).
- **Web UI**: route group auto-loaded from `routes/web.php` (only registered when `config('dotenveditor.activated')` is true). Default prefix `admin/env`, name prefix `admin.env.`, middleware `['web','admin']`, controller namespace `Brotzka\DotenvEditor\Http\Controllers`.
- **No Artisan commands, no Nova tool.**

## Artisan commands (verified)
- none

## Web routes (verified, from `routes/web.php`)
- `GET  /` → `EnvController@overview` (name `index`) — Vue GUI overview page
- `POST /add` → `add` — add one key/value (`request->key`, `request->value`)
- `POST /update` → `update` — change one existing key's value
- `GET  /createbackup` → `createBackup` — timestamped backup
- `GET  /deletebackup/{timestamp}` → `deleteBackup`
- `GET  /restore/{backuptimestamp}` → `restore`
- `POST /delete` → `delete` — delete entry by `request->key`
- `GET  /download/{filename?}` → `download` — download a backup file or the live `.env`
- `POST /upload` → `upload` — upload a file, moved into project root as `.env` (overwrites)
- `GET  /getdetails/{timestamp?}` → `getDetails` — returns env (or backup) content as JSON

## Public API (verified signatures from `src/DotenvEditor.php`)
- `__construct()` — reads `dotenveditor.backupPath` / `pathToEnv` / `filePermissions`; creates backup dir if missing; returns false (no-op) if `.env` does not exist.
- `getBackupPath()` — current backup path.
- `setBackupPath($path)` — set/create backup dir; returns bool.
- `keyExists($key)` — bool, checks parsed `.env` array.
- `getValue($key)` — returns `env($key)`; throws `DotEnvException` if key missing.
- `setAutoBackup($onOff)` — enable/disable auto-backup; throws `DotEnvException` if not bool.
- `autoBackupEnabled()` — bool.
- `createBackup()` — copies `.env` to `{backupPath}{time()}_env`; returns bool (copy result).
- `getLatestBackup()` — **protected** — latest timestamp.
- `restoreBackup($timestamp = null)` — restore given timestamp, or latest if null; returns copy() result.
- `getFile($timestamp)` — **protected** — path of a backup file; throws if not found.
- `getBackupVersions()` — array of `['formatted' => 'Y-m-d H:i:s', 'unformatted' => <ts>]`; throws `DotEnvException` if none.
- `getBackupFile($timestamp)` — full path to backup file; throws if not found.
- `deleteBackup($timestamp)` — unlink backup file; throws if not found.
- `getContent($timestamp = null)` — parsed assoc array of current env (or a backup).
- `envToArray($file)` — **protected** — parses file to key=>value array (skips `# ` comments and blank lines, splits on first `=`, drops empty keys).
- `getAsJson($timestamp = null)` — JSON array of `{key,value}` objects.
- `envToJson($file = [])` — **private**.
- `save($array)` — **protected** — writes assoc array back to `.env` (joins `key=value` with `\n`).
- `changeEnv($data = array())` — update values for **existing** keys only; auto-backups if enabled; throws `DotEnvException` if empty.
- `addData($data = array())` — append new key=>value pairs (sanitized); auto-backups if enabled; throws if empty.
- `santize($value = '')` — quote-normalizes a value (wraps in matching quotes / quotes values containing whitespace). [sic — method literally named "santize".]
- `isStartOrEndWith($value, $string = '')` — bool helper.
- `setStartAndEndWith($value, $string = '"')` — wrap helper.
- `deleteData($data = array())` — delete keys; **expects a numeric-indexed array of key names** (throws `DotEnvException` if any array key is non-numeric); auto-backups if enabled.

## Config keys (`config/dotenveditor.php`)
- `pathToEnv` — `base_path('.env')` — location of the `.env` to edit.
- `backupPath` — `resource_path('backups/dotenv-editor/')` — where timestamped backups are stored.
- `filePermissions` — `env('FILE_PERMISSIONS', 0755)` — mkdir mode for backup dir.
- `activated` — `true` — master switch that registers/unregisters the GUI routes.
- `template` — `'adminlte::page'` (shipped default; commented alt `'dotenv-editor::master'`) — wrapping Blade layout the overview extends.
- `overview` — `'dotenv-editor::overview-adminlte'` (commented alt `'dotenv-editor::overview'`) — the overview view rendered.
- `route` — array `{ namespace, prefix: 'admin/env', as: 'admin.env.', middleware: ['web','admin'] }` — route group config.

Note: the shipped config defaults `template`/`overview` to the author's personal **AdminLTE** views (`adminlte::page` requires the third-party `jeroennoten/laravel-adminlte` package, which is NOT a dependency). The Bootstrap-3 self-contained defaults are present but commented out — a fresh install renders broken unless the user un-comments them or installs AdminLTE.

## UI features (group C only)
- **inline edit**: via per-row edit modal (Vue) — POSTs to `/update`. Not truly inline; modal-based.
- **grouping**: none.
- **search**: none.
- **masking**: none — values shown in plaintext.
- **backup-restore UI**: yes — full "Backups" tab: create backup, list backups (numbered + formatted date), show backup content (modal, read-only table), restore, download, delete.
- **upload**: yes — "Upload" tab; uploads a file and overwrites the live `.env` (`$file->move(base_path(), '.env')`).
- **download/export**: yes — download current `.env` or any backup file.
- **auth**: only the `admin` middleware named in config (not shipped — the host app must define it). No built-in auth/guard.
- **IP gating**: none.
- **diff-preview**: none (backup "show details" is a plain key/value table, no diff).
- **add new key**: yes — "Add new" tab.
- **delete with confirm**: yes — delete confirmation modal.

## UI stack (group C only)
- **Blade** templates: `master.blade.php` (Bootstrap 3.3.6 via CDN) and `overview.blade.php`; plus an AdminLTE variant `overview-adminlte.blade.php` (the shipped default).
- **Vue 1.0.26** (loaded from cdnjs) + **jQuery 2.2.4** (googleapis CDN) for AJAX. Uses Vue-1-only syntax (`.$remove`, `v-show`, `@{{ }}`) — will not run on Vue 2+.
- No Livewire / Filament / Nova.

## Dependencies
- `php >= 5.5.9` (only declared require).
- Implicit/peer (not declared in composer.json): `illuminate/support` (`Str`), `vlucas/phpdotenv` (`Dotenv\Exception\InvalidPathException`, and `env()`), and `jeroennoten/laravel-adminlte` for the shipped default views.
- Front-end CDNs: Vue 1.0.26, jQuery 2.2.4, Bootstrap 3.3.6.

## Unique selling points
- One of the earliest (2016-era) Laravel `.env` editors with a **graphical Vue-based GUI** out of the box (overview/add/backups/upload tabs).
- Built-in **timestamped backup system** with restore/download/delete and an auto-backup-on-write option.
- Both **programmatic** (facade/class API) and **web UI** access to the same editor.
- Reads values through Laravel's `env()` helper so `getValue` reflects the running config.

## Tests
N — no tests directory, no PHPUnit/Pest config, no `require-dev` in composer.json.

## Notes / corrections
- **Stale / abandoned**: targets PHP 5.5.9+, Vue 1.x, Bootstrap 3, jQuery — pre-Laravel-package-discovery era patterns. Not compatible with modern Laravel front-end stacks without rework.
- **Security caveats**: `upload` blindly moves any uploaded file to `base_path('.env')` with no validation; `download` exposes the live `.env`; routes rely on an undefined `admin` middleware. No CSRF on GET-based mutating routes (createbackup/deletebackup/restore are `GET`).
- `getValue()` returns via `env($key)`, which is cached config — may not reflect just-written changes within the same request.
- `changeEnv()` only updates keys that already exist; new keys silently ignored (use `addData()`).
- `deleteData()` requires a **numeric-indexed array of key names** (e.g. `['KEY1','KEY2']`), not an assoc array — easy to misuse.
- `save()` quoting logic for whitespace values is buggy (the `changeEnv` path does not sanitize; only `addData` calls `santize()`).
- Method name is misspelled `santize` (not `sanitize`) in the public API — relevant if called directly.
- The facade accessor string and DI binding key is `'brotzka-dotenveditor'`.
- Turkish translations live under `src/Lang/tr/` (outside the `resources/lang/` tree loaded by the provider) — likely not actually loaded.
