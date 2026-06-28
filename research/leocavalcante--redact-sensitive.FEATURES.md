# FEATURES — leocavalcante/redact-sensitive
Source: https://github.com/leocavalcante/redact-sensitive · v0.4.1 · MIT · group E/F reference

## What it is / entry
library API. A single-class Monolog **processor** that redacts sensitive values
in a log record's `context` array before they are written. Entry point is the
invokable `RedactSensitive\RedactSensitiveProcessor`, registered via
`$logger->pushProcessor($processor)`. Not a Laravel package — pure Monolog v3.

## Public API or plugin surface (verified signatures)
- `RedactSensitive\RedactSensitiveProcessor implements Monolog\Processor\ProcessorInterface`
- Constructor:
  ```php
  public function __construct(
      array $sensitiveKeys,
      string $replacement = self::DEFAULT_REPLACEMENT, // '*'
      string $template = '%s',
      ?int $lengthLimit = null
  )
  ```
- `public const DEFAULT_REPLACEMENT = '*';`
- `public function __invoke(LogRecord $record): LogRecord` — redacts `$record->context`
  and returns `$record->with(context: $redactedContext)`.
- Private helpers (the actual mining surface): `redact(string $value, int $length): string`,
  `traverse()`, `traverseArr()`, `traverseObj()` — recursive descent over nested
  arrays AND objects (via `get_object_vars`, mutating object props in place).
- `$sensitiveKeys` is a **map of key-name → visible-char count** (an int), and may
  nest: a value that is itself an array re-keys into the nested structure
  (e.g. `['nested' => ['arr' => ['value' => 3, 'or_obj' => ['secret' => -3]]]]`).

## Artisan commands (if any)
- none

## Config keys
- N/A (library). Masking is configured entirely through constructor args:
  - `sensitiveKeys`: `['api_key' => 4]` → keep first 4 chars visible.
    `['api_key' => -4]` → keep **last** 4 chars visible. `['key' => 0]` → fully hidden.
    Nested arrays descend into nested context structures.
  - `replacement`: the single mask character repeated (default `'*'`).
  - `template`: `sprintf` template applied to the masked run, default `'%s'`.
    e.g. `'%s(redacted)'` appends a tag; `'...'` discards the masked chars entirely
    (template need not reference `%s`).
  - `lengthLimit`: optional truncation of the final redacted string.

## Patterns to mine
- redaction masking — **length-preserving partial masking** (NOT a fixed string,
  NOT char-count-blind). Mechanism, from `redact()`:
  ```php
  $valueLength  = strlen($value);
  $hiddenLength = $valueLength - abs($length);
  $hidden       = str_repeat($this->replacement, $hiddenLength);
  $placeholder  = sprintf($this->template, $hidden);
  $result       = substr_replace($value, $placeholder, max(0, $length), $hiddenLength);
  return $length > 0
      ? substr($result, 0, $this->lengthLimit)   // keep first N, mask trailing
      : substr($result, -$this->lengthLimit);     // keep last N, mask leading
  ```
  - The mask is `(strlen(value) - |N|)` copies of `replacement`, so by default the
    **output length equals the input length** — the number of `*` reveals the
    original value's length. `'mysupersecretapikey'` with `=> 4` →
    `mysu***************` (4 visible + 15 stars = 19 chars, same as input).
  - Positive `N` keeps the first N chars and masks the tail; negative `N` keeps the
    last |N| chars and masks the head (`4111111145551142` with `-4` → `************1142`).
  - `N = 0` masks everything (full-length run of stars).
  - Empty string returns unchanged (early `return $value`).
  - `template` can change/extend the placeholder or collapse it (`'...'` ⇒ no length
    leak); `lengthLimit` then truncates to fixed width (`access_token => 0`,
    `lengthLimit: 5` → `*****`).
  - Edge note: when `lengthLimit` is `null`, `substr($result, 0, null)` returns `''`
    in older PHP semantics, but here the positive branch passes `null` as length —
    relies on `substr`'s null-as-no-limit behavior on PHP 8.1+. Negative-`N` with
    null limit uses `substr($result, -null)` ⇒ `-0` ⇒ full string.

## Dependencies
- runtime: `php >=8.1`, `monolog/monolog ^3.0`
- dev: `pestphp/pest ^2.4`

## Tests
Y — Pest. `tests/RedactSensitiveTest.php`, `tests/MonologUsageTest.php`,
`tests/Pest.php`, `tests/TestCase.php`; config `phpunit.xml`. Runnable examples in
`examples/` (`00_hello_world.php`, `01_nested.php`, `02_completely_hidden.php`,
`03_right_to_left.php`).

## Notes / corrections
- Only `$record->context` is redacted; the log **message** and `extra` are untouched —
  secrets interpolated into the message string are NOT caught.
- Redaction keys match by **exact array/object key name**, no wildcard/regex/path
  matching; matching is case-sensitive.
- Non-scalar, non-array, non-object scalar fallthrough: traversing a leaf that isn't
  array/object throws `UnexpectedValueException` (only reached via mis-shaped nested
  key maps).
- Objects are mutated in place (`get_object_vars` + property assignment), so the
  original context object is modified, not cloned.
- Namespace is `RedactSensitive\` (PSR-4 → `src/`); single class, no interfaces/traits
  to extend.
