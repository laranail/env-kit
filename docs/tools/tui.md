# TUI

An interactive, full-screen `.env` editor built on `laravel/prompts`.

```bash
php artisan env:edit
```

## What it does

A browse/edit loop:

1. **Choose a key or action** — pick an existing key, *Add a new key*, or *Quit*.
2. On a key — **Edit value**, **Rename**, **Delete** (with confirmation), or *Back*.
3. On *Add a new key* — prompt for the name then the value.

Every change goes through the same engine as the programmatic API and CLI, so it is
atomic, backed-up, guarded, and audited. If a write is refused (e.g. a protected key
or the production guard), the error is shown inline and the loop keeps running — the
editor never crashes on a guarded action.

## Options

- `--file=PATH` — edit a custom `.env` file.
- `--force-production` — permit writes when running in production.

When the app environment is `production`, the editor shows a warning banner before
the menu.

---

[← Docs index](../../README.md#documentation)
