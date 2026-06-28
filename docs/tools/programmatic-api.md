# Programmatic API

Edit `.env` from a controller, job, or service. Three entry points resolve the
same engine:

```php
use Simtabi\Laranail\EnvKit\Headless\Facades\EnvKit;            // facade
use Simtabi\Laranail\EnvKit\Headless\Contracts\EnvKitInterface; // DI
env_kit('APP_NAME');                                            // helper
```

```php
public function __construct(private readonly EnvKitInterface $env) {}
```

## Reads

```php
EnvKit::get('APP_NAME', 'default');     // raw string (or default)
EnvKit::has('MAIL_HOST');               // bool
EnvKit::missing('NOPE');                // bool
EnvKit::all();                          // array<string, string>
EnvKit::keys();                         // list<string>
EnvKit::only(['A', 'B']);               // subset
EnvKit::except(['SECRET']);             // complement
EnvKit::group('MAIL');                  // every MAIL_* key
EnvKit::interpolated('DB_DSN');         // ${VAR} resolved
EnvKit::raw();                          // the file as a string
EnvKit::entries();                      // Collection of Setter metadata
```

### Typed getters

```php
EnvKit::getString('APP_NAME');          // ?string
EnvKit::getBool('APP_DEBUG', false);    // true/1/yes/on → true
EnvKit::getInt('PORT', 8080);           // ?int
EnvKit::getFloat('RATE');               // ?float
EnvKit::getArray('HOSTS');              // JSON array or comma list → array
EnvKit::getJson('FLAGS');               // decoded JSON
```

## Writes

```php
EnvKit::set('MAIL_HOST', 'smtp.acme.test');
EnvKit::set('PATH_BIN', '/usr/bin', ['export' => true]);  // export PATH_BIN=...
EnvKit::forget('OLD_KEY');
EnvKit::rename('OLD', 'NEW');
EnvKit::setMany(['A' => '1', 'B' => '2']);
```

## Three persistence modes

Gated by `config('env-kit.auto_commit')`:

**Immediate** (default) — each call is its own atomic commit:

```php
EnvKit::set('A', '1');   // committed now
```

**Transaction** — batch many mutations into one commit:

```php
EnvKit::transaction(function ($session) {
    $session->set('A', '1')->set('B', '2')->forget('C');
}); // one atomic write
```

**Staged session** — drive and save manually:

```php
$session = EnvKit::open();
$session->set('A', '1');
if ($session->isDirty()) {
    $session->preview();   // diff without writing
    $session->save();      // commit (or ->discard())
}
```

## Guards, backups, encryption

```php
EnvKit::allowProduction()->set('MAINTENANCE', 'true'); // opt past the prod guard
$backup = EnvKit::backup();                            // snapshot
EnvKit::restore($backup->name);                        // roll back

EnvKit::setEncrypted('STRIPE_SECRET', $plaintext);     // stored encrypted
EnvKit::getDecrypted('STRIPE_SECRET');                 // → plaintext
```

## Diagnostics & transfer

```php
EnvKit::inspect();                 // list<Diagnostic> (doctor rules)
EnvKit::diff(base_path('.env.example'));   // only_here / only_there / changed
EnvKit::export('json');            // serialize
EnvKit::import($json, 'json');     // apply (through the commit pipeline)
```

## Targeting another file

```php
EnvKit::file(base_path('.env.staging'))->set('APP_ENV', 'staging');
EnvKit::on('testing')->get('DB_DATABASE');  // .env.testing
```

## Testing

```php
$fake = EnvKit::fake(['APP_NAME' => 'Acme']);   // in-memory, no disk
$service->run();
$fake->assertSet('NEW_KEY');
$fake->assertForgotten('OLD_KEY');
```

---

[← Docs index](../../README.md#documentation)
