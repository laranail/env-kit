# CLI

EnvKit ships 14 Artisan commands. Each has a fully-qualified
`laranail::env-kit-headless.<cmd>` name and a short `env:<cmd>` alias — use either.

## Commands

| Command | Alias | Purpose |
|---------|-------|---------|
| `…​.set {key} {value?}` | `env:set` | Set/create a key. Accepts `KEY VALUE` or `KEY=VALUE`. `--export` adds the export prefix. |
| `…​.get {key}` | `env:get` | Print a value (`--default=` when absent). |
| `…​.unset {key}` | `env:unset` | Remove a key. |
| `…​.keys` | `env:keys` | List every key. |
| `…​.list` | `env:list` | List `KEY=VALUE`, secrets masked (`--reveal` to show). |
| `…​.rename {from} {to}` | `env:rename` | Rename a key in place. |
| `…​.backup` | `env:backup` | Snapshot the file. |
| `…​.backups` | `env:backups` | List backups (newest first). |
| `…​.restore {name?}` | `env:restore` | Restore a backup (latest if unnamed). |
| `…​.validate` | `env:validate` | Check every key/value for well-formedness. |
| `…​.edit` | `env:edit` | Interactive TUI editor (see [TUI](tui.md)). |
| `…​.doctor` | `env:doctor` | Run health-check rules (see [Doctor](doctor.md)). |
| `…​.diff {against}` | `env:diff` | Compare against another file, by key. |
| `…​.export` | `env:export` | Export as `--format=json\|csv` to stdout or `--output=`. |
| `…​.import {source}` | `env:import` | Import from a json/csv file. |

```bash
php artisan env:set MAIL_HOST=smtp.acme.test
php artisan env:get APP_NAME --default=Laravel
php artisan env:list --reveal
php artisan env:export --format=json --output=storage/env.json
php artisan env:import storage/env.json
php artisan env:restore                 # latest backup
```

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
php artisan env:set APP_NAME=Acme || echo "failed with code $?"
```

## Why `::` in the name?

The `laranail::env-kit-headless.*` shape mirrors the package's composer slug so the
source of a command is unambiguous across the laranail family. The `::` separator
is enabled by the command base from `laranail/console`; the short `env:*` aliases
are always available too.

---

[← Docs index](../../README.md#documentation)
