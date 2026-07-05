# laranail/env-kit-headless

[![Latest version on Packagist](https://img.shields.io/packagist/v/laranail/env-kit-headless.svg)](https://packagist.org/packages/laranail/env-kit-headless)
[![Tests](https://github.com/laranail/env-kit-headless/actions/workflows/ci.yml/badge.svg)](https://github.com/laranail/env-kit-headless/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> A view-less Laravel engine for reading and **safely editing** `.env` files — one
> code path behind a programmatic API, a CLI, and an interactive TUI.

`laranail/env-kit-headless` is the engine of the **EnvKit** family. Every mutation
flows through one transactional, atomic, guarded, audited commit path — whether you
call it from a controller, an Artisan command, or the interactive editor. It never
renders HTML or handles HTTP; the [`env-kit-webui`](https://github.com/laranail/env-kit-webui)
companion drives this engine for the web.

```php
use Simtabi\Laranail\EnvKit\Headless\Facades\EnvKit;

EnvKit::set('MAIL_HOST', 'smtp.acme.test');   // atomic · backed-up · audited
$debug = EnvKit::getBool('APP_DEBUG', false);  // typed read
```

## Why

Editing `.env` from code is deceptively risky: a half-written file, a clobbered
concurrent edit, a leaked secret in a log, or an accidental production write. EnvKit
makes those failure modes impossible by construction:

- **Format-preserving** — comments, blank lines, quoting, ordering, EOL and BOM all
  survive a round-trip (conformant with `vlucas/phpdotenv`).
- **Atomic & self-healing** — write to a temp file, `fsync`, rename; verify the result
  and **auto-rollback** on mismatch. Optimistic concurrency rejects clobbering writes.
- **Secret-safe** — secret-shaped values are redacted from logs, exceptions, audit
  records and events. Optional per-value **encryption-at-rest**, plus cryptographic
  **secret generators** (`token`/`hex`/`base64`/`app_key`).
- **Validated** — declare a [schema](docs/tools/schema.md) (config-seeded or fluent)
  and gate CI/deploys on `EnvKit::assertValid()` or `env:validate`. Keep `.env` aligned
  with its `.env.example` via `env:sync` / `env:check`.
- **Guarded** — production-write protection and a layered protected/hidden/editable key
  policy on *every* surface (programmatic, CLI, TUI), plus a pluggable
  [authorization gate + write observers](docs/authorization.md). Every CLI command
  prints a `PRODUCTION …` banner when `APP_ENV=production`.
- **Observable** — a full [lifecycle event set](docs/events.md) (redacted, actor-attributed)
  and opt-in [operator notifications](docs/notifications.md).
- **Open/Closed** — reshape the engine from your own service provider with zero source
  edits (fluent DSL, Macroable, driver registry, pipeline middleware, container tags).

## Install

```bash
composer require laranail/env-kit-headless
```

Requires **PHP 8.4.1+** and **Laravel 13**. The service provider and `EnvKit` facade
auto-register. Publish the config if you want to tune it:

```bash
php artisan vendor:publish --tag=env-kit-config
```

See **[docs/installation.md](docs/installation.md)** for details.

## The three faces

**Programmatic** — Facade, DI of `EnvKitInterface`, or the `env_kit()` helper:

```php
EnvKit::set('FEATURE_X', 'true');
EnvKit::transaction(fn ($s) => $s->set('A', '1')->set('B', '2')); // one commit
$value = env_kit('APP_NAME', 'Laravel');
```

Migrating off `jackiedo/dotenv-editor`? EnvKit exposes the jackiedo-named aliases
(`getValue`/`setKey`/`deleteKey`/…) and a drop-in `Compat\DotenvEditor` facade, so
existing call sites move over with a class swap — see
**[Programmatic API](docs/tools/programmatic-api.md)**.

**CLI** — 23 commands under `laranail::env-kit-headless.*`, each with a short `env:*` alias:

```bash
php artisan env:set MAIL_HOST=smtp.acme.test
php artisan env:get APP_NAME
php artisan env:doctor          # health-check rules
php artisan env:export --format=json --output=env.json   # also csv / dotenv / yaml
```

**TUI** — an interactive editor on `laravel/prompts`:

```bash
php artisan env:edit
```

## Configuration

```php
// config/env-kit.php (excerpt)
'auto_commit'       => true,                       // immediate writes
'auto_backup'       => true,                       // snapshot before each write
'protect_production'=> true,                       // block prod writes unless overridden
'protected_keys'    => ['APP_KEY', 'DB_PASSWORD'], // never writable
'hidden_keys'       => ['*_PASSWORD', '*_SECRET'], // masked in listings
'audit'             => ['enabled' => true],
'encryption'        => ['driver' => 'laravel'],
```

Full reference: **[docs/configuration.md](docs/configuration.md)**.

## <a name="documentation"></a>Documentation

Hosted at [`opensource.simtabi.com/env-kit-headless/docs/`](https://opensource.simtabi.com/env-kit-headless/docs/).
The same pages live under [`docs/`](docs/):

### Guides

- [Installation](docs/installation.md) — requirements, install, publishing config, the `ENV_KIT_PATH` override.
- [Configuration](docs/configuration.md) — every config key, layered key policy, schema precedence.
- [Architecture](docs/architecture.md) — the document model, commit pipeline, atomic writer, security core.
- [Extending](docs/extending.md) — `configure()` DSL, Macroable, `EnvKitManager`, pipeline middleware, custom drivers.
- [Authorization](docs/authorization.md) — the update gate + write observers, the Laravel-ability bridge.
- [Events](docs/events.md) — the lifecycle event table, actor attribution, listening.
- [Notifications](docs/notifications.md) — opt-in operator alerts, channels, testing.
- [Release](docs/release.md) — versioning, the release workflow, trusted publishing.

### Reference

- [Programmatic API](docs/tools/programmatic-api.md) — reads, typed getters, the three write modes, schema, secret generators, `EnvKit::fake()`.
- [CLI](docs/tools/cli.md) — all 23 commands, exit-code contract, `--file` / `--force-production`.
- [Schema](docs/tools/schema.md) — declarative validation, the rule set, `MatchesEnvSchema` for FormRequests.
- [TUI](docs/tools/tui.md) — the interactive `env:edit` editor.
- [Encryption](docs/tools/encryption.md) — per-value encryption-at-rest, cipher drivers.
- [Doctor](docs/tools/doctor.md) — health-check rules and writing your own.
- [Import / export](docs/tools/import-export.md) — the Porter, JSON/CSV/dotenv/YAML formats, custom formats.
- [Audit & events](docs/tools/audit-events.md) — audit sinks, the `AfterWrite` event, redaction.

### Project

- [Changelog](CHANGELOG.md) — release history.

## Stability

Pre-1.0 (0.x) — the public API may change between minor versions. Pin a version before bumping.

## Local development

```bash
vendor/bin/pest
vendor/bin/phpstan analyse   # level 9
vendor/bin/pint
```

## Sister packages

- [`laranail/env-kit-webui`](https://github.com/laranail/env-kit-webui) — the web companion (JSON API + themed panel) that drives this engine.
- [`laranail/console`](https://github.com/laranail/console) — the CLI/TUI base.

## Community

- [Issues](https://github.com/laranail/env-kit-headless/issues) — bugs + feature requests.

## Contributing & security

EnvKit handles secrets — secret-shaped values never reach logs, exceptions, audit records, or events.

- [CONTRIBUTING.md](CONTRIBUTING.md) — workflow + coding standards.
- [SECURITY.md](SECURITY.md) — report vulnerabilities privately to `opensource@simtabi.com`.

## License

MIT © Simtabi LLC. See [LICENSE](LICENSE).
