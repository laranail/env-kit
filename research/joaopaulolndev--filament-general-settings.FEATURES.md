# FEATURES — joaopaulolndev/filament-general-settings

Source: https://github.com/joaopaulolndev/filament-general-settings · branch `3.x` (HEAD; no git tags present in clone) · MIT · group D adapter

## What it is / entry
Filament **Plugin** (`Filament\Contracts\Plugin`). Adds a single full-page Filament
**Page** (`GeneralSettingsPage`) that edits a one-row `general_settings` table through a
tabbed schema: Application, Analytics, SEO Meta, Email (with "send test mail"), Social
Networks, plus optional user-defined Custom Tabs. It is a **general-settings manager** — it
does NOT include any `.env` file editor / env-editor tab (the "Email" tab edits SMTP/Mailgun/
Postmark/SES creds stored in the DB and pushes them into Laravel mail config at runtime, but
no `.env` is read or written). Also ships a Livewire `FilamentGeneralSettingsMiddleware` that,
per request, registers the saved `theme_color` as the Filament `primary` color and overrides
`app.name` with the saved `site_name`.

## Public API or plugin surface (verified signatures)
Plugin contract (`FilamentGeneralSettingsPlugin`):
- `static make(): static` — `app(static::class)`
- `static get(): static` — `filament('filament-general-settings')`
- `getId(): string` → `'filament-general-settings'`
- `register(Panel $panel): void` — registers `GeneralSettingsPage` page + `authMiddleware([FilamentGeneralSettingsMiddleware::class])`
- `boot(Panel $panel): void` — no-op

Fluent config methods (each setter returns `static`; getters used by the Page):
- `setSort(Closure|int = 100)` / `getSort(): int`
- `canAccess(Closure|bool = true)` / `getCanAccess(): bool`
- `setIcon(Closure|string = '')` / `getIcon(): ?string`
- `setNavigationGroup(Closure|string = '')` / `getNavigationGroup(): ?string`
- `setTitle(Closure|string = '')` / `getTitle(): ?string`
- `setNavigationLabel(Closure|string = '')` / `getNavigationLabel(): ?string`
Closures are resolved via `EvaluatesClosures` (`$this->evaluate(...)`).

Page (`Pages\GeneralSettingsPage extends Filament\Pages\Page`):
- View `filament-general-settings::filament.pages.general-settings-page`
- `mount()` — loads first model row into `$data`, normalizes seo/theme defaults, hydrates email
  config via `EmailDataHelper::getEmailConfigFromDatabase()`, wraps logo/favicon strings.
- `form(Schema $schema): Schema` — builds Filament 5 `Tabs`/`Tab` (`Filament\Schemas\Components\Tabs`)
  conditionally per `show_*_tab` config; `statePath('data')`.
- `update()` — `$this->form->getState()` → email re-pack → `clearVariables()` → `updateOrCreate([], $data)` → `Cache::forget('general_settings')` → success notify + redirect to Referer.
- `sendTestMail(MailSettingsService)` — loads form mail settings to config, sends `Mail\TestMail`.
- Static overrides `getNavigationGroup/Icon/Sort/canAccess/getTitle/getNavigationLabel` delegate to the plugin instance via `Filament::getCurrentOrDefaultPanel()?->getPlugin('filament-general-settings')`.

Service (`GeneralSettingsService`): `static getModel(): Model` (honors `config('...model')`),
`get(): ?Model` (cached read keyed `general_settings`, TTL = `expiration_cache_config_time`).

## Artisan commands (if any)
- `filament-general-settings` — `Commands\FilamentGeneralSettingsCommand`; stub/placeholder
  ("My command", prints "All done"). No real functionality.
- Spatie `InstallCommand` wiring: `filament-general-settings:install` (publishes config +
  migrations, asks to migrate, asks to star repo).
- Standard publish tags: `filament-general-settings-config`, `-migrations`, `-views`,
  `-translations`, `-assets`.

