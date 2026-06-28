# research/INDEX.md — mining-source inventory (Phase 1)

Discovery run **2026-06-28**. Metadata from the Packagist API; source shallow-cloned into
`research/_src/{vendor}--{package}/` (**gitignored — reference only, never committed**). This file and
`FEATURE_MATRIX.md` are the committed artifacts.

**License legend:** ✅ = MIT/BSD/Apache-2.0 (may mine + quote with credit) · ⛔ = GPL/AGPL/none
(behaviour-parity only, no copied code). **Result: every in-scope source is ✅** (all MIT except
`vlucas/phpdotenv` = BSD-3-Clause, still MIT-compatible).

| # | Grp | Package (Packagist) | Source | Latest (by date) | Commit | Released | License | Abandoned | MIT-compat | Notes |
|---|-----|---------------------|--------|------------------|--------|----------|---------|-----------|:---------:|-------|
| 1 | A | `jackiedo/dotenv-editor` | JackieDo/Laravel-Dotenv-Editor | 2.1.0 | 24667e3 | 2023-02-19 | MIT | no | ✅ | **Reference base** (1.25M installs). |
| 2 | A | `amdadulhaq/env-editor-laravel` | amdad121/env-editor-laravel | v2.0.0 | 8a10e50 | 2026-01-02 | MIT | no | ✅ | `get($k,$default)`, setOrUpdate, named backup. |
| 3 | A | `msztorc/laravel-env` | msztorc/laravel-env | v1.4.0 | be98829 | 2026-04-07 | MIT | no | ✅ | `rename`, wasChanged/isSaved. |
| 4 | A | `dacoto/laravel-env-set` | dacoto/laravel-env-set | 2.2.0 | d88f881 | 2026-03-01 | MIT | no | ✅ | Facade+DI parity. |
| 5 | A | `jobmetric/laravel-env-modifier` | jobmetric/laravel-env-modifier | 2.2.1 | 73aeaad | 2026-01-26 | MIT | no | ✅ | Atomic LOCK_EX, comment-preserve, value normalize. |
| 6 | A | `koel/dotenv-editor` | koel/Laravel-Dotenv-Editor | v3.0.0 | 7760dfb | 2026-03-18 | MIT | no | ✅ | jackiedo fork (L11–13). |
| 7 | A | `alezhu/dotenv-editor` | alezhu/Laravel-Dotenv-Editor | 2.3.0 | c91e80a | 2025-10-23 | MIT | no | ✅ | fork of koel. |
| 8 | A | `encodia/laravel-dotenv-editor` | encodia/laravel-dotenv-editor | 3.0.0 | 1578d5a | 2024-05-01 | MIT | **yes** | ✅ | **Abandoned** (Packagist flag); jackiedo fork. |
| 9 | A | `vtmdev/dotenv-editor` | VantomDeveloper/Laravel-Dotenv-Editor | dev-main | 575d2cf | 2023-02-18 | MIT | no | ✅ | **No stable tag**; jackiedo fork. |
| 10 | B | `sven/flex-env` | svenluijten/flex-env | v2.2.2 | 52bb51a | 2022-12-21 | MIT | no | ✅ | `env:set/get/delete/list`, `-L`. Old (L6–8). |
| 11 | B | `imliam/laravel-env-set-command` | ImLiam/laravel-env-set-command | v3.0.0 | 49a8a5f | 2026-05-09 | MIT | no | ✅ | KEY=VALUE shorthand, quoted spaces; active L12/13. |
| 12 | B | `tamer-dev/laravel-env-cli` | tamer-dev/laravel-env-cli | 1.2.0 | bf64d86 | 2023-04-14 | MIT | no | ✅ | `env:read` (not get), `--file`, `-b`, APP_KEY guard. |
| 13 | C | `geo-sot/laravel-env-editor` | GeoSot/Laravel-EnvEditor | 3.2.0 | 0a1a845 | 2025-04-14 | MIT | no | ✅ | **Group-aware insertion**, backup/restore UI, upload. |
| 14 | C | `brotzka/laravel-dotenv-editor` | Brotzka/laravel-dotenv-editor | v2.2.0 | 8456ede | 2023-01-29 | MIT | no | ✅ | Vue UI, JSON export, auto-backup. Stale. |
| 15 | C | `fadllabanie/env-editor` | Fadllabanie/Laravel-Env-Editor | v1.0.7 | 7323d6e | 2024-10-12 | MIT | no | ✅ | Auth + IP gating + session timeout. |
| 16 | C | `cranux/laravel-dotenv-editor` | cranux/laravel-dotenv-editor | 1.0.3 | 68ba7b4 | 2020-01-30 | MIT | no | ✅ | Legacy (L6 era); Vue GUI fork. |
| 17 | C | `dipesh79/laravel-env-manager` | Dipesh79/LaravelEnvManager | 0.0.1 | 9fa8302 | 2024-07-10 | MIT | no | ✅ | Blade dashboard; pre-release. |
| 18 | C | `marianvlad/nova-env-card` | marianvlad/nova-env-card | v1.0.0 | 9cfc665 | 2018-08-24 | MIT | no | ✅ | Legacy Nova modal editor, role-gate. |
| 19 | D | `joaopaulolndev/filament-edit-env` | joaopaulolndev/filament-edit-env | v3.0.0 | 58840eb | 2026-01-20 | MIT | no | ✅ | **Production guard**, Ace editor, per-user access. |
| 20 | D | `geo-sot/filament-env-editor` | GeoSot/filament-env-editor | 2.0.1 | 82fc364 | 2026-03-26 | MIT | no | ✅ | Sensitive-key hiding, auth callbacks, Filament 5. |
| 21 | D | `joaopaulolndev/filament-general-settings` | joaopaulolndev/filament-general-settings | v1.0.27 | 4f8817f | 2026-05-10 | MIT | no | ✅ | ⚠ latest-by-date is the **1.x (Filament-3) line**; a 3.x (Filament-5) line also exists — re-verify highest major in Phase 2. |
| 22 | D | `outl1ne/nova-settings` | outl1ne/nova-settings | 6.0.2 | b0ad22a | 2025-09-01 | MIT | no | ✅ | Validation rules, subpages, field-level auth (Nova 5). |
| 23 | E | `vlucas/phpdotenv` | vlucas/phpdotenv | v5.6.3 | 2af2719 | 2025-12-27 | **BSD-3-Clause** | no | ✅ | Validation API + round-trip-compat target. **Confirms `^5.6` pin valid (no v6).** |
| 24 | E | `leocavalcante/redact-sensitive` | leocavalcante/redact-sensitive | v0.4.1 | d11e1f6 | 2024-10-01 | MIT | no | ✅ | Monolog redaction/masking patterns. |
| 25 | F | `jtant/laravel-env-sync` | JulienTant/Laravel-Env-Sync | (git) | 0f3dfd8 | — | MIT | no | ✅ | Packagist vendor is **`jtant`** (not `juliantant`); `env:sync`/`diff`/`check`. |
| 26 | F | `worksome/envy` | worksome/envy | v1.5.0 | a28626b | 2026-03-02 | MIT | no | ✅ | Config-scan for missing keys. |
| 27 | D | `filament/spatie-laravel-settings-plugin` | (filament/filament monorepo) | — | — | — | MIT | no | ✅ | **doc-inventoried, not cloned** (monorepo); mine `canAccess()/canEdit()` from docs. |

