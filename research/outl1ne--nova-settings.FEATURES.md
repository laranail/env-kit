# FEATURES — outl1ne/nova-settings
Source: github.com/outl1ne/nova-settings · 6.0.2 (tag, 2025-09-01) · MIT · group D adapter

## What it is / entry
Nova tool. A Laravel Nova 5 tool for editing arbitrary application settings using native Nova fields, persisted to a key/value DB table. Registered as a Nova `Tool` (`Outl1ne\NovaSettings\NovaSettings`) in the app's `NovaServiceProvider::tools()`; fields are declared in `boot()`. No Filament, no Artisan commands. Auto-discovered service provider: `Outl1ne\NovaSettings\NovaSettingsServiceProvider`.

## Public API or plugin surface (verified signatures)
Static facade-style API on `Outl1ne\NovaSettings\NovaSettings` (src/NovaSettings.php):
- `NovaSettings::addSettingsFields($fields = [], $casts = [], $path = 'general')` — register fields/panels (array or callable returning array) plus optional Laravel-style `$casts`, grouped under a settings page `$path` (slugified). Returns the store (chainable).
- `NovaSettings::addCasts($casts = [])` — merge additional casts.
- `NovaSettings::getFields($path = null)` — resolved fields (raw per-path map if `$path` null).
- `NovaSettings::clearFields()` — reset registered fields/casts + cache.
- `NovaSettings::getCasts()`.
- `NovaSettings::getSetting($settingKey, $default = null)` — single value (cache-aware, cast-applied).
- `NovaSettings::getSettings(?array $settingKeys = null, array $defaults = [])` — many/all as `key => value` array.
- `NovaSettings::setSettingValue($settingKey, $value = null)` — upsert (firstOrCreate), returns model.
- `NovaSettings::getSettingsModel(): string` / `getSettingsTableName(): string` / `getStore(): NovaSettingsStore`.
- `NovaSettings::getPageName($key)`, `doesPathExist($path)`, `canSeeSettings()`, `getAuthorizations($key = null)`.
- Tool instance: `NovaSettings::make()->canSee(fn () => ...)` to gate the whole tool; `menu(Request)` builds sidebar `MenuSection`/`MenuItem` per path.

Global helper functions (src/helpers.php, autoloaded): `nova_get_settings($keys = null, $defaults = [])`, `nova_get_setting($key, $default = null)`, `nova_set_setting_value($key, $value = null)`.

Storage: single key/value table (default `nova_settings`) via migration `database/migrations/2019_08_13_000000_create_nova_settings_table.php` — columns `key` (string, primary/unique) and `value` (text, nullable; widened by `2021_02_15_000000_update_nova_settings_value_column`). Eloquent model `Outl1ne\NovaSettings\Models\Settings` (primaryKey `key`, no incrementing, no timestamps). Casting handled in the model's `setValueAttribute`/`getValueAttribute` using `NovaSettings::getCasts()` keyed by setting key; arrays/JsonSerializable are json_encoded, date/datetime stored raw.

## Artisan commands (if any)
None.

## Config keys
(config/nova-settings.php; `mergeConfigFrom`, publishable with tag `config`)
- `table` — `'nova_settings'` — settings table name.
- `base_path` — `'nova-settings'` — URL path of the settings page within Nova.
- `reload_page_on_save` — `false` — full page reload after save (for Nova-UI-affecting settings).
- `models.settings` — `Outl1ne\NovaSettings\Models\Settings::class` — overridable Eloquent model (must extend the original).
- `show_in_sidebar` — `true` — show the sidebar menu section.
- `cache.store` — `env('NOVA_SETTINGS_CACHE_DRIVER', ':memory:')` — cache backend: a Laravel cache store name → `NovaSettingsCacheStore`; `:memory:` → `NovaSettingsInMemoryStore` (singleton in-memory); `null`/other → `NovaSettingsNoCacheStore`.
- `cache.prefix` — `'nova-settings:'`.

## Patterns to mine
- **Authorization (canAccess / canEdit split):** the store fronts a hidden Nova `Resource` (`src/Nova/Resources/Settings.php`, `$displayInNavigation = false`). `NovaSettings::getAuthorizations()` instantiates that resource and reads `authorizedToView/Create/Update/Delete` — i.e. it routes through the app's normal Nova policy on the settings model. `canSeeSettings()` = view OR update. `SettingsController::get` requires `canSeeSettings()`; `save`/`deleteImage` require `authorizedToUpdate` specifically (read vs edit separation). Whole-tool gate via `NovaSettings::make()->canSee(...)`; per-field gate via Nova's native `$field->canSee(...)` (fields filtered through `FieldCollection::authorized()` in `availableFields()`).
- **Route middleware guard:** API routes (`routes/api.php`) are wrapped in `['nova', Authorize::class, SettingsPathExists::class]`. `Http/Middleware/Authorize` finds the registered `NovaSettings` tool and calls its `->authorize($request)` (the tool's `canSee`), aborting 403 otherwise. `SettingsPathExists` validates the `{path}` segment against registered paths (`doesPathExist`).
- **Validation chain:** on `save`, for each field it builds a fake `Fluent` resource, resolves the field (nova-translatable support), then accumulates `$field->getUpdateRules($request)` and runs `Validator::make($request->all(), $rules)->validate()` before persisting — so per-field Nova validation rules apply. Readonly fields are skipped (`$field->isReadonly`).
- **No explicit production guard / sensitive-key hiding** beyond the policy/`canSee` authorization layer — there is no built-in "hide in production" or secret-masking mechanism; access control is entirely via Nova authorization.
- **Field nesting support:** `findField` recurses into `Laravel\Nova\Panel` and (optionally) `Eminiarts\Tabs\Tabs`; NovaDependencyContainer fields (`meta['fields']`) are flattened on save. Markdown field preview endpoint (added 6.0.2) calls `$field->previewFor($content)`.

## Dependencies (esp. Filament/Nova version)
- `php >= 8.1`
- `laravel/nova: ^5.0`  (Nova 5 only; 6.x line)
- `outl1ne/nova-translations-loader: ^5.0`
- dev: `laravel/nova-devtool ^1.0`, `nunomaduro/collision ^7.8`, `orchestra/testbench ^8.30|^9.8`
- Composer repo `https://nova.laravel.com`; `minimum-stability: dev`, `prefer-stable: true`.

## Tests
Y. PHPUnit Feature + Browser (Dusk) tests under `tests/` — e.g. `tests/Feature/SettingsSaveTest.php`, `SettingsRetrieveTest.php`, `SettingsHelpersTest.php`, `SettingsCastTest.php`, `NavigationTest.php`; `tests/Browser/DetailTest.php`. Config: `phpunit.xml.dist`, `phpunit.dusk.xml.dist`, `testbench.yaml`.

## Notes / corrections
- Version: latest is **6.0.2** (only git tag present; HEAD grafted at that tag). 6.0.0 added Nova 5 support; the 6.x line requires `laravel/nova ^5.0`.
- "Settings page" is multi-page: each `$path` becomes its own sidebar entry (`MenuSection` if one page, collapsable `MenuSection` of `MenuItem`s if several). Frontend is an Inertia page `NovaSettings` served at `{base_path}/{pageId?}`.
- Storage is a flat key/value table — every Nova field's `attribute` becomes a row key; values are text with optional Laravel casts applied at the model boundary. There is no per-key encryption; secrets would persist in plaintext unless the consumer adds an `encrypted` cast.
- Cache invalidation: model `updated` event calls `getStore()->clearCache($key)`.
