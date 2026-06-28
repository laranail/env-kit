# BUILD-LOG — laranail/env-kit-headless

Append-only log of build progress. Each entry: what changed · tests covering it · what's still open.
Spec: `_scratch-files/dotenv-editor-consolidation-plan.md`. Process: the EnvKit build-runner prompt.

## Phase checklist

- [x] **Phase 0 — repo setup** — git init + identity, `.gitignore`/`.gitattributes`, BUILD-LOG.
- [x] **Phase 1 — discovery** — 26 repos cloned → `research/_src/` (gitignored); `research/INDEX.md` complete, all licenses ✅.
- [x] **Phase 2 — feature inventory** — 26 `research/<pkg>.FEATURES.md`; signatures re-verified vs source.
- [x] **Phase 3 — gap analysis** — `research/FEATURE_MATRIX.md` finalized, every row decided (no TBD).
- [ ] **Phase 4 — build headless** — engine, EditSession, guardrails, CLI, TUI, audit, encryption, extensibility; full test regime green.
- [ ] **Phase 6 — docs** — README + docs/ set incl. `extending.md`.
- [ ] **Phase 7 — release** — only after explicit approval.

## Open items (from the approved Phase 1 plan)

- TUI engine fork (`symfony/tui` vs `laravel/prompts`) — **parked until Phase 3→4 gate**; do not require `symfony/tui` yet.
- `laranail/package-scaffolder` does not exist — decision 14 relaxed (manual scaffold).
- Regression tests authored **new** (no copied third-party test code); license-gated per INDEX.
- Infection start thresholds: MSI ≥ 85% / covered-MSI ≥ 90% on engine core.
- Confirm `vlucas/phpdotenv` constraint matches Laravel 13's during Phase 2.

## Log

### Phase 0 — repo setup (done 2026-06-28)
- `git init` (branch `main`) + GitHub-noreply identity in headless & webui.
- Added `.gitignore` (ignores `/research/_src/`, vendor, caches), `.gitattributes` (export-ignore
  research/tests/.github), `BUILD-LOG.md`. Committed scaffold baseline.

### Phase 1 — discovery (done 2026-06-28)
- Resolved Packagist metadata + shallow-cloned **26** mining repos into `research/_src/` (gitignored).
- `research/INDEX.md`: 27 rows (26 cloned + filament/spatie doc-inventoried). **All licenses ✅** (MIT,
  except `vlucas/phpdotenv` BSD-3-Clause). Only `encodia` flagged abandoned.
- Corrections logged: phpdotenv is BSD-3 + v5.6 (no v6); `jtant/laravel-env-sync` (not `juliantant`);
  jobmetric latest 2.2.1; `filament-general-settings` latest-by-date is the Filament-3 line (re-verify 3.x).
- `research/FEATURE_MATRIX.md` seeded (finalize in Phase 3).

### Phase 2 — feature inventory (done 2026-06-28)
- Wrote 26 `research/<pkg>.FEATURES.md` (3 parallel agents read cloned source). Signatures verified.
- Confirmed corrections (see INDEX "Phase 2 verified findings"): jackiedo `getValue` no-default;
  amdadulhaq service-not-facade; msztorc facade unbound; jobmetric LOCK_EX in-place (we do better);
  tamer-dev `env:read`; geo-sot options-array insertion; phpdotenv parse/escape model → locks §3B;
  filament-general-settings HEAD is FL5/3.x (DB-settings, not env editor); filament-edit-env guard is
  render-time only (our pipeline guard is stronger); fadllabanie auth is broken (anti-pattern).

### Phase 3 — gap analysis (done 2026-06-28)
- `research/FEATURE_MATRIX.md` finalized: every feature resolved (no TBD). ~22 propose / 14 adopt /
  10 keep / 9 merge / 5 drop. Headless = behavioral superset; drops = obsolete stacks + anti-patterns.
- **Phase 3→4 gate: needs the TUI-engine decision (`symfony/tui` vs `laravel/prompts`) + Infection
  thresholds before engine coding.**