## Corrections captured (carry into FEATURES.md / matrix)

- `vlucas/phpdotenv` is **BSD-3-Clause** (not MIT); current line is **v5.6.x — no v6** → the headless
  `^5.6` pin is correct (verify against Laravel 13's transitive constraint in Phase 2).
- `jobmetric/laravel-env-modifier` latest = **2.2.1** (the plan's "2026" was a date, not a version).
- `jtant/laravel-env-sync` is the real Packagist vendor (`juliantant` does not resolve).
- `joaopaulolndev/filament-general-settings` latest-**by-date** is the Filament-3 `1.x` line; the
  Filament-5 `3.x` line must be confirmed in Phase 2 (the matrix cares about the Filament-5 surface).
- Stale/abandoned for "quick-verify only": **encodia** (abandoned), **vtmdev** (dev-main),
  **cranux** (2020), **marianvlad** (2018), **brotzka** (2023). Forks with no net-new API: koel, alezhu,
  encodia, vtmdev.

## Phase 2 verified findings (from cloned source — full detail in each `*.FEATURES.md`)

**Build-impacting:**
- **jackiedo `getValue(string $key)`** — confirmed **no default arg**; throws `KeyNotFoundException`
  on missing (→ EnvKit adopts `get($k,$default)` from amdadulhaq).
- **amdadulhaq** is a **service class, not a facade** (`AmdadulHaq\EnvEditor\EnvEditor`); **auto-save**.
- **msztorc** declares a `LaravelEnv` facade that is **never bound** — real entrypoint is `new Env()`;
  hybrid `$write` (default true).
- **jobmetric** uses `file_put_contents(..., LOCK_EX)` **in place** — NOT temp+rename; its docblock
  "atomically" is overstated → **validates EnvKit's stronger choice** (LOCK_EX + tmpfile + fsync + rename).
- **tamer-dev** read command is **`env:read`** (not `env:get`) — confirmed. `--file` on all 4 commands;
  `-b/--backup` only on `env:set`; APP_KEY guard only in `env:set`.
- **geo-sot** group insertion = `addKey($key,$value,['group'=>…])` (options array); update via `editKey`.
- **phpdotenv** (BSD-3): `ifPresent()` is on `Dotenv` (not `Validator`); `allowedValues($choices)`.
  Parse model → single-quote literal; double-quote escapes only `\" \\ \$ \f \n \r \t \v`; `#` outside
  quotes starts a comment; **only `${VAR}` brace form resolved** (no bare `$VAR`) → locks the §3B spec.
- **filament-general-settings** cloned HEAD = **3.x / Filament 5** (`^5.3`) — plan's Filament-5 label
  correct; but it's a **DB-settings panel, not an `.env` editor** → mine `canAccess()/canEdit()` + tabs only.
- **filament-edit-env** production guard is **render-time only** (overridable, no save-time gate) →
  validates EnvKit's pipeline-level ProductionGuard as the stronger model.
- **geo-sot/filament-env-editor** does **hide-only** (drops keys), not `****` masking.

**Cautionary (anti-patterns to avoid, not copy):**
- **fadllabanie** "auth + IP gating + session timeout" are **broken/unenforced** (edit/update routes have
  no middleware; timeout never set) — EnvKit implements these properly (§9).
- **imliam** key validation `^[a-zA-Z_]+$` rejects digits (e.g. `S3_BUCKET`); **sven** has a dead
  `env:example` stub + a regex that misses digit-containing keys — avoid both.

**Net-new among forks:** only **alezhu** adds real API (`getEOLMode/setEOLMode/…` EOL handling); koel =
L11–13 modernization (no new API); encodia/vtmdev = trivial/stale.

## Gate status

✅ **Phase 1** — 27-row INDEX, all licenses ✅. ✅ **Phase 2** — 26 `*.FEATURES.md` written, signatures
re-verified against source, corrections logged above. Ready for **Phase 3** (finalize `FEATURE_MATRIX.md`).
