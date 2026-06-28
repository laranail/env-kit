# FEATURES — fadllabanie/env-editor
Source: fadllabanie/env-editor · v1 (no tags; `minimum-stability: dev`) · MIT · group C web-UI

## Invocation / entry
Web routes only (auto-registered by `EnvEditorServiceProvider::boot()` when
`config('env-editor.env-editor-enable')` is true). No facade, no Artisan
commands, no Nova tool. Routes (`routes/web.php`):
- `GET  /env-editor/login`  → `EnvEditorAuthController@showLoginForm` (name `env.login`, `web`)
- `POST /env-editor/login`  → `EnvEditorAuthController@login` (name `env.login.submit`, `throttle:5,1` + `web`)
- `POST /env-editor/logout` → `EnvEditorAuthController@logout` (name `env.logout`)
- `GET  /env-editor/edit`   → `EnvEditorController@index` (name `env.edit`) — **no auth/middleware**
- `POST /env-editor/update` → `EnvEditorController@update` (name `env.update`) — **no auth/middleware**

## Artisan commands (verified)
- none

## Public API (if any)
- none usable. `register()` binds `$this->app->singleton('env-editor', fn() => new EnvEditor())`,
  but **class `Fadllabanie\EnvEditor\EnvEditor` does not exist in the repo** (no
  `src/EnvEditor.php`). Resolving `app('env-editor')` would fatal. The binding is dead/broken.

## Config keys
(`config/env-editor.php`)
- `env-editor-enable` — `env('ENV_EDITOR_ENABLE', false)` — master on/off; gates route+view+publish registration. (README docs the default as `true`; the shipped config default is `false`.)
- `white_ips_list` — `env('ENV_EDITOR_WHITE_IPS_LIST', ['127.0.0.1'])` — allowed-IP list for login.
- `username` — `env('ENV_EDITOR_USERNAME', 'admin')` — login username.
- `password` — `env('ENV_EDITOR_PASSWORD', 'password')` — login password.

## UI features (group C only)
- inline edit — yes. Each existing `.env` key rendered as a Bootstrap floating-label text input (`name="env[KEY]"`), edited in place.
- grouping — no.
- search — no.
- masking — no. Values (incl. secrets/passwords) shown in plain `type="text"` inputs.
- backup-restore UI — no.
- upload — no.
- auth — yes (login form, username/password); but see Notes — not enforced on the editor pages.
- IP gating — yes (allow-list checked at login only).
- diff-preview — no.
- export — no.
- add/remove keys — yes. Alpine.js "Add New Key-Value Pairs" rows; trash icon deletes existing rows (`confirm()` dialog) by removing the DOM node so the key is omitted on submit. Logout button posts to `env.logout`.
- Parsing is naive: lines split on first `=`; comments (`#...`) and blank lines are dropped, so **on save all comments/quoting/formatting are lost** — file is rewritten as `KEY=VALUE\n` only.

## UI stack (group C only)
- Blade views (`resources/views/login.blade.php`, `edit.blade.php`), namespaced `env-editor::`.
- Bootstrap 5.3.0-alpha1 (CDN), Font Awesome 6 (CDN).
- Alpine.js 3.x (CDN) for the add/remove-row interactivity.
- No Vue / Livewire / Filament / Nova.

## Dependencies
- `php: ^8.0`
- `laravel/framework: ^11.0`
- (runtime) uses `vlucas/phpdotenv` `Dotenv\Dotenv` via Laravel; `Illuminate\Support\Facades\{File,Crypt,Session,Artisan}`.
- No dev/test dependencies declared.

## Unique selling points
- Extremely small, zero-config-ish web `.env` editor: drop-in package, one enable flag, login + IP allow-list, edit form.
- Auto-seeds random credentials: on `register()`, if `ENV_EDITOR_USERNAME`/`ENV_EDITOR_PASSWORD` are unset it appends `Crypt::encryptString(Str::random(24))` values to the project `.env`.

## Tests
N — no tests, no `tests/` dir, no PHPUnit/Pest config, no test dependency in composer.json.

## Notes / corrections
- **AUTH mechanism — present at login but NOT enforced on the editor.** Login
  (`EnvEditorAuthController::login`) validates `username`/`password` required,
  then `checkCredentials()` does a plain timing-unsafe string compare against
  config:
  ```php
  return $username === config('env-editor.username') && $password === config('env-editor.password');
  ```
  On success `authenticateSession()` sets a session flag and redirects to `env.edit`.
  **Critical gap:** the `GET /env-editor/edit` and `POST /env-editor/update`
  routes have **no middleware and no session check** — nothing reads
  `env_editor_authenticated`. Anyone who knows the URLs can view/overwrite `.env`
  without logging in. Auth is effectively decorative. (`update()` even calls
  `session()->forget('env_editor_authenticated')` and redirects to login after a
  successful save — the only place the flag is touched besides login/logout.)

- **IP gating — login-only, not a middleware, and broken on the default.**
  Enforced inline inside `login()`:
  ```php
  if ($this->isIpRestricted() && !$this->isAllowedIp($request->ip())) {
      return abort(403, 'Unauthorized access from your IP address.');
  }
  ```
  ```php
  private function isIpRestricted()
  {
      return !empty(config('env-editor.white_ips_list'));
  }
  private function isAllowedIp($ip)
  {
      $allowedIps = explode(',', config('env-editor.white_ips_list'));
      return in_array($ip, $allowedIps);
  }
  ```
  There is **no IP middleware** — `routes/web.php` references none. Config key is
  `white_ips_list`. Two defects: (1) the default config value is an **array**
  `['127.0.0.1']`, but `isAllowedIp()` calls `explode(',', ...)` which requires a
  **string** — passing the array throws a `TypeError` in PHP 8, so login dies
  unless `ENV_EDITOR_WHITE_IPS_LIST` is set as a comma string in `.env`. (2) Gating
  only happens at the login POST; the unprotected `/edit` and `/update` routes
  bypass IP restriction entirely.

- **SESSION TIMEOUT — README's "2 minutes" claim is FALSE (no-op).** The README
  ("Session Time: The session expires after 2 minutes by default") is not backed
  by working code. `authenticateSession()`:
  ```php
  session(['env_editor_authenticated' => true], now()->addMinutes(2));
  ```
  The Laravel `session()` helper signature is `session($key = null, $default = null)`.
  When `$key` is an array it writes the pairs and **ignores the second argument**;
  `now()->addMinutes(2)` is silently discarded as an unused `$default`. There is
  **no TTL, no expiry timestamp, no per-session lifetime** set — the flag persists
  for the normal app session lifetime. And since no route ever checks the flag,
  even a real expiry would be moot. Effective session timeout: none (relies on
  global `config/session.php` lifetime only, and that flag is never gate-checked).

- Other: README's sample config default `'env-editor-enable' => true` disagrees
  with the shipped `config/env-editor.php` (`false`). `update()` rewrites `.env`
  from scratch, dropping all comments/blank lines/quoting. CSRF is present on
  forms. `throttle:5,1` rate-limits login attempts only.
