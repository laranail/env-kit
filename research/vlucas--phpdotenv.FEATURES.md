# FEATURES — vlucas/phpdotenv
Source: github.com/vlucas/phpdotenv · v5.6.x (composer branch-alias `dev-master` → `5.6-dev`; no git tags in this checkout) · BSD-3-Clause · group E/F reference

## What it is / entry
library API. Loads `.env` files into `getenv()`/`$_ENV`/`$_SERVER` (or an array-backed repository), with a parser, a nested-variable resolver, and a fluent validation API. No CLI, no Laravel service provider — pure library. Primary entry class is `Dotenv\Dotenv` (static factories), values flow Store → Parser → Loader → Repository; validation reads back from the Repository via `Dotenv\Validator`.

## Public API or plugin surface (verified signatures)
Library — methods we'd mine.

**`Dotenv\Dotenv` factories** (all `static`, signature `($paths, $names = null, bool $shortCircuit = true, ?string $fileEncoding = null): Dotenv`):
- `createMutable(...)` — default adapters (Env/Server const), mutable repo.
- `createUnsafeMutable(...)` — adds `PutenvAdapter` (writes via `putenv()`).
- `createImmutable(...)` — default adapters, `immutable()` (won't overwrite existing env).
- `createUnsafeImmutable(...)` — immutable + `PutenvAdapter`.
- `createArrayBacked(...)` — `ArrayAdapter` only (no global env mutation).
- `create(RepositoryInterface $repository, $paths, $names = null, bool $shortCircuit = true, ?string $fileEncoding = null): Dotenv` — low-level, bring-your-own repository.
- `parse(string $content): array<string,string|null>` — static; parse+resolve a string with an array repo, no env mutation.

**`Dotenv\Dotenv` instance methods:**
- `load(): array<string,string|null>` — parse store, load into repo, return loaded vars.
- `safeLoad(): array<string,string|null>` — `load()` swallowing `InvalidPathException`.
- `required(string|string[] $variables): Validator` — returns `(new Validator($repo,(array)$variables))->required()` (i.e. already asserts presence).
- `ifPresent(string|string[] $variables): Validator` — returns a fresh `Validator` WITHOUT asserting presence (assertions skip null values).

**`Dotenv\Validator`** (constructed with `RepositoryInterface $repository, string[] $variables`; every assertion returns `$this` → chainable, throws `Dotenv\Exception\ValidationException` on failure):
- `required(): Validator` — value `!== null` (uses raw `assert`, NOT null-skipping).
- `notEmpty(): Validator` — `Str::len(trim($value)) > 0` (null-skipping).
- `isInteger(): Validator` — `ctype_digit($value)` (null-skipping).
- `isBoolean(): Validator` — `filter_var(..., FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null`; empty string fails (null-skipping; needs `ext-filter`).
- `allowedValues(array $choices): Validator` — `in_array($value, $choices, true)` (null-skipping). *Param is `$choices`, not `$set`.*
- `allowedRegexValues(string $regex): Validator` — `Regex::matches($regex,$value)` truthy (null-skipping).
- `assert(callable(?string):bool $callback, string $message): Validator` — public; runs callback over every var, collects failures.
- `assertNullable(callable(string):bool $callback, string $message): Validator` — public; wraps `assert`, returns true (skips) when value is null.

## Artisan commands (if any)
- none

## Config keys
- N/A. Behaviour is configured by factory arguments (`$paths`, `$names`, `$shortCircuit`, `$fileEncoding`) and `RepositoryBuilder`/`StoreBuilder` (adapters, immutability, default name `.env`), not config files.

## Patterns to mine
- **Validation chain mechanism.** `Dotenv::required()` / `Dotenv::ifPresent()` are the entry points that mint a `Validator` bound to a variable set + the repository; each assertion method calls the shared private `assert()` which iterates the variables, reads each via `$repository->get($name)`, accumulates `"<NAME> <message>"` strings, and throws a single `ValidationException` listing all failures ("One or more environment variables failed assertions: …"). Two assertion flavours: `assert` (sees null) vs `assertNullable` (null ⇒ pass), so `ifPresent()` chains naturally short-circuit on absent vars. `required()` is the only assertion built on raw `assert`. Validators are reusable/chainable (each returns `$this`).
- **Value parse model (for round-trip write target).** Parsing is a hand-rolled FSM/transducer, NOT regex-replace:
  - `Parser::parse()` splits content on `\r\n|\n|\r`, `Lines::process()` stitches multi-line/quoted entries, then `EntryParser::parse()` handles each raw entry.
  - `EntryParser::splitStringIntoParts()` splits on the FIRST `=` (`explode('=', $line, 2)`), `trim`s both sides; no `=` ⇒ value is `null` (var present-but-unset, later `clear`ed). Empty LHS ⇒ error.
  - Name: optional leading `export ` stripped; optional surrounding matching quotes stripped; validated against `~(*UTF8)\A[\p{Ll}\p{Lu}\p{M}\p{N}_.]+\z~` (letters/marks/numbers/`_`/`.`).
  - Value: lexed by `Lexer` into tokens via an anchored alternation of patterns (newlines, non-newline whitespace, `\`, `'`, `"`, `#`, `$`, and runs of "other"), then reduced through a 7-state machine: INITIAL, UNQUOTED, SINGLE_QUOTED, DOUBLE_QUOTED, ESCAPE_SEQUENCE, WHITESPACE, COMMENT.
    - **Single quotes**: literal — nothing interpolated or unescaped until the closing `'`.
    - **Double quotes**: `\` enters ESCAPE state; only `\"`, `\\`, `\$` and `\f \n \r \t \v` (via `stripcslashes`) are valid escapes, anything else ⇒ "unexpected escape sequence" error; `$` marks an interpolation point.
    - **Unquoted**: a `#` or whitespace ends the value (whitespace ⇒ trailing chars must be space-only or another value-start is rejected); `$` marks interpolation.
    - **Comments**: `#` outside quotes switches to COMMENT state, rest of line discarded.
    - Unterminated single/double quote or dangling escape ⇒ REJECT_STATES ⇒ "a missing closing quote" error.
  - **Variable interpolation `${VAR}`**: the parser does NOT resolve — it only records integer offsets of `$` markers (`Value::getVars()`, sorted descending). Resolution happens later in `Dotenv\Loader\Resolver::resolve()`, which at each recorded offset runs `/\A\${([a-zA-Z0-9_.]+)}/` against the repository; unknown vars are left as the literal `${VAR}` text. Note interpolation only fires for `$` seen in INITIAL/UNQUOTED/DOUBLE_QUOTED states (not single-quoted), and only the `${...}` brace form is supported (no bare `$VAR`).
  - For a round-trip writer: to reproduce a value exactly you must re-quote based on content — single-quote to emit literally, double-quote when you need escapes/interpolation, and escape `"`, `\`, `$` plus control chars inside double quotes; bare unquoted values must avoid whitespace, `#`, `$`, quotes.

## Dependencies
- runtime: `php ^7.2.5 || ^8.0`, `ext-pcre`, `graham-campbell/result-type ^1.1.4` (Result monad threaded through parser), `phpoption/phpoption ^1.9.5` (Option), `symfony/polyfill-ctype`, `symfony/polyfill-mbstring`, `symfony/polyfill-php80`.
- suggest: `ext-filter` (required for `isBoolean()`).
- dev: `phpunit ^8.5 || ^9.6 || ^10.4`, `bamarni/composer-bin-plugin`, `ext-filter`.

## Tests
Y — `tests/Dotenv/` (PHPUnit suites incl. parser/validator) plus `tests/fixtures/` (`.env` sample files).

## Notes / corrections
- **`ifPresent()` is a `Dotenv` method, not a `Validator` method.** It lives on `Dotenv\Dotenv` and returns a bare `Validator`; the prompt listed it alongside the Validator assertions. The Validator itself has no `ifPresent()`.
- **`required()` exists on BOTH classes**: `Dotenv::required($variables)` (entry point, returns an already-asserted Validator) and `Validator::required()` (the presence assertion). Same name, different layers.
- **`allowedValues` param name is `$choices`** (prompt wrote `$set`). Signature is `allowedValues(array $choices)`.
- `Dotenv::required(...)` returns a `\Dotenv\Validator` — confirmed (line 253: `(new Validator($this->repository, (array) $variables))->required()`).
- `isBoolean()` accepts the `filter_var` boolean set (`1/0/true/false/on/off/yes/no`) and rejects empty string; depends on `ext-filter`.
- License: **BSD-3-Clause confirmed** — `composer.json` `"license": "BSD-3-Clause"` and `LICENSE` header "BSD 3-Clause License", copyright Graham Campbell (2014) / Vance Lucas (2013).
- Version: no git tags present in this checkout (`git describe` fails); inferred **5.6.x** from `extra.branch-alias.dev-master = 5.6-dev` and the presence of `allowedRegexValues`/`createUnsafe*` (5.5+/5.x API).
