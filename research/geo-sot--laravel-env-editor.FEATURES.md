# FEATURES — geo-sot/laravel-env-editor
Source: GeoSot/Laravel-EnvEditor · require `php >=8.1`, `laravel/framework >=11.0` (no tagged version in source; `minimum-stability: dev`) · MIT · group C web-UI

## Invocation / entry
- **Facade**: `GeoSot\EnvEditor\Facades\EnvEditor` (alias `EnvEditor`), backed by singleton-bound `GeoSot\EnvEditor\EnvEditor` (also aliased `'env-editor'`). Auto-discovered via `extra.laravel.providers`.
- **Web routes**: registered from `routes/routes.php` only when `config('env-editor.route.enable')` is `true` (default **false**). Prefix `env-editor`, middleware `['web']`. Handled by `GeoSot\EnvEditor\Controllers\EnvController`.
- **No Artisan commands**, no Nova/Filament tool.

Routes (all under prefix `env-editor`):
- `GET /` → `index` (`env-editor.index`) — view, or JSON when `wantsJson()`
- `POST key` → `addKey` (`env-editor.key`)
- `PATCH key` → `editKey`
- `DELETE key` → `deleteKey`
- `DELETE clear-cache` → `clearConfigCache` (`env-editor.clearConfigCache`) — runs `Artisan::call('config:clear')`
- `GET files/` → `getBackupFiles` (`env-editor.getBackups`)
- `POST files/create-backup` → `createBackup` (`env-editor.createBackup`)
- `POST files/restore-backup/{filename?}` → `restoreBackup` (`env-editor.restoreBackup`)
- `DELETE files/destroy-backup/{filename?}` → `destroyBackup` (`env-editor.destroyBackup`)
- `GET files/download/{filename?}` → `download` (`env-editor.download`)
- `POST files/upload` → `upload` (`env-editor.upload`)

## Artisan commands (verified)
- none — package ships no `Command` classes.

## Public API (if any)
Main class `GeoSot\EnvEditor\EnvEditor` (signatures verbatim from `src/EnvEditor.php`):
- `getEnvFileContent(string $fileName = ''): Collection` — parse `.env` (or a named backup) into `Collection<int, EntryObj>`
- `keyExists(string $key): bool`
- `getKey(string $key, mixed $default = null): float|bool|int|string|null`
- `addKey(string $key, mixed $value, array $options = []): bool` — add a key; `$options` is `array{index?: int|string|null, group?: int|string|null}`
- `editKey(string $keyToChange, mixed $newValue): bool`
- `deleteKey(string $key): bool`
- `getAllBackUps(): Collection` — `Collection<int, BackupObj>`
- `upload(UploadedFile $uploadedFile, bool $replaceCurrentEnv): File`
- `backUpCurrent(): bool`
- `getFilePath(string $fileName = ''): string`
- `deleteBackup(string $fileName): bool`
- `restoreBackUp(string $fileName): bool`
- `config(string $key, mixed $default = null): mixed`
- `getKeysManager(): EnvKeysManager`
- `getFilesManager(): EnvFilesManager`
- `getFileContentManager(): EnvFileContentManager`

Underlying `EnvKeysManager::add(string $key, mixed $value, array $options = []): bool` (signature verbatim, `@param array{index?: int|string|null, group?: int|string|null} $options`).

## Config keys
- `paths.backupDirectory` — `storage_path('env-editor')` — where backups are stored
- `route.enable` — `false` — master switch for the web UI/routes
- `route.prefix` — `'env-editor'` — URL prefix for the route group
- `route.name` — `'env-editor'` — base route name
- `route.middleware` — `['web']` — middleware on the route group (this is the ONLY access control; no auth/IP gating)
- `timeFormat` — `'d/m/Y H:i:s'` — date format in views / parsed backups
- `layout` — `'env-editor::layout'` — Blade layout that `index.blade.php` extends

## UI features (group C only)
- **inline edit**: NO — edits happen via a Bootstrap modal (`_itemModal.blade.php`), not inline in the table.
- **grouping**: YES (data-model level) — `.env` is parsed into groups separated by blank-line separators; "Add after" places a key in the same group. UI shows blank separator rows but has no group headers/collapsible group sections.
- **search/filter**: NO — no search box; full table rendered with `v-for`.
- **masking / secret hiding**: NO — values rendered in plaintext in the table, modal, and backup preview.
- **backup-restore UI**: YES — Backups tab lists backups with view-content (collapse/accordion), download, restore, delete; plus "Backup current .env" and "Download current .env" buttons.
- **upload**: YES — Upload tab; upload as backup OR upload-and-replace-current `.env`.
- **auth**: NO dedicated auth — relies solely on the configured route middleware (default `['web']`).
- **IP gating**: NO.
- **diff-preview**: NO — backup view shows raw key/value entries, no diff against current.
- **export / download**: YES — download current `.env` and download any backup file.
- **CSRF**: YES — `X-CSRF-Token` header sent on all AJAX calls.
- **config cache clear**: YES — "Delete config cache" button calls `config:clear`.
- **add / edit / delete keys**: YES (via modal); add-new and "add after key" supported.

