# laranail/env-kit

[![Tests](https://github.com/laranail/env-kit/actions/workflows/ci.yml/badge.svg)](https://github.com/laranail/env-kit/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

`laranail/env-kit` is not published to Packagist, so there is no registry-version badge to show: see [Install](#install).

> A view-less Laravel engine for reading and **safely editing** `.env` files — one transactional, atomic, guarded, audited commit path behind a programmatic API, a CLI, and an interactive TUI.

PHP `^8.4.1` on Laravel `^13`. It is the engine of the **EnvKit** family; the [`env-kit-webui`](https://opensource.simtabi.com/documentation/laranail/env-kit-webui/) companion drives it for the web.

## Install

```bash
composer require laranail/env-kit
```

```php
use Simtabi\Laranail\EnvKit\Headless\Facades\EnvKit;

EnvKit::set('MAIL_HOST', 'smtp.acme.test');   // atomic · backed-up · audited
$debug = EnvKit::getBool('APP_DEBUG', false);  // typed read
```

## Quick start guide and usage

### Getting started

The service provider and facade register themselves through package discovery, and EnvKit edits
your application's `.env` out of the box. Optionally:

1. Publish the config to change the defaults (writes `config/laranail/env-kit.php`):
   `php artisan vendor:publish --tag=laranail::env-kit-config`.
2. Point it at another file with the `ENV_KIT_PATH` variable.
3. Verify against the current `.env`: `php artisan laranail::env-kit.doctor`.

### Usage

```php
use Simtabi\Laranail\EnvKit\Headless\Facades\EnvKit;

EnvKit::set('MAIL_HOST', 'smtp.acme.test');    // atomic, backed up, audited

EnvKit::get('MAIL_HOST');                      // "smtp.acme.test"
EnvKit::getBool('APP_DEBUG', false);           // true / 1 / yes / on → true
```

Target another file per call:

```php
EnvKit::file(base_path('.env.staging'))->set('APP_ENV', 'staging');
EnvKit::on('testing')->get('DB_DATABASE'); // → .env.testing alongside the base file
```

The full walkthrough is in [Programmatic API](docs/tools/programmatic-api.md); everything else is in the [documentation index](#documentation).

## <a name="documentation"></a>Documentation

Full documentation is at **[opensource.simtabi.com/documentation/laranail/env-kit](https://opensource.simtabi.com/documentation/laranail/env-kit/)** — format-preserving atomic writes, secret redaction + encryption-at-rest, schema validation, the guard/protection policy, the CLI, the interactive TUI, and configuration.

## Contributing & security

Issues and PRs are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities per
[SECURITY.md](SECURITY.md) (opensource@simtabi.com); participation follows the [Code of Conduct](CODE_OF_CONDUCT.md).

## License

MIT © Simtabi LLC. See [LICENSE](LICENSE).
