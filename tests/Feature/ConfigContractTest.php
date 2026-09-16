<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\EnvKitManager;
use Simtabi\Laranail\EnvKit\Headless\Tests\TestCase;
use Simtabi\Laranail\EnvKit\Headless\Contracts\ValueCipherInterface;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\ProtectedKeyException;

uses(TestCase::class);

/**
 * Asserts that this package reads its configuration at the key it actually registers.
 *
 * It did not. `->name('laranail/env-kit')` + `->hasConfigFile('env-kit')` register the block at
 * `laranail.env-kit`, but every read site asked for the bare `env-kit.*`, including the whole-block
 * `$config->get('env-kit', [])` that builds the `EnvKit` singleton. In a real application that
 * returned an empty array and **every shipped value silently fell back to its hardcoded default**:
 *
 *  - `protected_keys` (`APP_KEY`, `DB_PASSWORD` — documented "never writable") became `[]`, so they
 *    were writable through the CLI, the TUI and the WebUI.
 *  - `hidden_keys` became `[]`, so `SecretRedactor` masked nothing and those same secrets were
 *    printed in cleartext in listings and written to the audit log.
 *  - `limits.max_value_length` became null (unbounded) and `schema` was never seeded.
 *
 * None of it raised an error, and the suite could not see it: the test harness sets the bare keys
 * itself, which created the tree the package was reading. That is what makes this worth a guard
 * rather than just a fix -- the next bare read would be invisible the same way.
 *
 * `hasConfigFile('env-kit')` is an **id, not a key**, so the argument there is correct as written;
 * the container tags (`env-kit.doctor_rules`, …) and the `env-kit.update` gate ability are separate
 * registries and are deliberately excluded below.
 */
function shippedEnvKitConfig(): array
{
    return require __DIR__ . '/../../config/env-kit.php';
}

it('registers its config where the package reads it', function (): void {
    // The whole-block read is the one that builds EnvKit itself. An empty array here is the bug.
    expect(config('laranail.env-kit'))->toBeArray()->not->toBeEmpty();

    expect(config()->has('env-kit'))
        ->toBeFalse('nothing should be registered at the bare key; a read there resolves to nothing');
});

it('exposes every shipped config value at the scoped key', function (): void {
    // Discovery-driven: a key added to the shipped file is covered here the day it lands.
    $missing = [];

    foreach (array_keys(shippedEnvKitConfig()) as $key) {
        if (! config()->has("laranail.env-kit.{$key}")) {
            $missing[] = $key;
        }
    }

    expect($missing)->toBe([], sprintf(
        "the shipped config offers these keys, but nothing resolves them at laranail.env-kit.*:\n  %s",
        implode("\n  ", $missing),
    ));
});

it('reads no configuration at a bare env-kit key', function (): void {
    // Asserted against the source, because a bare read is not an error -- it silently returns the
    // hardcoded default. This is the assertion that fails when someone adds the seventeenth one.
    // The container tags and the gate ability are different registries and keep their own names.
    $exempt = ['doctor_rules', 'port_formats', 'audit_sinks', 'observers', 'update'];
    $offenders = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../../src')) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        foreach (file($file->getPathname()) ?: [] as $number => $line) {
            // Only config lookups: `config('env-kit…')` or `->get('env-kit…')`. The trailing
            // segments matter: a first cut of this pattern anchored the closing quote straight
            // after the first segment and so matched `env-kit.path` but not
            // `env-kit.encryption.driver` -- it passed while that exact read was still bare.
            if (preg_match('/(?:config\(|->get\()\s*[\'"]env-kit(?:\.([a-z_]+))?(?:\.[a-z_.]+)?[\'"]/', $line, $m) !== 1) {
                continue;
            }

            if (in_array($m[1] ?? '', $exempt, true)) {
                continue;
            }

            $offenders[] = basename($file->getPathname()) . ':' . ($number + 1) . ' — ' . trim($line);
        }
    }

    expect($offenders)->toBe([], sprintf(
        "these read config at the bare key, which resolves to nothing in a real application:\n  %s",
        implode("\n  ", $offenders),
    ));
});

it('actually enforces the shipped protected keys', function (): void {
    // The end-to-end proof that the config is live rather than merely present: a shipped protected
    // key must be refused. This is the behaviour that was silently off in every deployment.
    $this->bindEnv("APP_NAME=x\n");

    expect(shippedEnvKitConfig()['protected_keys'])->toContain('DB_PASSWORD');
    // The concrete class, not Throwable: Pest's toThrow() falls back to matching the *message*
    // for anything class_exists() rejects, and Throwable is an interface -- so the loose spelling
    // silently asserts that the message contains the word 'Throwable'.
    expect(fn () => EnvKit::set('DB_PASSWORD', 'should-be-refused'))->toThrow(ProtectedKeyException::class);
});

it('can build a cipher for every driver its config can name', function (): void {
    // EnvKitManager extends Illuminate\Support\Manager, so the driver name is interpolated into a
    // method name: driver('vault') becomes createVaultDriver().
    $driver = shippedEnvKitConfig()['encryption']['driver'];
    $method = 'create' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', (string) $driver))) . 'Driver';

    expect(method_exists(EnvKitManager::class, $method))
        ->toBeTrue("config names the cipher driver [{$driver}], which EnvKitManager cannot build: {$method}()");

    expect(app(EnvKitManager::class)->cipher())->toBeInstanceOf(ValueCipherInterface::class);
});

it('fails loudly, not silently, on a cipher driver that does not exist', function (): void {
    // Pinning the failure mode: encryption must never quietly fall back to a different cipher.
    expect(fn () => app(EnvKitManager::class)->cipher('not-a-real-cipher'))
        ->toThrow(InvalidArgumentException::class);
});
