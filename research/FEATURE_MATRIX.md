# research/FEATURE_MATRIX.md — consolidation decision grid (Phase 3, finalized)

**Status: finalized 2026-06-28.** Every unique feature found across the 26 `*.FEATURES.md` inventories
is resolved below — **no TBD rows**. Decisions fold in the Phase 2 source corrections (see
`INDEX.md` → "Phase 2 verified findings").

Decision values: **keep** (jackiedo covers it ≥ as well) · **adopt** (take a superior/missing feature) ·
**merge** (combine takes from several) · **propose** (net-new — no source ships it) · **drop**
(niche / KISS / anti-pattern). H = headless · W = webui.

## Core — parser / document / writer

| Feature | Best source(s) | H | W | Decision | Rationale |
|---|---|:-:|:-:|---|---|
| Comment/format-preserving parse+write | jackiedo, jobmetric | ✅ | — | merge | base round-trip + jobmetric value-normalization. |
| Single modern parser (drop legacy V1/V2) | koel | ✅ | — | keep | koel's lesson: one ParserV3, no version branching. |
| LF/CRLF + BOM + encoding preservation | alezhu (EOL only) | ✅ | — | propose | alezhu has `get/setEOLMode`; we extend to full byte-fidelity (§3B). |
| Value normalization (null/bool/array/JSON, quote on ws/`#`/`=`) | jobmetric | ✅ | — | adopt | into `ValueFormatter`, aligned to phpdotenv escape set. |
| Value-encoding/quoting spec + phpdotenv round-trip conformance | vlucas/phpdotenv | ✅ | — | propose | §3B; phpdotenv = the conformance oracle. |
| `${VAR}` interpolation (brace-only, resolve-on-read, literal-store) | vlucas/phpdotenv | ✅ | ✅ | adopt | phpdotenv resolves only `${VAR}`, not bare `$VAR` — match it. |
| No-op writes skip backup/audit | — | ✅ | ✅ | propose | §3B idempotency. |

## Read API

| Feature | Best source(s) | H | W | Decision | Rationale |
|---|---|:-:|:-:|---|---|
| `get($k, $default = null)` | amdadulhaq | ✅ | ✅ | adopt | jackiedo `getValue` has **no** default. |
| jackiedo aliases (`getValue/keyExists/getKeys/getEntries/getContent`) | jackiedo | ✅ | ✅ | keep | compat layer. |
| Typed getters `getBool/getInt/getFloat/getArray/getJson` | — | ✅ | ✅ | propose | net-new. |
| `has/missing/all/only/except/keys` | jackiedo (+Laravel idiom) | ✅ | ✅ | keep | Laravel-idiomatic surface. |
| `group('PREFIX')` read | — | ✅ | ✅ | propose | net-new (geo-sot has group on write only). |
| `raw()` full content · `entry()/entries()` metadata | jackiedo | ✅ | ✅ | keep | `getContent`/`getEntries`. |

## Write API

| Feature | Best source(s) | H | W | Decision | Rationale |
|---|---|:-:|:-:|---|---|
| `set($k,$v,$options)` (`comment`/`export`/`group`/`quote`) | jackiedo + geo-sot | ✅ | ✅ | merge | jackiedo setter + geo-sot group. |
| Group **+ index** aware insertion | geo-sot | ✅ | ✅ | adopt | `addKey($k,$v,['group'=>,'index'=>])` → our `set` options + `moveToGroup`. |
| `setMany`/`setKeys` batch | jackiedo, dacoto | ✅ | ✅ | keep | |
| `update` (existing-only) · `setOrUpdate` | amdadulhaq | ✅ | ✅ | adopt | `update` throws `KeyNotFoundException`. |
| `setIfMissing` | — | ✅ | ✅ | propose | net-new. |
| `forget/forgetMany` (`deleteKey/deleteKeys`) | jackiedo | ✅ | ✅ | keep | |
| `rename` | msztorc | ✅ | ✅ | adopt | `renameVariable`. |
| `comment/addComment/addEmptyLine/setExport` | jackiedo | ✅ | ✅ | keep | |
| Typed write coercion (true/false/null literals) | vtmdev | ✅ | ✅ | adopt | vtmdev's one real change; fold into ValueFormatter. |
| **3 persistence modes** (immediate auto-commit / `transaction()` / staged `open()…save()`) gated by `auto_commit` | jackiedo(save) + amdadulhaq/geo-sot(auto) + msztorc(hybrid) | ✅ | ✅ | propose | unifies the 3 source models. |
| Change detection `isDirty/changes/discard` | jackiedo `hasChanged`, msztorc `wasChanged/isSaved` | ✅ | ✅ | keep | |

