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

/** A unique, auto-cleaned temp `.env` path for a filesystem test. */
function envkit_temp(): string
{
    static $counter = 0;

    $dir = sys_get_temp_dir().'/envkit-'.getmypid().'-'.(++$counter);
    @mkdir($dir, 0777, true);
    register_shutdown_function(static fn () => envkit_rmrf($dir));

    return $dir.'/.env';
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

        $path = $dir.'/'.$entry;
        is_dir($path) ? envkit_rmrf($path) : @unlink($path);
    }

    @rmdir($dir);
}
