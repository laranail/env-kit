# Doctor

A health-check engine that inspects the `.env` for common problems.

```bash
php artisan env:doctor
```

Exits `0` when there are no error-severity findings, `3` otherwise — so it fits CI
gates. Warnings and info notes do not fail the command.

## Built-in rules

| Rule | Severity | Flags |
|------|----------|-------|
| `DuplicateKeys` | error | A key defined more than once (the later silently wins). |
| `BlankValue` | info | A key with an empty value. |
| `ByteOrderMark` | warning | A leading UTF-8 BOM (some parsers mishandle it). |
| `MissingTrailingNewline` | warning | The file does not end with a newline. |

## Programmatic use

```php
foreach (EnvKit::inspect() as $diagnostic) {
    // $diagnostic->severity, ->message, ->key
}
```

## Custom rules

Implement `DoctorRuleInterface` and register it — it runs after the built-ins:

```php
use Simtabi\Laranail\EnvKit\Headless\Contracts\DoctorRuleInterface;
use Simtabi\Laranail\EnvKit\Headless\Doctor\Diagnostic;
use Simtabi\Laranail\EnvKit\Headless\Document\EnvDocument;

final class RequireAppUrl implements DoctorRuleInterface
{
    public function check(EnvDocument $document): array
    {
        return $document->has('APP_URL')
            ? []
            : [Diagnostic::error('APP_URL is required.', 'APP_URL')];
    }
}

EnvKit::configure()->registerDoctorRule(new RequireAppUrl);
```

---

[← Docs index](../../README.md#documentation)
