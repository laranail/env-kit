# FEATURES — dacoto/laravel-env-set
Source: https://github.com/dacoto/laravel-env-set · version not declared in composer.json (git-tag driven releases) · MIT · group A (programmatic)

## Invocation
- **Facade FQCN:** `dacoto\EnvSet\Facades\EnvSet` (accessor returns `dacoto\EnvSet\EnvSetEditor::class`).
- **Alias:** `EnvSet` (registered via `extra.laravel.aliases` for package auto-discovery).
- **Service / main class:** `dacoto\EnvSet\EnvSetEditor` — bound in the container by `dacoto\EnvSet\EnvSetServiceProvider` (`$this->app->bind(EnvSet::class, fn($app) => new EnvSetEditor($app))`). Note the bind key is the **facade** class, not the editor class — so the facade resolves, and constructor injection works because `EnvSetEditor` is concrete/autowirable.
- **Global helper:** none.
- **Auto-discovery:** yes — provider + alias declared under `extra.laravel` in composer.json.
- **Constructor:** `__construct(Illuminate\Contracts\Container\Container $app)` — immediately calls `load()`, so the instance reads `.env` on resolution.

## Public API (verified signatures)
All exact signatures from `src/EnvSetEditor.php`. Setter/mutator methods write only to an in-memory buffer; nothing touches disk until `save()`.

- `load(string $filePath = null): self` — (re)point editor at a file and read it into the reader/writer buffer; resolves path from `$app->environmentPath()/environmentFile()` when `$filePath` is null, else a relative `../../../../../../.env` fallback — none (read only)
- `getContent(): string` — return raw file/buffer content from the reader — none
- `getLines(): array` — return all parsed lines (line number, raw + parsed type/key/value/comment) — none
- `getValue(string $key, mixed $default = null): mixed` — return a key's value; returns `$default` if provided and key missing, else throws `KeyNotFoundException` — none
- `getKeys(array $keys = []): array` — return all keys, or only the given subset; each entry has line/key/value/comment/export — none
- `addEmpty(): self` — append an empty line to the buffer — buffer only (needs `save()`)
- `addComment(string $comment): self` — append a `# comment` line to the buffer — buffer only (needs `save()`)
- `setKey(string $key, string $value = null, string $comment = null, bool $export = false): self` — add or update one key in the buffer (delegates to `setKeys`) — buffer only (needs `save()`)
- `setKeys(array $data): self` — add/update many keys; accepts array-of-arrays (`key/value/comment/export`) or associative `key => value`; appends if file/key absent, else updates in place preserving prior comment when none passed — buffer only (needs `save()`)
- `keyExists(string $key): bool` — true if key present in current content — none
- `deleteKey(string $key): self` — remove one key from the buffer (delegates to `deleteKeys`) — buffer only (needs `save()`)
- `deleteKeys(array $keys = []): self` — remove many keys from the buffer — buffer only (needs `save()`)
- `save(): self` — write the buffer to the file via `file_put_contents` (throws `UnableWriteToFileException` if not writable) — **explicit persist**

`protected resetContent(): void` exists but is non-public (clears path + reader/writer buffers; called by `load`).

## Artisan commands
- none. No `src/Commands/` directory, no console command classes, and the service provider registers no commands.

## Config keys
- none. There is no published config file and no `mergeConfigFrom`/`publishes` calls; the file path is derived at runtime from Laravel's `environmentPath()`/`environmentFile()` (overridable per call via `load($filePath)`).

## Dependencies (composer require)
- `php`: `^8.2`
- `illuminate/support`: `^11.0 | ^12.0 | ^13.0`
- require-dev: `orchestra/testbench` `^9.0 | ^10.0 | ^11.0`, `phpunit/phpunit` `^10.4 | ^11.5 | ^12.0`

## Persistence model
**buffer + explicit `save()`.** All mutators (`setKey`, `setKeys`, `deleteKey`, `deleteKeys`, `addEmpty`, `addComment`) mutate an in-memory string buffer held by `Workers\Writer`. Disk is only touched by `save()`, which runs a writability check then `file_put_contents($filePath, $buffer)`. No auto-save and no `$write`/hybrid flag. (Readers `getValue`/`getKeys`/`keyExists` read current file content via `Workers\Reader`, independent of unsaved buffer edits.)

## Unique vs jackiedo base
This package is a slimmed-down, modernized fork of the classic jackiedo/dotenv-editor lineage. Key differences observed in source:
- **No Artisan commands at all** — jackiedo ships `dotenv:*` commands; here the surface is facade-only.
- **No backup/restore/auto-backup feature set** — there are no `backup()`, `restore()`, `getBackups()`, `deleteBackups()`, `keep` methods; persistence is a single `save()`.
- **No config file** — jackiedo publishes `dotenv-editor.php` (backup path, auto-backup, etc.); this fork has zero config.
- **Slim public API** — 14 public methods total, focused on read + buffered write + save.
- **`setKeys` accepts an associative `key => value` shorthand** in addition to the array-of-setters form (see `setKeys` loop branch for non-array `$setter` with string key).
- **`getValue` supports a `$default`** to avoid the not-found exception (jackiedo's classic `getValue` throws).
- Modern stack: `declare(strict_types=1)`, typed properties/signatures, PHP `^8.2`, Laravel 11/12/13.

## Tests
Y — `tests/Unit/EnvSetEditorTest.php` (PHPUnit via `orchestra/testbench`; `tests/TestCase.php` + `tests/stubs/env` fixture, `phpunit.xml` config). 10 test methods exercising the facade: getKeys, get/set value, create key, delete one/multiple, keyExists, KeyNotFoundException, default-value fallback, addComment, addEmpty. CI workflow at `.github/workflows/ci.yml`.

## Notes / corrections to the plan
- The facade accessor returns `EnvSetEditor::class`, but the provider binds `EnvSet::class` (the facade) to the editor instance. Resolution works because facade accessor + the bind key are wired together; constructor-injecting `EnvSetEditor` also works (concrete autowire). Worth noting the slightly unusual binding key.
- `getValue`'s `$default` is only returned when `$default !== null`; passing `null` (or omitting it) for a missing key throws `KeyNotFoundException`. Tests confirm `''` and `false` defaults are honored.
- `setKey` signature uses implicit-nullable `string $value = null` (and `$comment = null`) — deprecated implicit-nullable param style on PHP 8.4+, but functionally fine.
- Constructor calls `load()` eagerly; the `__construct` is documented `@throws UnableReadFileException`. Resolving the facade reads `.env` immediately.
- The non-Laravel fallback path is a hardcoded relative `__DIR__ . '/../../../../../../.env'` (assumes vendor install depth).
- No `version` field in composer.json — releases are git-tag driven; report version from the chosen tag/release rather than the manifest.