## Backups

| Feature | Best source(s) | H | W | Decision | Rationale |
|---|---|:-:|:-:|---|---|
| Named backup `backups()->create($name)` | amdadulhaq | ✅ | ✅ | adopt | |
| List / latest / restore / delete | jackiedo, geo-sot | ✅ | ✅ | keep | |
| Auto-backup toggle | jackiedo | ✅ | ✅ | keep | |
| Retention `deleteOlderThan` / prune | — | ✅ | ✅ | propose | net-new. |
| Remote backup disks (Laravel Filesystem, S3) | — | ✅ | ✅ | propose | net-new. |
| Restore creates pre-restore backup | — | ✅ | ✅ | propose | §9 reversible restore. |
| Upload `.env` file (as backup/active) | geo-sot, brotzka | — | ✅ | adopt | webui. |

## Schema / validation

| Feature | Best source(s) | H | W | Decision | Rationale |
|---|---|:-:|:-:|---|---|
| Validation chain (`required/notEmpty/isInteger/isBoolean/allowedValues/allowedRegexValues/ifPresent`) | vlucas/phpdotenv, mathiasgrimm | ✅ | ✅ | merge | note `ifPresent` is on `Dotenv` / `allowedValues($choices)`. |
| Config-driven rules | mathiasgrimm | ✅ | ✅ | adopt | into `config('env-kit.schema')`. |
| `schema()` builder + `schema_file` + `.env` annotations, layered precedence | — | ✅ | ✅ | propose | §3B / decision 20. |
| Reusable Rule objects (`ValidEnvKey/ValidEnvValue/MatchesEnvSchema`) | — | ✅ | ✅ | propose | shared by CLI/programmatic/webui. |
| Key identifier validation that **allows digits** | (anti-pattern: imliam/sven) | ✅ | ✅ | propose | fix `S3_BUCKET` rejection bug. |

## Security / guardrails

