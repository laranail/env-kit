# FEATURES — jackiedo/dotenv-editor
Source: https://github.com/jackiedo/dotenv-editor · 2.x (composer branch-alias `dev-master` = `2.x-dev`; README documents the 2.x line) · MIT · group A (programmatic)

## Invocation
- Facade FQCN: `Jackiedo\DotenvEditor\Facades\DotenvEditor` (auto-aliased as `DotenvEditor`).
- Service / concrete class: `Jackiedo\DotenvEditor\DotenvEditor`.
- Container binding: `app('dotenv-editor')` (bound in `DotenvEditorServiceProvider::register()`), or constructor injection of `Jackiedo\DotenvEditor\DotenvEditor`.
- Global helper: none.
- Auto-discovery: yes — `extra.laravel.providers` registers `DotenvEditorServiceProvider`, `extra.laravel.aliases` registers the `DotenvEditor` facade. Provider is a `DeferrableProvider`.

## Public API (verified signatures)
Every method below is `public` on `Jackiedo\DotenvEditor\DotenvEditor`. Most writers/mutators are fluent (return `$this`, i.e. `DotenvEditor`) for chaining. PHPDoc `@return DotenvEditor` is used (no native return type declarations in source).

Loading:
- `__construct(Container $app, Config $config): void` — wires reader/writer, picks a phpdotenv-compatible parser, configures backups, then calls `load()` — none
- `load(?string $filePath = null, bool $restoreIfNotFound = false, ?string $restorePath = null): DotenvEditor` — re-inits state and loads a file into the reader/buffer; defaults to the app's env file path; optionally restores if missing — none (read into buffer)

Reading:
- `getContent(): string` — raw file content from the reader — none
- `getEntries(bool $withParsedData = false): array` — all line entries; optionally include parsed_data — none
- `getKeys(array $keys = []): array` — all setter key info, or only the given keys (assoc keyed by key name) — none
- `getKey(string $key): array` — info array for one key; **throws `KeyNotFoundException`** if absent — none
- `getValue(string $key): string` — returns `getKey($key)['value']` — none
- `keyExists(string $key): bool` — whether key is present in file content — none

Writing (buffer mutators — fluent, do NOT touch disk until `save()`):
- `hasChanged(): bool` — whether the buffer differs from last load/save — none
- `getBuffer(bool $asArray = true): array` — current buffer content — none
- `addEmpty(): DotenvEditor` — append blank line to buffer; sets hasChanged — buffer (explicit `save()`)
- `addComment(string $comment): DotenvEditor` — append comment line — buffer (explicit `save()`)
- `setKeys(array $data): DotenvEditor` — append/update many setters; each item `['key'=>,'value'=>,'comment'=>,'export'=>]` (or `key => value` pairs) — buffer (explicit `save()`)
- `setKey(string $key, ?string $value = null, ?string $comment = null, $export = null): DotenvEditor` — append/update one setter (delegates to `setKeys`) — buffer (explicit `save()`)
- `setSetterComment(string $key, ?string $comment = null): DotenvEditor` — set/replace a setter's comment — buffer (explicit `save()`)
- `clearSetterComment(string $key): DotenvEditor` — clear a setter's comment (calls `setSetterComment($key, null)`) — buffer (explicit `save()`)
- `setExportSetter(string $key, bool $state = true): DotenvEditor` — toggle leading `export ` on a setter — buffer (explicit `save()`)
- `deleteKeys(array $keys = []): DotenvEditor` — delete many setters from buffer — buffer (explicit `save()`)
- `deleteKey(string $key): DotenvEditor` — delete one setter (delegates to `deleteKeys`) — buffer (explicit `save()`)
- `save(bool $rebuildBuffer = true): DotenvEditor` — write buffer to file (auto-backs-up first if enabled), optionally rebuild buffer — **persists to disk (explicit save)**

Backups:
- `autoBackup(bool $on = true): DotenvEditor` — enable/disable auto-backup-on-save at runtime — none
- `backup(): DotenvEditor` — copy current file to a timestamped backup; **throws `FileNotFoundException`** if file missing — writes a backup file immediately
- `getBackups(): array` — list available backups (`filename`, `filepath`, `created_at`) — none
- `getLatestBackup(): array|null` — info of newest backup, or `null` if none — none
- `restore(?string $filePath = null): DotenvEditor` — copy a backup (latest, or given path) over the loaded file and rebuild buffer; **throws `NoBackupAvailableException` / `FileNotFoundException`** — writes to disk immediately
- `deleteBackups(array $filePaths = []): DotenvEditor` — delete given backups, or all if empty — deletes files immediately
- `deleteBackup(string $filePath): DotenvEditor` — delete one backup (delegates to `deleteBackups`) — deletes file immediately

