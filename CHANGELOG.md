# Changelog

All notable changes to `laranail/env-kit` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2026-07-11

### Fixed

- **The shipped configuration was inert. Every value fell back to its hardcoded default.**
  The provider registers the block at `laranail.env-kit` (`->name('laranail/env-kit')` plus
  `->hasConfigFile('env-kit')`), but all sixteen read sites asked for the bare `env-kit.*` — including
  the whole-block `$config->get('env-kit', [])` that constructs the `EnvKit` singleton. In an
  application that returned an empty array, so `config/env-kit.php` had no effect on anything.

  Nothing errored, which is why it survived: each read carries its own fallback, and the test suite
  set the bare keys itself, creating the very tree the package was reading.

  Three of the defaults are not cosmetic:

  | Setting | Shipped value | What was actually in effect |
  |---|---|---|
  | `protected_keys` | `['APP_KEY', 'DB_PASSWORD']` — documented *never writable* | `[]` — both writable through the CLI, TUI and WebUI |
  | `hidden_keys` | `['APP_KEY', '*_PASSWORD', '*_SECRET', '*_TOKEN']` | `[]` — `SecretRedactor` masked nothing, so those secrets appeared in listings and in the audit log |
  | `limits.max_value_length` | `32768` | `null` — unbounded, defeating the documented write-size defence |

  `schema` was never seeded and `encryption.driver` was never read, so a consumer-registered cipher
  (`EnvKitManager::extend('vault', …)`) could be selected in config and silently never used.

  **Breaking for anyone who worked around this by setting the bare key.** Configuration now reads
  from `laranail.env-kit.*`; publish the config and move any `config/env-kit.php` overrides under the
  scoped key. Two names are deliberately unchanged because they are different registries, not config:
  the container tags (`env-kit.doctor_rules`, `env-kit.port_formats`, `env-kit.audit_sinks`,
  `env-kit.observers`) and the `env-kit.update` gate ability.

### Added

- `tests/Feature/ConfigContractTest.php` — asserts the package reads configuration at the key it
  registers, that every shipped key resolves, that a shipped protected key is genuinely refused, and
  that no source file reads a bare `env-kit.*` config key again.

Initial public release.
