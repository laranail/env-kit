# laranail/env-kit

[![Latest version on Packagist](https://img.shields.io/packagist/v/laranail/env-kit.svg)](https://packagist.org/packages/laranail/env-kit)
[![Tests](https://github.com/laranail/env-kit/actions/workflows/ci.yml/badge.svg)](https://github.com/laranail/env-kit/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

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

## Documentation

Full documentation is at **[opensource.simtabi.com/documentation/laranail/env-kit](https://opensource.simtabi.com/documentation/laranail/env-kit/)** — format-preserving atomic writes, secret redaction + encryption-at-rest, schema validation, the guard/protection policy, the CLI, the interactive TUI, and configuration.

## Contributing & security

Issues and PRs are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities per
[SECURITY.md](SECURITY.md) (opensource@simtabi.com); participation follows the [Code of Conduct](CODE_OF_CONDUCT.md).

## License

MIT © Simtabi LLC. See [LICENSE](LICENSE).
