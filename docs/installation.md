# Installation

## Requirements

- **PHP** 8.4.1 or newer (8.4 / 8.5)
- **Laravel** 13
- ext-mbstring

## Install

```bash
composer require laranail/env-kit
```

The package ships a Laravel auto-discovery manifest, so the
`EnvKitServiceProvider` and the `EnvKit` facade register automatically — no
manual provider or alias wiring.

## Publish the configuration (optional)

The package works with sensible defaults out of the box. Publish the config only
if you want to change them:

```bash
php artisan vendor:publish --tag=env-kit-config
```

This writes `config/env-kit.php`. See [Configuration](configuration.md) for every
key.

## Which file does it edit?

By default EnvKit operates on your application's `.env` (`base_path('.env')`).
Override it per environment with the `ENV_KIT_PATH` variable or the config `path`
key, or per call:

```php
EnvKit::file(base_path('.env.staging'))->set('APP_ENV', 'staging');
EnvKit::on('testing')->get('DB_DATABASE'); // → .env.testing alongside the base file
```

## Verify

```bash
php artisan env:doctor   # runs health checks against the current .env
php artisan env:keys     # lists every key
```

---

[← Docs index](../README.md#documentation)
