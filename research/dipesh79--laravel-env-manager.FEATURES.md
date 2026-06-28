# FEATURES — dipesh79/laravel-env-manager
Source: github.com/Dipesh79/laravel-env-manager · no tagged version (`minimum-stability: dev`, untagged) · MIT · group C web-UI

## Invocation / entry
Three entry surfaces, auto-wired by `LaravelEnvManagerServiceProvider`:
- **Artisan commands** — 5 commands registered in `register()`.
- **Web routes** — `boot()` calls `loadRoutesFrom(src/routes/web.php)`; routes are
  hard-registered under middleware `['web', 'auth']` (no route prefix/config toggle).
- **Blade view** — `loadViewsFrom(src/views, 'envManager')`, view `envManager::index`.
No Facade, no service/manager class, no Nova tool. There is **no programmatic public
API** (see Notes).

## Artisan commands (verified)
- `env:set {key} {value}` — set/update a var. Reads `.env`, does
  `str_replace("$key=".env($key), "$key=$value", contents)`, writes back. Errors if
  `.env` missing. (Fragile: relies on cached `env($key)` matching the literal file
  line; fails to add a brand-new key and mismatches quoted/changed values.)
- `env:remove {key}` — remove a var via
  `preg_replace('/^'.preg_quote($key).'=.*$/m', '', contents)`. Leaves a blank line.
  Errors if `.env` missing.
- `env:list` — prints raw `file_get_contents('.env')` via `$this->info()`. Errors if
  `.env` missing. (No masking — dumps secrets verbatim.)
- `env:backup` — copies `.env` to `storage/app/env_backups/.env_{Y-m-d_H-i-s}`,
  creating the dir (0755) if absent. Prints backup filename.
- `env:restore {timestamp}` — copies
  `storage/app/env_backups/.env_{timestamp}` back to `.env`. Errors if backup not
  found. (Timestamp must be given exactly, e.g. `2026-06-28_14-30-00`.)

## Public API (if any)
None. No Facade and no injectable manager class with public methods. The only public
methods are HTTP controller actions (not a callable API):
- `EnvManagerController::index(): View` — gate-checks `auth()->user()->hasAccessToPage()`
  (403 if false), reads/parses `.env`, renders `envManager::index`.
- `EnvManagerController::update(Request $request): RedirectResponse` — merges
  `variables[]` + `newVariables[]` (the latter `key=value` strings), rebuilds and
  `File::put`s the whole `.env`, redirects to route `laravel-env-manager` with success.
- `EnvManagerController::backup(): RedirectResponse` — same backup logic as the command.
- Private helpers: `parseEnv(string): array`, `buildEnvContent(array): string`.
- Contract `Dipesh79\LaravelEnvManager\Contracts\LaravelEnvEditorInterface` with
  `hasAccessToPage(): bool` — the consuming app's `User` model is expected to implement
  this; the controller calls it for authorization.

## Config keys
- `returnUrl` — default `"/"` — URL for the view's "Return Back" nav link. (Published
  to `config/envManager.php`; this is the **only** config key. Route paths, middleware,
  and the `auth` guard are hardcoded, not configurable.)

## UI features (group C only)
- inline edit — **yes** (text input per existing var, bulk "Save Changes").
- add variable — **yes** (JS `prompt()` for name+value, appended as `newVariables[]`).
- remove variable — **yes** (client-side DOM removal; persisted on save since omitted
  keys aren't rewritten).
- backup — **yes** (POST button → `backup()`); **restore via UI — no** (CLI only).
- auth gating — **yes** (`auth` middleware + `hasAccessToPage()` contract → 403).
- grouping — none. search/filter — none. masking — none (values shown in plain text).
- upload / import — none. export / download — none. diff-preview — none.
- IP gating — none (only the auth-user contract check).

## UI stack (group C only)
Plain **Blade** (single `index.blade.php`), styled with CDN **Bootstrap 5.2.3** +
Font Awesome 5.15.1, vanilla JS (no build step). No Vue / Livewire / Filament / Nova.

## Dependencies
- `composer.json` `require: {}` — **zero runtime deps** (relies on the host Laravel
  framework: `Illuminate\Console`, `Illuminate\Support\Facades\File`, routing, views).
- No dev deps, no test deps declared.

## Unique selling points
- Dependency-free, drop-in: install + auto-discovered provider, no config required.
- Covers both a CLI workflow and a minimal browser editor for `.env`.
- Timestamped backups + CLI restore.
- Pluggable authorization via the `LaravelEnvEditorInterface` contract on the User model.

## Tests
N — no tests, no `tests/` dir, no PHPUnit/Pest config or dev-dependency in the repo.

## Notes / corrections
- **Group C classification is CORRECT.** This *is* a real web-UI package: it ships a
  controller (`src/Controller/EnvManagerController.php`), routes
  (`src/routes/web.php`: `GET /env`, `POST /env/update`, `POST /env/backup`), and a
  Blade view (`src/views/index.blade.php`). It is **not** a programmatic facade-only
  package — and equally it is **not** facade-API based at all (there is no Facade or
  public manager API to call from code). It is best described as **CLI + web-UI**, with
  no programmatic API surface.
- Security caveats worth flagging: `env:list` and the web editor expose all secrets in
  plaintext (no masking); routes are registered globally at `/env` with no prefix/config
  guard beyond `auth` + the user-implemented `hasAccessToPage()`; `update()` rewrites the
  entire `.env` from form input, dropping comments, blank lines, and any var removed
  client-side, and does not quote values.
- `env:set` cannot add a new key (its `str_replace` needs an existing `key=env($key)`
  line) and depends on the runtime-cached `env()` value matching the file — brittle.
- License file is misspelled `LISCENSE` in the repo (composer declares MIT).
- No tagged release; `composer.json` sets `minimum-stability: dev`.
