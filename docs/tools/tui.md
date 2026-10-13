# TUI

An interactive, full-screen `.env` editor built on `laranail/console`'s `Prompter`, which wraps
`laravel/prompts`.

```bash
php artisan laranail::env-kit.edit
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

## Without a terminal

The editor needs a person at a terminal. Run with `--no-interaction`, or with stdin that
is not a terminal (a cron job, a CI step, a pipe), it changes nothing and exits with code
`2`, naming the commands that work without one: `laranail::env-kit.set`,
`laranail::env-kit.unset` and `laranail::env-kit.rename`.

The check is `laranail/console`'s `Prompter::canPrompt()`: it answers yes when Laravel routes
prompts through its fakeable question-helper fallback (on Windows, and under the test harness),
and otherwise only when stdin is a terminal.

## Prompts go through `laranail/console`

env-kit does not declare `laravel/prompts` and never imports it: every prompt goes through
`laranail/console`'s `Prompter`, which does. `tests/Unit/PromptsBoundaryTest.php` fails if a file
under `src/` names `Laravel\Prompts`.

---

[← Docs index](../../README.md#documentation)