| Feature | Best source(s) | H | W | Decision | Rationale |
|---|---|:-:|:-:|---|---|
| Atomic LOCK_EX + tmpfile+fsync+rename + ConflictDetector + CircuitBreaker + stale-lock | jobmetric (LOCK_EX only) | ✅ | — | merge | jobmetric writes in-place; **we go further** (real atomic + optimistic-lock). |
| Secret redaction (exception-construction + Monolog) | leocavalcante | ✅ | ✅ | adopt | length-preserving partial mask, configurable char/template. |
| Sensitive-key **masking** vs **hide** | geo-sot/filament (hide), leocavalcante (mask) | ✅ | ✅ | merge | `hidden_keys` mask in listings (readable in code). |
| Layered key policy `protected_keys`/`hidden_keys`/`editable_keys` | tamer-dev (APP_KEY guard) | ✅ | ✅ | merge+propose | generalize APP_KEY guard; §9. |
| Production guard — all surfaces, **save-time**, + banner | joaopaulolndev (render-time), tamer-dev | ✅ | ✅ | merge | sources guard render/APP_KEY only; we guard the **pipeline** (stronger) + banner. |
| PathGuard (traversal / allowed disks) | — | ✅ | ✅ | propose | net-new. |
| File mode/owner preserve; refuse world-readable | — | ✅ | — | propose | §9. |
| IP allowlist + auth (done correctly) | fadllabanie (broken) | — | ✅ | adopt | reimplement properly (fadllabanie's is unenforced). |
| Confirm-password before destructive ops | cranux (confirm dialog) | — | ✅ | adopt | |
| Event-payload redaction | — | ✅ | ✅ | propose | §9 — no secrets to listeners/logs. |
| Audit log — pluggable sinks (file/db/psr3), who/what/when/where | spatie/activitylog, owen-it (patterns) | ✅ | ✅ | propose | net-new for env editing. |

## Encryption

| Feature | Best source(s) | H | W | Decision | Rationale |
|---|---|:-:|:-:|---|---|
| Encryption-at-rest `local-aes` + optional KMS; **wraps** Laravel `env:encrypt`/`env:decrypt` | (refs) stechstudio, lupennat | ✅ | — | propose | never shadow core commands. |
| Key rotation / re-encrypt | — | ✅ | — | propose | net-new. |

## CLI

| Feature | Best source(s) | H | W | Decision | Rationale |
|---|---|:-:|:-:|---|---|
| `env:set` KEY=VALUE + quoted spaces + custom file | imliam | ✅ | — | adopt | |
| `-L`/`--line-break` | sven | ✅ | — | adopt | |
| `--file` (all), `-b`/`--backup` | tamer-dev | ✅ | — | adopt | |
| `env:get` (read, typed, default) | tamer-dev `env:read` | ✅ | — | adopt | rename to `env:get`. |
| `env:keys`/`env:list` | jackiedo, sven | ✅ | — | keep | |
| `env:rename` | msztorc | ✅ | — | adopt | |
| backup/restore/backups/backup:delete | jackiedo | ✅ | — | keep | |
| `env:validate`/`diff`/`doctor`/`generate`/`history`/`docs`/`import`/`export`/`encrypt`/`decrypt`/`edit`(TUI) | — | ✅ | — | propose | net-new verbs. |
| Namespaced `laranail::env-kit-headless.*` + `env:*` aliases; exit-code contract | — | ✅ | — | propose | decisions 3/§3. |
| Dead `env:example` stub | sven | — | — | drop | anti-pattern; superseded by `.sync`. |

## `.env.example` sync / import-export / docs

| Feature | Best source(s) | H | W | Decision | Rationale |
|---|---|:-:|:-:|---|---|
| `sync`/`diff`/`check` `.env` ↔ `.env.example` | jtant | ✅ | — | adopt | (jtant diffs by key only / append-only — we do full diff). |
| Scan config `env()` calls for referenced keys | worksome/envy (AST) | ✅ | — | adopt | better than regex; find missing/unused keys. |
| `.env.example` scaffold (strip values) | — | ✅ | — | propose | net-new. |
| Import/export JSON + YAML/CSV/dotenv (`Porter`); `toArray/toJson/toCollection` | brotzka (JSON export) | ✅ | ✅ | merge | brotzka JSON only; we add formats. |
| `env:docs` (schema/annotations → markdown) | — | ✅ | — | propose | net-new. |

## WebUI / adapters

| Feature | Best source(s) | H | W | Decision | Rationale |
|---|---|:-:|:-:|---|---|
| Inline/structured edit + optional raw-file mode | geo-sot (modal), joaopaulolndev (Ace), marianvlad (raw) | — | ✅ | merge | structured default + validated raw mode. |
| Grouped + **searchable** keys | — | — | ✅ | propose | sources lack search. |
| Diff/preview before save | — | — | ✅ | propose | net-new in UI. |
| Backup/restore UI + upload | geo-sot, brotzka | — | ✅ | adopt | |
| Multi-file / profile switch | — | — | ✅ | propose | net-new. |
| Audit-trail viewer | — | — | ✅ | propose | net-new. |
| Role/authorization callbacks (`authorize`, `canAccess/canEdit`, policy) | geo-sot/filament, outl1ne, filament/spatie | — | ✅ | adopt | |
| Framework-agnostic theme adapters (Tailwind/Bootstrap/unstyled/custom) | — | — | ✅ | propose | net-new; none are agnostic. |
| Filament 5 Plugin (`make/getId/register/boot`, page) | joaopaulolndev, geo-sot/filament | — | ✅ | adopt | save-time guard added (sources lack it). |
| Nova tool + fields + policy + caching | outl1ne/nova-settings | — | ✅ | adopt | inventory from open source; tests skip-if-absent. |
| Tabbed settings grouping | joaopaulolndev/filament-general-settings | — | ✅ | drop | DB-settings pattern, not `.env`; out of scope v1. |
| Legacy Vue1/2 + AdminLTE-coupled views | brotzka, cranux, marianvlad | — | — | drop | obsolete stacks; we use Livewire/agnostic. |
| `hasAccessToPage()` ad-hoc contract | dipesh79 | — | — | drop | use Gate/Policy instead. |

## Runtime extensibility & testing (net-new — no source has any)

| Feature | H | W | Decision | Rationale |
|---|:-:|:-:|---|---|
| Manager drivers + Macroable + container `extend()` + tagging + Pipeline + fluent DSL | ✅ | ✅ | propose | §2A Open/Closed; zero source offers runtime extensibility. |
| `EnvKit::fake()` test seam | ✅ | ✅ | propose | net-new. |

---

## Gate status (Phase 3)

✅ **Every feature resolved — no TBD rows.** Counts: ~22 propose (net-new), ~14 adopt, ~10 keep,
~9 merge, ~5 drop. The headless engine is a **behavioral superset** of every mined package; the drops
are obsolete stacks or anti-patterns (digit-rejecting key regex, broken auth, dead stubs, DB-settings
tabs). Ready for the **Phase 3→4 gate** — at which the **TUI-engine decision** (`symfony/tui` vs
`laravel/prompts`) and **Infection thresholds** must be confirmed before engine coding.
