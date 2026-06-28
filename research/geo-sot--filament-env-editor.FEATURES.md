# FEATURES — geo-sot/filament-env-editor
Source: https://github.com/GeoSot/filament-env-editor · v2.0.1 · MIT · group D adapter

## What it is / entry
Filament Plugin. A `.env` file viewer/editor page for Filament v5 panels. Registered
by adding `FilamentEnvEditorPlugin::make()` to a panel's `->plugin(...)` chain. All
actual env read/write/backup logic is delegated to the upstream `geo-sot/laravel-env-editor`
(`GeoSot\EnvEditor\Facades\EnvEditor`); this package is purely the Filament UI layer.
No standalone library API, no Nova tool.

## Public API or plugin surface (verified signatures)
Class `GeoSot\FilamentEnvEditor\FilamentEnvEditorPlugin implements Filament\Contracts\Plugin`
(uses `EvaluatesClosures`):
- `getId(): string` → returns `'filament-env-editor'`
- `static make(): static` → `app(static::class)`; `static get(): static` → alias of `make()`
- `register(Panel $panel): void` → registers the single page (`$this->viewPage`, default `ViewEnv::class`)
- `boot(Panel $panel): void` → empty
- `authorize(bool|\Closure $callback = true): static` → sets `$authorizeUsing`
- `isAuthorized(): bool` → `true === $this->evaluate($this->authorizeUsing)`
- `viewPage(string $page): static` / property `$viewPage` (swap in a custom page class)
- `navigationGroup(string|\Closure|null): static` / `getNavigationGroup(): string`
- `navigationSort(int|\Closure): static` / `getNavigationSort(): int`
- `navigationIcon(string|\Closure): static` / `getNavigationIcon(): string` (default `heroicon-o-document-text`)
- `navigationLabel(string|\Closure|null): static` / `getNavigationLabel(): string`
- `slug(string|\Closure): static` / `getSlug(): string` (default `'env-editor'`)
- `hideKeys(string ...$keys): static` / `getHiddenKeys(): array`

Page `GeoSot\FilamentEnvEditor\Pages\ViewEnv extends Filament\Pages\Page`:
- `static canAccess(): bool` → `FilamentEnvEditorPlugin::get()->isAuthorized()`
- `form(Schema $schema): Schema` → two Tabs: "Current env" + "Backups"
- header/nav statics delegate to the plugin singleton; `triggerRefresh()` dispatches `$refresh`.

Actions (Filament `Action` subclasses under `src/Pages/Actions/`):
- Env entries: `CreateAction` (add key), `EditAction` (edit value), `DeleteAction`, `OptimizeClearAction`.
- Backups (`Actions/Backups/`): `MakeBackupAction`, `DeleteBackupAction`, `RestoreBackupAction`,
  `UploadBackupAction`, `DownloadEnvFileAction`, `ShowBackupContentAction`.

## Artisan commands (if any)
None shipped by this package. (Spatie `InstallCommand` is wired via
`->hasInstallCommand(...)` → `filament-env-editor:install`, an interactive
publish/star scaffold only — no env logic.)

## Config keys
None. No `config/*.php` file is published — `ServiceProvider::configurePackage()`
only calls `->name()`, `->hasInstallCommand()`, `->hasTranslations()`, `->hasViews()`.
All configuration is fluent on the plugin instance (navigationGroup/Label/Icon/Sort,
slug, hideKeys, authorize, viewPage). Upstream `geo-sot/laravel-env-editor` ships its
own `env-editor.php` config (backup paths etc.), but that is out of this repo.

## Patterns to mine
- **Sensitive-key hiding (verified):** This is HIDE-ONLY, not masking. Keys are listed
  via the fluent `->hideKeys('APP_KEY', 'BCRYPT_ROUNDS')` (variadic, stored in
  `protected array $hideKeys`), read back through `getHiddenKeys()`. In `ViewEnv::getFirstTab()`
  each entry collection is `->reject(fn (EntryObj $obj) => $this->shouldHideEnvVariable($obj->key))`,
  where `shouldHideEnvVariable($key)` = `in_array($key, FilamentEnvEditorPlugin::get()->getHiddenKeys())`.
  Hidden keys are entirely omitted from the rendered list (no masked/`****` placeholder,
  value never sent to the page). NOTE: there is NO config key for this — it is plugin-instance
  state only, exact-match on the full key name (no wildcard/prefix matching).
- **Auth callbacks (verified):** Single gate. `->authorize(bool|\Closure $callback = true)`
  stores the closure in `$authorizeUsing` (default `true`). `isAuthorized()` returns
  `true === $this->evaluate($this->authorizeUsing)` (Filament `EvaluatesClosures`).
  `ViewEnv::canAccess()` returns `isAuthorized()` — this is the only authorization point;
  it gates page access AND navigation visibility. No per-action (edit/delete/create) auth,
  no per-key edit gating.
- **Production guard:** NONE. No `app()->environment()` check, no `production` confirmation,
  no read-only mode. Edits/deletes/backups work in any environment once the page is accessible.
- **Validation chain:** Minimal Filament form validation only. `EditAction`: `key` is
  `->required()->readOnly()` (key immutable on edit), `value` free TextInput; writes via
  `EnvEditor::editKey($data['key'], $data['value'])`. `CreateAction`: `key` `->required()`,
  `value` optional, optional `index` Select; writes via `EnvEditor::addKey(...)` wrapped in
  try/catch on `GeoSot\EnvEditor\Exceptions\EnvException` (shows failure notification + `halt()`).
  No format/uniqueness/quote-escaping validation in this layer — delegated to the upstream library.

## Dependencies (esp. Filament/Nova version)
- `php: ^8.2`
- `filament/filament: ~5.0` (Filament v5 ONLY; per README: 3.x→pkg 0.x, 4.x→pkg 1.x, 5.x→pkg 2.x)
- `geo-sot/laravel-env-editor: ^3.0` (does the real env read/write/backup; `EnvEditor` facade)
- `illuminate/contracts: >=12.0` (Laravel 12+)
- `spatie/laravel-package-tools: ^1.15.0`
- dev: php-cs-fixer ^3, larastan ^3, orchestra/testbench >=9, phpstan-deprecation-rules ^2, phpstan-phpunit ^2

## Tests
N — no `tests/` directory. Only static analysis (phpstan/larastan) + php-cs-fixer wired
in `composer.json` scripts and `.github/workflows/` (phpstan.yml, php-cs-fixer.yml). No CI test job.

## Notes / corrections
- Single-page plugin (one `ViewEnv` page); no Filament Resource.
- The "Backups" tab is a notable feature (make/restore/upload/download/show/delete backups),
  all delegated to `EnvEditor::getAllBackUps()` / upstream library DTOs (`BackupObj`, `EntryObj`).
- Localized: en, it, ja translation files under `resources/lang/`.
- Version sourced from `git describe --tags` = `2.0.1` (latest local tag).
- Reusable pattern worth mining for a headless adapter: the clean `hideKeys`/`authorize`
  fluent surface, but BE AWARE it lacks production guards and value masking — hidden keys
  are simply dropped, full values are rendered as `<code>KEY=value</code>` for all non-hidden keys.
