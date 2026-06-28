<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Exceptions\FileNotWritableException;
use Simtabi\Laranail\EnvKit\Headless\Writer\AtomicEnvWriter;

it('writes exact bytes and creates the file', function () {
    $path = envkit_temp();

    (new AtomicEnvWriter)->write($path, "A=1\nB=2\n");

    expect(file_get_contents($path))->toBe("A=1\nB=2\n");
});

it('replaces existing content and leaves no temp files behind', function () {
    $path = envkit_temp();
    file_put_contents($path, "OLD=1\n");

    (new AtomicEnvWriter)->write($path, "NEW=2\n");

    expect(file_get_contents($path))->toBe("NEW=2\n");

    $leftovers = array_filter(
        scandir(dirname($path)) ?: [],
        static fn (string $f): bool => str_starts_with($f, '.env-kit-'),
    );
    expect($leftovers)->toBeEmpty();
});

it('preserves the mode of an existing file (never widens)', function () {
    $path = envkit_temp();
    file_put_contents($path, "A=1\n");
    chmod($path, 0600);

    (new AtomicEnvWriter)->write($path, "A=2\n");

    expect(substr(sprintf('%o', fileperms($path)), -4))->toBe('0600');
});

it('throws when the target directory is not writable', function () {
    (new AtomicEnvWriter)->write('/this/dir/does/not/exist/.env', "A=1\n");
})->throws(FileNotWritableException::class);