## UI stack (group C only)
- **Blade** server-rendered shell + **Vue 2** (Vue **2.5.17** via cdnjs; `index.blade.php` references Vue 2.5.17, layout loads Vue 2.5.17). Components are inline `<template id>` + plain JS objects, wired through a global `envEventBus = new Vue()`.
- **Bootstrap 4.6.1** (CSS + JS bundle) and **jQuery 3.5.1 slim** (modals/alerts/tabs/collapse), Font Awesome 5.10.0 — all via CDN.
- No Livewire, Filament, or Nova. No build step / npm assets — everything is CDN + inline scripts.

## Dependencies
- runtime: `php >=8.1`, `laravel/framework >=11.0`
- dev: `friendsofphp/php-cs-fixer ^3`, `larastan/larastan ^3`, `orchestra/testbench >=9`, `rector/rector ^2`
- front-end (CDN, not composer): Vue 2.5.17, Bootstrap 4.6.1, jQuery 3.5.1 slim, Font Awesome 5.10.0

## Unique selling points
- Both a programmatic Facade API and an optional GUI for the same operations.
- Structure-preserving editing: parses `.env` into `EntryObj` lines (incl. blank-line group separators) and rewrites preserving grouping/order via float-index insertion (`index + 0.1`).
- Group-aware insertion: append a key to the end of a named group, or after a specific line, via the `$options` array.
- Full backup lifecycle: create/list/view/restore/download/delete backups, upload an external `.env` as backup or to replace current.
- Built-in "clear config cache" action so edited values take effect.
- i18n-ready (publishable translations) and publishable config/views.

## Tests
Y — PHPUnit (`phpunit.xml.dist`, `orchestra/testbench`). Paths:
- `tests/Feature/ConfigurationTest.php`, `tests/Feature/UiTest.php`
- `tests/Unit/Dto/{BackupObjTest,EntryObjTest}.php`
- `tests/Unit/Helpers/{EnvFileContentManagerTest,EnvKeysManagerTest,FilesManagerTest}.php`
- `tests/TestCase.php`, fixture `tests/fixtures/.env.example`

## Notes / corrections
- **Group-aware insertion signature (verified verbatim)**: it is an **options array**, NOT named params and NOT `['group'=>...]` as a 3rd-of-three positional. Exact:
  - Public: `EnvEditor::addKey(string $key, mixed $value, array $options = []): bool`
  - Impl:  `EnvKeysManager::add(string $key, mixed $value, array $options = []): bool` with `@param array{index?: int|string|null, group?: int|string|null} $options`.
  - So callers write `addKey('FOO','bar', ['group' => 'MAIL'])` or `addKey('FOO','bar', ['index' => 12])`. README documents `['index'=>...]` and `['group'=>'MAIL/APP etc']`. There is no dedicated `setKey`; `editKey($key,$value)` (positional, no options) is the update path.
- Group resolution detail: when `group` is omitted, the new key goes to a fresh group at the end (a separator row is pushed if the last entry isn't already one). When `group` is given, it finds the last separator whose key-prefix (`explode('_',key,2)[0]`) uppercased matches the group and inserts at that separator's `index + 0.1`. `index` in `$options` overrides the computed position.
- The web controller passes `$request->except(['key','value'])` straight into `$options`, so any extra POST fields (e.g. `group`, `index`) flow into the options array from the UI (the modal sends a hidden `group` field).
- **No version tag in the source tree** — `composer.json` has no `version` field and `minimum-stability: dev`; treat the actual release version as unverified from source.
- **Security caveat**: the only gate on the GUI is `route.middleware` (default `['web']`) and `route.enable=false`. No built-in auth, no IP allow-list, no value masking — exposed `.env` values (incl. secrets) are shown in plaintext. Hardening is left entirely to the integrator via middleware.
- Upload validation: `mimetypes:application/octet-stream,text/plain|mimes:txt,text,` — note the trailing comma / empty `mimes` entry (permits extension-less files).