## Config keys
File `config/filament-general-settings.php`:
- `model` — `GeneralSetting::class` — Eloquent model backing the settings row.
- `show_application_tab` — `true` — toggle Application tab.
- `show_logo_and_favicon` — `false` — enable logo/favicon upload fields (needs extra migration).
- `show_analytics_tab` — `true` — toggle Analytics tab.
- `show_seo_tab` — `true` — toggle SEO Meta tab.
- `show_email_tab` — `true` — toggle Email/SMTP tab.
- `show_social_networks_tab` — `true` — toggle Social Networks tab.
- `expiration_cache_config_time` — `60` — settings cache TTL (seconds/minutes per cache driver).
- `show_custom_tabs` — (opt, default absent/false) — enable user-defined extra tabs.
- `custom_tabs` — (opt) — array of `label/icon/columns/fields[]`; field `type` drawn from
  `Enums\TypeFieldEnum` (Text/Select/Textarea/Datetime/Boolean/RichEditor).

## Patterns to mine
- **canAccess / authorization**: `Page::canAccess()` returns `$plugin->getCanAccess()`, which
  evaluates the `canAccess(Closure|bool)` set on the plugin (README example
  `->canAccess(fn() => auth()->user()->id === 1)`). Single gate; no separate view/edit split.
- **Sensitive-key handling**: `clearVariables()` in the Page strips transient/secret form keys
  (smtp_password, mailgun_secret, postmark_token, amazon_ses_secret, etc.) from `$data` before
  persisting — the secrets are repacked into the `email_settings` JSON column by
  `EmailDataHelper::setEmailConfigToDatabase()` rather than stored as individual columns. There is
  NO production guard and NO masking of secrets in the UI beyond normal password fields.
- **Validation chain**: per-field `rules` strings supplied in `custom_tabs` config (e.g.
  `'required|string|max:255'`); built-in tabs rely on the Filament Forms field definitions in
  `src/Forms/*FieldsForm.php`.
- **Cache invalidation**: write path calls `Cache::forget('general_settings')`; read path
  (`GeneralSettingsService::get`) uses `Cache::remember` with the configurable TTL.
- **Runtime config override**: middleware injects DB `theme_color` → `FilamentColor` and
  `site_name` → `config('app.name')` on every request — a pattern worth noting for any env tool
  that wants DB-backed runtime overrides.

## Dependencies (esp. Filament/Nova version)
- `php` `^8.1`
- **`filament/filament` `^5.3`**  ← targets **Filament 5**
- `spatie/laravel-package-tools` `^1.15`
- dev: pestphp/pest `^2`, orchestra/testbench `^9`, larastan `^3`, pint, collision `^8`.
- Uses Filament-5-only namespaces: `Filament\Schemas\Components\Tabs`, `Filament\Schemas\Schema`,
  `Filament\Actions\Action` (confirms Filament 5 schema API, not the Filament 3 Forms API).

## Tests
Y — `tests/` (Pest): `ArchTest.php`, `CustomTabsTest.php`, `ExampleTest.php`, `Pest.php`,
`TestCase.php`. Testing helper mixin `src/Testing/TestsFilamentGeneralSettings.php`.

## Notes / corrections
- **PLAN CORRECTION — Filament major:** The task brief claimed the clone is tag `v1.0.27` on the
  Filament-3 (1.x) line. That is WRONG for this checkout. The clone has **no git tags at all**;
  HEAD is the **`3.x` branch**, and `composer.json` requires **`filament/filament: ^5.3`** — i.e.
  this clone targets **Filament 5**, NOT Filament 3 (and not "assumed Filament 5" — it is confirmed
  Filament 5). Per the README compatibility table: package `1.x`→Filament 3, `2.x`→Filament 4,
  `3.x`→Filament 5. So the plan's "Filament 5" assumption is CORRECT for this clone, but the
  "v1.0.27 / Filament-3 line" label is incorrect — this is the 3.x / Filament-5 line.
- **No env-editor.** Despite the brief's guess ("manages general settings + an env-editor tab?"),
  there is **no `.env` editor tab**. Tabs are Application, Analytics, SEO, Email (DB-stored mail
  creds, not env), Social Networks, and optional Custom Tabs. Mail settings are written to the DB
  (`email_settings` JSON) and loaded into Laravel's runtime mail config by `MailSettingsService`,
  never to a `.env` file.
- Single-row settings model: `update()` uses `updateOrCreate([], $data)` (empty constraints → always
  the one row).
