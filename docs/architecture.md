# Architecture

EnvKit is a small set of single-responsibility objects composed behind one root
service. Everything that writes goes through the same commit pipeline.

## Layers

```
EnvKit (root service, implements EnvKitInterface)
 ├── Document      immutable, format-preserving model of the file
 ├── Session       EditSession — stage → diff → commit
 ├── Pipeline      CommitPipeline — validate → guard → middleware → backup → write → verify → audit
 ├── Writer        AtomicEnvWriter + IntegrityVerifier
 ├── Security      validators · sanitizer · redactor · guards · cipher
 ├── Backup        BackupManager
 ├── Audit         sinks + AfterWrite event
 └── Extension     EnvKitConfigurator (the configure() DSL) + EnvKitManager
```

The three faces — programmatic API, CLI commands, and the TUI — are all thin
wrappers over the **same** `EnvKit` engine. None of them re-implement parsing,
writing, or guarding.

## The document model

`EnvDocument` is an immutable, ordered list of entries (`Setter`, `Comment`,
`EmptyLine`). Each setter remembers its **original** raw line, so an untouched key
re-renders byte-for-byte; only changed values are re-encoded through
`ValueFormatter`. The parser detects and preserves BOM, EOL (LF/CRLF), and the
trailing-newline. The result round-trips conformantly with `vlucas/phpdotenv`.

Encoding rules (see `ValueFormatter`): values are quoted only when they must be
(whitespace, `#`, quotes, `$`, backticks, backslash, or leading/trailing
whitespace); a bare `=` does **not** force quoting. Double-quoted values escape
`\" \\ \$ \n \r \t`.

## The commit pipeline

Every write — from any surface — runs through `CommitPipeline` as an
`Illuminate\Pipeline` of focused pipes:

1. **ValidateKeys** — each changed key matches `^[A-Za-z_][A-Za-z0-9_]*$`.
2. **Guard** — production guard + protected-key guard.
3. **…middleware** — any consumer-pushed pipes (see [Extending](extending.md)).
4. **Backup** — snapshot the pre-write file (when `auto_backup`).
5. **Write** — `AtomicEnvWriter`: temp file → `flock` → `fwrite` → `fflush` →
   `fsync` → atomic `rename`. A reader never sees a partial file.
6. **Verify** — re-parse the written file and compare; on mismatch, **roll back**
   to the captured previous contents and raise `IntegrityException`.
7. **Audit** — record the (redacted) change set to every sink and dispatch
   `AfterWrite`. Runs only on success.

Optimistic concurrency: `EditSession` fingerprints the file when it opens and
refuses to commit if the file changed underneath it (`ConflictException`).

## Security core

A single shared core is reused by every surface (and by the web companion's
validation):

- `KeyValidator` / `ValueSanitizer` — well-formedness; the sanitizer rejects NUL
  and strips control characters (except tab/newline/CR).
- `SecretRedactor` — masks secret-shaped values (`*_PASSWORD`, `*_SECRET`,
  `*_TOKEN`, `*_KEY`, …). Redaction happens at exception construction and event
  build time, so a raw secret never escapes.
- `ProtectedKeys` / `ProductionGuard` — the write guards.
- `LaravelValueCipher` — per-value encryption (see [Encryption](tools/encryption.md)).

## Exceptions

All extend `EnvKitException`. Messages reference key names and reasons, **never raw
values**. The CLI maps them to a stable exit-code contract (see [CLI](tools/cli.md)).

---

[← Docs index](../README.md#documentation)
