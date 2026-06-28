# FEATURES — cranux/laravel-dotenv-editor
Source: github.com/cranux (fork of Brotzka/laravel-dotenv-editor) · no version tag in composer.json (README calls it "2.x") · MIT · group C web-UI

## Invocation / entry
- **Facade**: `DotenvEditor` (alias `Cranux\DotenvEditor\DotenvEditorFacade`), bound to container key `cranux-dotenveditor`.
- **Direct instantiation**: `new Cranux\DotenvEditor\DotenvEditor()`.
- **Web routes** (auto-loaded from `routes/web.php`, gated on `config('dotenveditor.activated')`): a route group with configurable `prefix` (default `admin/env`), `as` prefix `admin.env.`, middleware `['web','admin']`, namespace `Cranux\DotenvEditor\Http\Controllers`. Routes:
  - `GET  /`                          → `EnvController@overview`     (name `index`)
  - `POST /add`                       → `add`
  - `POST /update`                    → `update`
  - `GET  /createbackup`              → `createBackup`
  - `GET  /deletebackup/{timestamp}`  → `deleteBackup`
  - `GET  /restore/{backuptimestamp}` → `restore`
  - `POST /delete`                    → `delete`
  - `GET  /download/{filename?}`      → `download`
  - `POST /upload`                    → `upload`
  - `GET  /getdetails/{timestamp?}`   → `getDetails` (returns JSON)
- No Nova tool, no Artisan commands.

## Artisan commands (verified)
- none.

