# FEATURES — marianvlad/nova-env-card

Source: https://github.com/marianvlad/nova-env-card · no tagged version (`minimum-stability: dev`, unversioned) · MIT · group C web-UI

## Invocation / entry
Laravel **Nova Card** (`Marianvlad\NovaEnvCard\NovaEnvCard extends Laravel\Nova\Card`, component `nova-env-card`, width `1/3`). Registered in a host app's `NovaServiceProvider::cards()` and gated with `->canSee(...)` (README example checks `$request->user()->role == 'admin'`). The card renders an "Edit Environment File" button that opens a Nova modal. Auto-discovered service provider `CardServiceProvider` registers two API routes and injects the compiled `dist/js/card.js` / `dist/css/card.css` via `Nova::serving`.

Nova version: `composer.json` requires `laravel/nova: "*"` (any version — unpinned). Code uses the classic Nova UI API (`Nova.booting((Vue, router) => …)`, `Nova.request()`, `card`/`modal`/`heading`/`portal` Vue components, Tailwind classes like `bg-90`, `text-80`), i.e. **Nova 1.x / 2.x / 3.x era** (Vue 2 stack), not Nova 4+.

## Artisan commands (verified)
- none — package ships no Artisan commands.

## Public API (if any)
- `NovaEnvCard::component()` — returns `'nova-env-card'` (Nova card component name). No other public/business API.
- `EnvironmentController::show()` — returns `file_get_contents(base_path('.env'))` (raw .env body).
- `EnvironmentController::update(Request $request)` — writes `file_put_contents(base_path('.env'), $request->value)` (overwrites entire .env with posted `value`).

## Config keys
- none — no config file, no publishable config, no env-driven options. Target file is hardcoded to `base_path('.env')`.

## UI features (group C only)
- **Inline / full-file edit** — yes. The whole `.env` is loaded into a CodeMirror editor inside a modal; user edits free-form text and saves. Not per-key editing.
- **Syntax highlighting** — yes. CodeMirror 5 with shell mode (`text/x-sh`), line numbers, line wrapping, tab size 4, indent-with-tabs.
- **Grouping** — no.
- **Search** — no (beyond whatever CodeMirror provides by default; no search UI added).
- **Masking / secret hiding** — no. Values shown in plaintext.
- **Backup / restore UI** — no. Save overwrites `.env` with no backup.
- **Upload / download / export** — no.
- **Diff preview** — no.
- **Auth** — yes (two layers): card-level `->canSee()` closure (host-defined), plus the `Authorize` middleware on the API routes which confirms `NovaEnvCard::class` appears in `Nova::availableCards()`/`availableTools()` for the request, else `abort(403)`. Routes also run behind the `nova` middleware group.
- **IP gating** — no.

## UI stack (group C only)
- **Nova** card (Vue 2 era — Nova 1.x–3.x). `laravel/nova: "*"`.
- **Vue** `^2.5.0` (Vue 2). Single-file components `Card.vue`, `Editor.vue`.
- **CodeMirror** `^5.40.0` editor (shell mode).
- Build: Laravel Mix `^1.0` + webpack (`webpack.mix.js`), `cross-env ^5.0.0`. Compiled assets committed under `dist/`.

## Dependencies
- PHP: `php >= 7.1.0`, `laravel/nova: *`.
- JS runtime deps: `vue ^2.5.0`, `codemirror ^5.40.0`.
- JS dev deps: `laravel-mix ^1.0`, `cross-env ^5.0.0`.

## Unique selling points
- Dead-simple full-`.env` editor right inside the Nova dashboard — open modal, edit, save.
- CodeMirror shell-syntax highlighting with line numbers for the `.env` body.
- Authorization piggybacks on the card's own `canSee` gate via the `Authorize` middleware (no separate config).

## Tests
N — no test suite, no `tests/` directory, no PHPUnit/Pest config, no `require-dev`.

## Notes / corrections
- Despite the family theme, this is a **raw whole-file text editor**, not a structured key/value env manager: `show()` returns the entire file, `update()` blindly overwrites it with the posted `value`. No parsing, no per-key ops, no validation, no backup.
- **No write safety**: `file_put_contents` with no error handling, no backup, no atomic write, no CSRF/value validation beyond Nova's auth. A bad save can corrupt `.env`.
- Routes are POST/GET under `nova-vendor/nova-env-card/environment`; skipped when `routesAreCached()`.
- Very old/unmaintained stack (Vue 2, CodeMirror 5, Laravel Mix 1, Nova classic UI). Will not work as-is on Nova 4+ (Inertia/Vue 3). `laravel/nova: "*"` makes the effective Nova version whatever the host resolves, but the JS targets the legacy `Nova.booting`/`Nova.request` global API.
- No CHANGELOG, no versioned release in repo; `minimum-stability: dev`.
