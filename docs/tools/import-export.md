# Import / Export

Move env data in and out of the file through the **Porter**. JSON and CSV ship
built-in; the format set is extensible.

## CLI

```bash
php artisan env:export                              # JSON to stdout
php artisan env:export --format=csv --output=env.csv
php artisan env:import env.json                     # JSON (default)
php artisan env:import env.csv --format=csv
```

Imports run through the full commit pipeline — so keys are validated, guards apply,
the write is atomic, and the change is audited.

## Programmatic

```php
$json = EnvKit::export('json');     // pretty-printed object
$csv  = EnvKit::export('csv');      // KEY,VALUE with a header row (RFC-4180)

EnvKit::import($json, 'json');
EnvKit::import($csv, 'csv');
```

## Custom formats

Implement `PortFormatInterface` (`name()`, `export()`, `import()`) and register it:

```php
use Simtabi\Laranail\EnvKit\Headless\Contracts\PortFormatInterface;

final class YamlFormat implements PortFormatInterface
{
    public function name(): string { return 'yaml'; }

    public function export(array $values): string { /* … */ }

    public function import(string $content): array { /* … */ }
}

EnvKit::configure()->registerPortFormat(new YamlFormat);
// php artisan env:export --format=yaml
```

---

[← Docs index](../../README.md#documentation)
