# FEATURES — joaopaulolndev/filament-edit-env

Source: https://github.com/joaopaulolndev/filament-edit-env · v3.0.0 (2026-01-20) · MIT · group D adapter

## What it is / entry
Filament Plugin. A FilamentPHP panel plugin that injects a single icon-button into
the panel header (via a render hook) which opens a modal containing an Ace code
editor pre-loaded with the raw contents of the project's `.env` file; saving writes
the text straight back to `base_path('.env')`. No Nova tool, no library API, no
Artisan commands. Registered through `extra.laravel.providers` →
`FilamentEditEnvServiceProvider` (Spatie `PackageServiceProvider`), and added to a
panel via `FilamentEditEnvPlugin::make()` in the `->plugins([...])` array.

## Public API or plugin surface (verified signatures)
- `FilamentEditEnvPlugin implements Filament\Contracts\Plugin` (uses `EvaluatesClosures`).
  - `getId(): string` → `'filament-edit-env'`
  - `register(Panel $panel): void` → empty (no-op)
  - `boot(Panel $panel): void` → registers Livewire component `change-env-file`
    (`ChangeEnvFileComponent`) and a `FilamentView::registerRenderHook` on
    `PanelsRenderHook::GLOBAL_SEARCH_BEFORE` that renders `@livewire('change-env-file')`
    **only if** `$this->evaluate($this->showButton)` is truthy (else returns `''`).
  - `static make(): static` → resolves from container and seeds a **default**
    `showButton` closure (the production guard — see below).
  - `static get(): static` → `filament(app(static::class)->getId())` accessor used by
    the Livewire component to read the icon.
  - `showButton(bool|Closure $showButton = true): static` — config/fluent setter.
  - `setIcon(string|Closure $setIcon = 'heroicon-o-command-line'): static` — config setter.
  - `getIcon(): string` → evaluated `$setIcon` or fallback `'heroicon-o-command-line'`.
  - Public props: `bool|Closure|null $showButton`, `string|Closure|null $setIcon`.
- Pages/Resources registered: **none.** The UI is a Livewire component
  (`Joaopaulolndev\FilamentEditEnv\Livewire\ChangeEnvFileComponent`) implementing
  `HasActions`, `HasForms`; it exposes `editAction(): Filament\Actions\Action` (an
  icon-button action opening a modal with one `AceEditorInput::make('envFile')`,
  mode `php`, default `file_get_contents(base_path('.env'))`, `->required()`; the
  action does `file_put_contents(base_path('.env'), $data['envFile'])` then a
  `Notification`).

## Artisan commands (if any)
- None. (Spatie InstallCommand only adds the implicit `filament-edit-env:install`
  scaffolding command via `->hasInstallCommand(...)`, which just calls
  `askToStarRepoOnGitHub`; no domain commands.)

## Config keys
- None. There is **no published config file** (no `config/` dir, no `hasConfigFile()`).
  All configuration is fluent on the plugin: `->showButton(...)` and `->setIcon(...)`.

## Patterns to mine
- **Production guard (exact mechanism):** lives in `FilamentEditEnvPlugin::make()`.
  `make()` seeds a default closure:
  `$plugin->showButton(fn () => match (app()->environment()) { 'production', 'prod' => false, default => true });`
  The render hook in `boot()` evaluates `$this->showButton`; when false it returns an
  empty string, so the edit button is **never rendered** in `production`/`prod`
  environments. The guard is purely a render-time visibility check keyed on
  `app()->environment()` — **not** a policy, middleware, `canAccess`, or save-time
  assertion. CAVEAT: it is a default that is fully **overridable** — passing your own
  `->showButton(fn () => auth()->user()->id === 1)` (as the README example does)
  **replaces** the env match and removes the production block entirely. There is no
  second guard at save time, so an overridden `showButton` that returns true in
  production would allow editing `.env` in production.
- **Sensitive-key hiding:** none. The whole `.env` is loaded verbatim into the editor
  textarea, including secrets; no masking/redaction of `APP_KEY`, DB passwords, etc.
- **canAccess / canEdit:** none. Visibility == editability; the single `showButton`
  closure gates both. No per-field or per-key authorization.
- **Validation chain:** minimal — only `AceEditorInput::make('envFile')->required()`.
  No env syntax validation, no diffing, no backup; save is a raw `file_put_contents`
  overwrite of `.env`.

## Dependencies (esp. Filament/Nova version)
- `php: ^8.2`
- `filament/filament: ^5.0`  ← **Filament major 5** (plan's assumption of Filament 5 is CORRECT)
- `jeffersongoncalves/filament-ace-editor-field: ^2.0` (the Ace editor form field)
- `spatie/laravel-package-tools: ^1.15.0`
- `minimum-stability: dev`, `prefer-stable: true`
- Compatibility matrix (README): 1.x→Filament 3.x, 2.x→Filament 4.x, 3.x→Filament 5.x.

## Tests
Y — `tests/` (Pest): `tests/ExampleTest.php`, `tests/ArchTest.php`, `tests/Pest.php`,
`tests/TestCase.php`. These are skeleton/arch tests only; no behavioral coverage of
the env edit/save or the production guard.

## Notes / corrections
- Version resolved from CHANGELOG.md: **v3.0.0 (2026-01-20)**; no local git tags present.
- The render hook anchor is `PanelsRenderHook::GLOBAL_SEARCH_BEFORE`, so the button
  appears next to the panel's global search (topbar), not as a nav/resource item.
- i18n: en/es/pt translation files under `resources/lang/*/default.php`
  (keys: `heading`, `hint`, `save`).
- Single point of trust: production safety relies entirely on the default `showButton`
  closure NOT being overridden — worth flagging for any adapter that re-exposes this.