(Protected helpers, not part of the public API: `init`, `standardizeFilePath`, `buildBuffer`, `createBackupFolder`, `configBackuping`, `selectCompatibleParser`, `getDotenvPackageVersion`.)

### getValue() verification
Exact source signature (DotenvEditor.php line 239):
```php
public function getValue(string $key)
{
    return $this->getKey($key)['value'];
}
```
CONFIRMED: `getValue()` takes a single `string $key` parameter and has NO second/default argument. There is no `getValue($key, $default)` overload. A missing key does not yield a default — it propagates `KeyNotFoundException` thrown by the internal `getKey()` call.

## Artisan commands
Registered via the service provider (names set with `protected $name`, args/options via `getArguments()`/`getOptions()`):
- `dotenv:set-key {key} {value?} {comment?} [--filepath=] [--restore|-r] [--restore-path=] [--export-key|-e] [--force]` — add new or update one setter in the .env file
- `dotenv:delete-key {key} [--filepath=] [--force]` — delete one setter in the .env file
- `dotenv:get-keys [--filepath=]` — list all setters in the .env file
- `dotenv:get-backups [--latest|-l]` — list all backup versions (or only the latest)
- `dotenv:backup [--filepath=]` — back up the .env file
- `dotenv:restore [--filepath=] [--restore-path=] [--force]` — restore the .env file from a backup or a special file

## Config keys
Config file `dotenv-editor.php` (publish tag `config`):
- `autoBackup` — `true` — back up the original file before each `save()`
- `backupPath` — `base_path('storage/dotenv-editor/backups/')` — directory where backups are stored
- `alwaysCreateBackupFolder` — `false` — create the backup folder on boot even when no backup is taken

## Dependencies (composer require)
- `illuminate/console` `^10.0|^9.0|^8.0|^7.0|^6.0|^5.8`
- `illuminate/contracts` (same constraint)
- `illuminate/support` (same constraint)
- `jackiedo/path-helper` `^1.0`
- (runtime parser selection keys off the host app's `vlucas/phpdotenv` version via `composer.lock`, but it is not a direct require)

## Persistence model
buffer+save(). All read methods hit the reader directly; all `add*/set*/delete*` mutators only touch an in-memory writer buffer and flip `hasChanged`. Nothing is written to the .env file until `save()` is called (no auto-save). On `save()`, if the file exists and auto-backup is on, a timestamped backup is taken first, then the buffer is flushed to disk. The backup operations (`backup`, `restore`, `deleteBackup(s)`) act on disk immediately and independently of `save()`.

## Unique vs jackiedo base
- This IS the jackiedo base — baseline. (Other group-A packages in this research are measured against this surface.)

## Tests
N — no `tests/` directory and no PHPUnit/dev dependency in `composer.json`. Only CI present is `.github/workflows/fix-coding-standards.yml` (php-cs-fixer), not a test suite.

## Notes / corrections to the plan
- `getValue()` is single-arg only — any plan assuming a `getValue($key, $default)` fallback is WRONG; absent keys throw `KeyNotFoundException` via `getKey()`.
- `getKey()` (and therefore `getValue()`) throws on missing keys; use `keyExists()` first to guard.
- Mutators are buffer-only and fluent — chaining works (e.g. `->setKey(...)->addComment(...)->save()`), but a `save()` is mandatory to persist.
- `setKey()`/`setKeys()` is upsert: appends when the key/file is absent, updates in place otherwise; on update, omitted `comment`/`export` preserve the existing values.
- `getLatestBackup()` returns `null` (not `[]`) when there are no backups.
- Parser is auto-selected at construction from the app's installed `vlucas/phpdotenv` version (ParserV1/V2/V3) by reading the app `composer.lock`; this requires running inside a real app with that lock file.
- Backup filenames are `.env.backup_YYYY_MM_DD_HHMMSS` (prefix constant `.env.backup_`, empty suffix).
- `composer.json` declares no explicit `version`; uses `extra.branch-alias.dev-master = 2.x-dev`. Copyright holder in LICENSE is "Anh Vũ Đỗ" (2017), license MIT.
