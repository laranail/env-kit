# Contributing

Thanks for your interest in improving `laranail/env-kit-headless`. This guide
covers everything you need to get a change merged.

## Requirements

- **PHP 8.4.1+** and **Laravel 13**.
- Install dependencies:

  ```bash
  composer install
  ```

## Quality gates

Run all three locally before opening a pull request. CI runs the same checks and
they must pass.

```bash
vendor/bin/pest                 # tests
vendor/bin/phpstan analyse      # static analysis, PHPStan level 9
vendor/bin/pint                 # code style, Laravel preset + strict types
```

## Conventions

- **Strict types** in every file: `declare(strict_types=1);` at the top.
- **Tests** use **Pest 4**. Feature tests run against **Testbench**; unit tests
  exercise the engine in isolation. Cover new behavior and edge cases.
- The **engine core is pure OOP and DI-only**: it never imports view or HTTP
  code, and never calls `app()` or other service-locator helpers. Inject
  dependencies through constructors and contracts instead.
- **New Artisan commands** extend the namespaced command base and follow the
  `laranail::env-kit-headless.*` naming with an `env:*` alias.
- Keep PRs **small and reviewable** — one focused change per PR.

## Commits

- Conventional, **lowercase, imperative** subject lines (e.g. `add doctor rule
  for empty values`).
- Subject **≤72 characters**; explain the *why* in the body when it helps.
- **No AI attribution** of any kind in commits or PRs.

## Pull requests

1. Branch from `main`.
2. Make your change with accompanying tests.
3. Ensure the three quality gates pass.
4. Open a PR with a clear description of what changed and why.

We review promptly and appreciate your contribution.