## Public API (if any)
Class `Cranux\DotenvEditor\DotenvEditor` (signatures verbatim):
- `getBackupPath()` — returns the current (trailing-slash-normalized) backup path.
- `setBackupPath($path)` — sets a new backup path, `mkdir($path, 0777, true)` if missing; returns bool.
- `keyExists($key)` — checks key presence in parsed `.env`; returns bool.
- `getValue($key)` — returns `env($key)` if key exists, else throws `DotEnvException`. (Note: reads Laravel's cached `env()`, not the file value.)
- `setAutoBackup($onOff)` — bool toggle for auto-backup; throws `DotEnvException` if not a bool. Returns void.
- `autoBackupEnabled()` — returns the in-memory auto-backup flag (bool).
- `createBackup()` — copies `.env` to `{backupPath}{time()}_env`; returns bool.
- `restoreBackup($timestamp = null)` — restores given timestamp's backup (or latest if null) over `.env`; returns bool from `copy()`.
- `getBackupVersions()` — array of all backups, each `['formatted' => 'Y-m-d H:i:s', 'unformatted' => <ts>]`; throws `DotEnvException` if none.
- `getBackupFile($timestamp)` — returns full path to the backup file; throws if missing.
- `deleteBackup($timestamp)` — `unlink()`s the backup file; throws if missing. Returns void.
- `getContent($timestamp = null)` — parses current `.env` (or a backup) into an associative array (key⇒value).
- `getAsJson($timestamp = null)` — JSON string: array of `{key, value}` objects.
- `changeEnv($data = array())` — updates **only existing** keys from assoc array; auto-backups first if enabled; throws if empty. Returns bool.
- `addData($data = array())` — appends new key/value pairs (values run through `santize()`); auto-backups if enabled; throws if empty. Returns bool.
- `deleteData($data = array())` — deletes entries; **keys of `$data` must be numeric** (values are the env keys to remove) else throws; returns bool.
- `santize($value = '')` — quoting/sanitizing helper (public).
- `isStartOrEndWith($value, $string = '')` — bool helper (public).
- `setStartAndEndWith($value, $string = '')` — wraps value in `"` (public).
- Protected/internal: `getLatestBackup()`, `getFile($timestamp)`, `envToArray($file)`, `save($array)`; private: `envToJson($file = [])`.

## Config keys
File `config/dotenveditor.php`:
- `pathToEnv` — `base_path('.env')` — path to the .env being edited.
- `backupPath` — `resource_path('backups/dotenv-editor/')` — where backups are written.
- `filePermissions` — `env('FILE_PERMISSIONS', 0755)` — mode for created backup dir.
- `activated` — `true` — master switch for the GUI/routes.
- `template` — `'adminlte::page'` (the Brotzka default `'dotenv-editor::master'` is commented out) — layout the overview extends.
- `overview` — `'dotenv-editor::overview-adminlte'` (Brotzka default `'dotenv-editor::overview'` commented out) — overview view rendered.
- `route` — array: `namespace` `Cranux\DotenvEditor\Http\Controllers`, `prefix` `admin/env`, `as` `admin.env.`, `middleware` `['web','admin']`.

## UI features (group C only)
- **inline edit** — yes, via Bootstrap modal "edit" (per-row pencil icon → `editModal` → POST `/update`).
- **grouping** — no.
- **search/filter** — no search box; but on load values whose key contains `key`/`secret`/`password` are auto-masked (adminlte view only).
- **masking** — yes in `overview-adminlte` only: a Vue `hide` filter renders `'*'.repeat(value.length)`, toggled by an eye icon (`fa-eye`); auto-enabled for key/secret/password keys. The plain `overview` view has **no masking**.
- **backup-restore UI** — yes: create backup, list backups (table), show backup details modal, restore, delete, download per backup.
- **upload** — yes: a file-upload form that replaces the live `.env` (`upload()` moves the uploaded file to `base_path()` as `.env`).
- **download/export** — yes: download current `.env` or any backup (`download()` → `BinaryFileResponse`).
- **auth / IP gating** — only the `admin` middleware in the default route config (no built-in auth/IP logic; relies on the host app's `admin` middleware existing).
- **diff-preview** — no (only a read-only table view of a backup's contents).
- Tabs: Overview, Add new, Backups, Upload (Vue-driven tab switch).

## UI stack (group C only)
- **Blade** views: `master.blade.php` (Bootstrap 3.3.6 standalone layout), `overview.blade.php` (Brotzka-style), `overview-adminlte.blade.php` (custom, extends `adminlte::page` and uses `@push('css')`/`@push('js')`).
- **Vue.js 1.0.20** loaded from CDN (`cdn.staticfile.org/vue/1.0.20/vue.js`) — legacy Vue 1.x (uses `v-show`, `entries.$remove`, `filters`).
- **jQuery 2.2.4** + **Bootstrap 3.3.6 JS/CSS** from CDN (used for `$.ajax`, modals, tooltips/popovers).
- **Glyphicons** Halflings font shipped under `asset/` (`css/glyphicons.css` + `.eot/.svg/.ttf/.woff/.woff2`), published to `public/vendor/dotenv-editor`. The adminlte view also uses Font Awesome icons (`fa-*`) provided by the host AdminLTE theme.
- No build step / no compiled JS — all inline `<script>` in the Blade files.

## Dependencies
- `php >= 7.2.0` (only declared require).
- Implicitly relies on Laravel (`Illuminate\Support\Str`, `ServiceProvider`, `Facade`, `Controller`) and `vlucas/phpdotenv` (`Dotenv\Exception\InvalidPathException`) provided by the host app — not declared.
- adminlte template requires `jeroennoten/laravel-adminlte` (or similar `adminlte::page`) in the host app, since it's the default `template`.
- CDN-loaded Vue/jQuery/Bootstrap at runtime (no npm).

## Unique selling points
- Drop-in graphical `.env` editor with backup/restore/upload/download out of the box.
- Vue-based SPA-ish overview with tabbed UI.
- **Fork additions over Brotzka**: AdminLTE-integrated view (`overview-adminlte`) with **secret masking** (auto-hides key/secret/password values, eye-toggle), delete/restore `confirm()` guards, and Chinese-author maintenance for Laravel 6.x compatibility.

## Tests
N — no test directory, no PHPUnit config, no `require-dev` in composer.json.

## Notes / corrections
- **This is a near-verbatim fork of `brotzka/laravel-dotenv-editor`** (original author "Fabian"/Brotzka — PhpStorm headers still say `User: Fabian`). README opens in Chinese stating it was modified from Brotzka because of Laravel 6.x incompatibility. Namespace renamed `Brotzka\DotenvEditor` → `Cranux\DotenvEditor`; container binding `brotzka-dotenveditor` → `cranux-dotenveditor`; translations namespace kept as `dotenv-editor::`.
- **Differences from brotzka**: (1) adds `config/dotenveditor.php` `template`/`overview` defaults pointing at a **custom AdminLTE view** (`overview-adminlte.blade.php`) — the upstream `dotenv-editor::master`/`overview` lines are commented out; (2) adds the `overview-adminlte` view with secret-masking Vue filter and `confirm()` dialogs; (3) adds extra `tr` translations under `src/Lang/tr/` (in addition to `resources/lang/{de,en,nl,pl,ru,vi,zh-CN}`); (4) raises PHP floor to `>=7.2.0`; (5) publishes assets to `public/vendor/dotenv-editor`.
- **Security caveats (inherited & real)**: default route middleware is `['web','admin']` but the package ships no `admin` middleware — if the host app lacks one, registration fails or routes are unprotected depending on setup. `upload()` blindly overwrites the project `.env` with no validation. `getValue()` returns the framework-cached `env()` value, which can diverge from the on-disk file. `save()`/`envToArray()` drop comments and blank lines and strip lines beginning with `# ` — round-tripping the file loses comments. `setBackupPath()` hardcodes `0777`.
- `src/Lang/tr/` lives under `src/` (not `resources/lang/`), so it is NOT covered by `loadTranslationsFrom(__DIR__.'/../resources/lang', ...)` — the Turkish strings are effectively unloaded/dead unless manually wired.
- The non-adminlte `overview.blade.php` references a `makeBackup()` Vue method and `popover`/`tooltip` init but loads jQuery/Bootstrap *after* the inline Vue script (load-order fragility).
