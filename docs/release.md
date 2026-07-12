# Release

EnvKit Headless follows [Semantic Versioning](https://semver.org) and a
tag-driven release flow.

## Versioning

- `MAJOR.MINOR.PATCH`. Breaking API changes bump MAJOR; new features bump MINOR;
  fixes bump PATCH.
- The single source of version truth is the git tag (`vX.Y.Z`). No version is
  hard-coded in `composer.json`.

## Cutting a release

1. Update `CHANGELOG.md` — move the `Unreleased` items under a new
   `## [X.Y.Z] - <date>` heading (Keep a Changelog).
2. Ensure the gates are green:
   ```bash
   vendor/bin/pest
   vendor/bin/phpstan analyse
   vendor/bin/pint --test
   ```
3. Tag and push:
   ```bash
   git tag vX.Y.Z && git push origin vX.Y.Z
   ```
4. The `release` workflow extracts the tagged version's CHANGELOG block and uses
   it as the GitHub release body, then Packagist updates via its webhook.

Every release carries a human-readable description sourced from the CHANGELOG —
never a bare "see CHANGELOG" stub.

## Supported versions

See [SECURITY.md](../SECURITY.md) for the supported-version policy and how to
report a vulnerability.

---

[← Docs index](../README.md#documentation)
