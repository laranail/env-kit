# CLI

EnvKit ships 23 Artisan commands. Each has a fully-qualified
`laranail::env-kit.<cmd>` name and a short `env:<cmd>` alias — use either.

## Commands

| Command | Alias | Purpose |
|---------|-------|---------|
| `…​.set {key} {value?}` | `laranail::env-kit.set` | Set/create a key. Accepts `KEY VALUE` or `KEY=VALUE`. `--export` adds the export prefix. |
| `…​.get {key}` | `laranail::env-kit.get` | Print a value (`--default=` when absent). |
| `…​.unset {key}` | `laranail::env-kit.unset` | Remove a key. |
| `…​.keys` | `laranail::env-kit.keys` | List every key. |
| `…​.list` | `laranail::env-kit.list` | List `KEY=VALUE`, secrets masked (`--reveal` to show). |
| `…​.rename {from} {to}` | `laranail::env-kit.rename` | Rename a key in place. |
| `…​.backup` | `laranail::env-kit.backup` | Snapshot the file. |
| `…​.backups` | `laranail::env-kit.backups` | List backups (newest first). |
| `…​.backup-delete {name?}` | `laranail::env-kit.backup-delete` | Delete a named backup, or `--older-than=DAYS` to prune by age. |
| `…​.restore {name?}` | `laranail::env-kit.restore` | Restore a backup (latest if unnamed). |
| `…​.validate` | `laranail::env-kit.validate` | Check every key/value for well-formedness **and** the configured schema. |
| `…​.sync` | `laranail::env-kit.sync` | Add keys present in `.env.example` but missing from `.env` (`--example=`). |
| `…​.check` | `laranail::env-kit.check` | List missing example keys; non-zero exit on drift (`--example=`, CI-friendly). |
| `…​.generate {type=token}` | `laranail::env-kit.generate` | Generate a secret (`--bytes=`); `--set=KEY` writes it to a key. |
| `…​.encrypt-value {key}` | `laranail::env-kit.encrypt-value` | Encrypt a single key's value in place. |
| `…​.decrypt-value {key}` | `laranail::env-kit.decrypt-value` | Decrypt a single key's value back to plaintext. |
| `…​.edit` | `laranail::env-kit.edit` | Interactive TUI editor (see [TUI](tui.md)). |
| `…​.doctor` | `laranail::env-kit.doctor` | Run health-check rules (see [Doctor](doctor.md)). |
| `…​.diff {against}` | `laranail::env-kit.diff` | Compare against another file, by key. |
| `…​.export` | `laranail::env-kit.export` | Export as `--format=json\|csv\|dotenv\|yaml` to stdout or `--output=`. |
| `…​.import {source}` | `laranail::env-kit.import` | Import from a json/csv/dotenv/yaml file. |
| `…​.history` | `laranail::env-kit.history` | Show recent audit history — who changed which keys, when (`--limit=20`). Values are never shown. |
| `…​.docs` | `laranail::env-kit.docs` | Render the resolved validation schema as a Markdown table (`--output=` to write a file). |

```bash
php artisan laranail::env-kit.set MAIL_HOST=smtp.acme.test
php artisan laranail::env-kit.get APP_NAME --default=Laravel
php artisan laranail::env-kit.list --reveal
php artisan laranail::env-kit.export --format=json --output=storage/env.json
php artisan laranail::env-kit.import storage/env.json
php artisan laranail::env-kit.restore                 # latest backup
php artisan laranail::env-kit.backup-delete --older-than=30   # prune backups older than 30 days
php artisan laranail::env-kit.check                   # CI: exit 3 if .env drifts from .env.example
php artisan laranail::env-kit.sync                    # add the missing example keys
php artisan laranail::env-kit.generate app_key --set=APP_KEY  # generate a secret and write it
php artisan laranail::env-kit.encrypt-value STRIPE_SECRET     # encrypt one value in place
php artisan laranail::env-kit.history --limit=50              # recent changes (keys + actor + time)
php artisan laranail::env-kit.docs --output=docs/env-schema.md  # render the schema as Markdown
```

## Per-value encryption vs. Laravel core

`laranail::env-kit.encrypt-value` / `laranail::env-kit.decrypt-value` encrypt a **single value** in place
(read it back with `EnvKit::getDecrypted()` — see [Encryption](encryption.md)).
They are deliberately **not** aliased to Laravel's core `env:encrypt` /
`env:decrypt`, which encrypt the **whole file** — EnvKit never shadows those.

## Global options

- `--file=PATH` — operate on a custom `.env` file instead of the configured one.
- `--force-production` — permit the write in production (on write commands).

## Exit codes

Commands return a stable contract, so scripts and CI can branch on them:

| Code | Meaning |
|------|---------|
| `0` | Success |
| `2` | Usage error (bad arguments) |
| `3` | Validation / policy failure (invalid key, protected key, production guard) |
| `4` | Conflict (file changed underneath the edit) |
| `5` | I/O error (not writable, lock failure, integrity mismatch) |

```bash
php artisan laranail::env-kit.set APP_NAME=Acme || echo "failed with code $?"
```

## Why `::` in the name?

The `laranail::env-kit.*` shape mirrors the package's composer slug so the
source of a command is unambiguous across the laranail family. The `::` separator
is enabled by the command base from `laranail/console`; the short `env:*` aliases
are always available too.

---

[← Docs index](../../README.md#documentation)
