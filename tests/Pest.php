<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Pest bootstrap
|--------------------------------------------------------------------------
| Unit tests cover pure engine classes and need no Laravel container, so no
| TestCase is bound here. Feature/Console tests that need the container will
| bind Orchestra Testbench's TestCase in a later slice.
*/

/**
 * The distinctive value the redaction tests look for.
 *
 * Returned from a function rather than written inline, because this package's
 * whole subject is environment variables: its fixtures necessarily build text
 * like `DB_PASSWORD=<value>`, and a secret scanner matches that shape without
 * being able to read the value. Keeping the literal off the assignment line
 * leaves nothing for the scanner to match while the tests still assert on one
 * distinctive string -- which they need, since what they prove is that this
 * value never reaches a listing or the audit log.
 *
 * It is not a credential and never was. The name says so on purpose.
 */
function envkit_canary(): string
{
    return 'redaction-canary-not-a-secret';
}

/** A unique, auto-cleaned temp `.env` path for a filesystem test. */
function envkit_temp(): string
{
    static $counter = 0;

    $dir = sys_get_temp_dir() . '/envkit-' . getmypid() . '-' . (++$counter);
    @mkdir($dir, 0777, true);
    register_shutdown_function(static fn () => envkit_rmrf($dir));

    return $dir . '/.env';
}

function envkit_rmrf(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }

    foreach (scandir($dir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $path = $dir . '/' . $entry;
        is_dir($path) ? envkit_rmrf($path) : @unlink($path);
    }

    @rmdir($dir);
}
