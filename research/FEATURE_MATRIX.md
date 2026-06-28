# research/FEATURE_MATRIX.md — consolidation decision grid

**Status: seeded (Phase 1).** Rows are filled from `INDEX.md` + §2/§3A of the spec and finalized in
**Phase 3** after per-package `FEATURES.md` (Phase 2). Gate for Phase 3: **no undecided (TBD) rows**.

Decision values: **keep** (jackiedo covers it) · **adopt** (take a superior/missing feature) ·
**merge** (combine takes) · **propose** (net-new, no source) · **drop** (niche / violates KISS).

| Feature | Category | Best source(s) | Headless | WebUI | Decision | Rationale / notes |
|---|---|---|:---:|:---:|---|---|
| Comment/format-preserving parser+writer | core | jackiedo, jobmetric | ✅ | — | merge | base behaviour + jobmetric's value normalization; ours adds LF/CRLF/BOM (§3B). |
| `get($k, $default)` | read | amdadulhaq | ✅ | ✅ | adopt | jackiedo `getValue` has **no** default. |
| Typed getters `getBool/getInt/getJson/…` | read | — | ✅ | ✅ | propose | net-new; no source ships them. |
| `group('PREFIX')` read | read | — | ✅ | ✅ | propose | net-new (geo-sot has group on write only). |
| `set` + group-aware insertion | write | geo-sot | ✅ | ✅ | adopt | `set($k,$v,['group'=>…])`. |
| `setMany` / batch | write | jackiedo, dacoto | ✅ | ✅ | keep | |
| `rename` | write | msztorc | ✅ | ✅ | adopt | `renameVariable`. |
| `setOrUpdate` / `update` / `setIfMissing` | write | amdadulhaq | ✅ | ✅ | adopt/propose | `setIfMissing` net-new. |
| Comments / blank lines / export prefix | write | jackiedo | ✅ | ✅ | keep | |
| Transactions (`transaction()` / staged `open()`) | write | — | ✅ | ✅ | propose | net-new; mirrors DB::transaction. |
| Named backup | backup | amdadulhaq | ✅ | ✅ | adopt | `backup('name')`. |
| Backups list/restore/prune + auto-backup | backup | jackiedo, geo-sot | ✅ | ✅ | keep | + restore-creates-backup (§9). |
| Remote backup disks (Filesystem) | backup | — | ✅ | ✅ | propose | net-new. |
| Atomic `LOCK_EX` write | guardrail | jobmetric | ✅ | — | merge | + tmpfile/fsync/rename, ConflictDetector (ours). |
| Validation chain | schema | vlucas/phpdotenv, mathiasgrimm | ✅ | ✅ | merge | required/int/bool/enum/regex/url. |
| Secret redaction | security | leocavalcante | ✅ | ✅ | adopt | redact at exception construction (§9). |
| Production guard | security | joaopaulolndev, tamer-dev | ✅ | ✅ | merge | all surfaces + banner (§9). |
| IP gating / auth | security (UI) | fadllabanie | — | ✅ | adopt | |
| `env:set` KEY=VALUE + quoted spaces | cli | imliam | ✅ | — | adopt | |
| `-L` line-break / `--file` / `-b` | cli | sven, tamer-dev | ✅ | — | adopt | |
| `.env.example` sync/diff/check | cli | jtant, worksome | ✅ | — | adopt | |
| Group-aware UI, masking, diff-preview, upload | ui | geo-sot, brotzka, joaopaulolndev | — | ✅ | merge | |
| Filament panel (Plugin, production guard, sensitive hide) | ui adapter | joaopaulolndev, geo-sot/filament | — | ✅ | adopt | Filament 5. |
| Nova tool (validation, field auth) | ui adapter | outl1ne/nova-settings, marianvlad | — | ✅ | adopt | inventory from open source. |
| JSON export | ui | brotzka | — | ✅ | adopt | + our YAML/CSV/dotenv Porter. |
| `${VAR}` interpolation | core | vlucas/phpdotenv | ✅ | ✅ | adopt | resolve-on-read, literal-store (§3B). |
| Audit log | guardrail | (patterns: activitylog/auditing) | ✅ | ✅ | propose | pluggable sinks; ours. |
| Encryption-at-rest | guardrail | stechstudio/env-security (ref) | ✅ | — | merge | wraps Laravel `env:encrypt`; local-aes core. |
| _… (remaining rows added during Phase 2 inventory)_ | | | | | TBD | one row per unique feature found. |
