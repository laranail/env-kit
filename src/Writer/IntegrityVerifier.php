<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Writer;

use Simtabi\Laranail\EnvKit\Headless\Document\EnvDocument;

/**
 * Re-reads a just-written file and confirms it parses back to the same logical
 * key→value map we intended to write. Catches truncated/partial/corrupted writes
 * before the session reports success.
 */
final class IntegrityVerifier
{
    public function verify(string $path, EnvDocument $expected): bool
    {
        if (! is_file($path)) {
            return false;
        }

        $actual = @file_get_contents($path);
        if ($actual === false) {
            return false;
        }

        return EnvDocument::parse($actual)->toArray() === $expected->toArray();
    }
}
