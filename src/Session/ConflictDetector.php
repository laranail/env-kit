<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Session;

use Simtabi\Laranail\EnvKit\Headless\Exceptions\ConflictException;

/**
 * Optimistic concurrency: fingerprint the file when a session opens, then verify
 * the fingerprint is unchanged immediately before committing — so a write never
 * silently clobbers an edit made by another process in the meantime.
 */
final class ConflictDetector
{
    public function fingerprint(string $path): string
    {
        if (! is_file($path)) {
            return 'absent';
        }

        $hash = @hash_file('sha256', $path);

        return $hash !== false ? $hash : 'mtime:'.((string) @filemtime($path));
    }

    public function ensureUnchanged(string $path, string $expected): void
    {
        if ($this->fingerprint($path) !== $expected) {
            throw ConflictException::for($path);
        }
    }
}
