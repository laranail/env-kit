<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Security;

use Simtabi\Laranail\EnvKit\Headless\Exceptions\ProtectedKeyException;

/**
 * The "never writable" tier of the layered key policy (§9). Supports wildcard
 * patterns (e.g. `*_PASSWORD`). Enforced for every surface so nothing can edit
 * a protected key. Reads are unaffected.
 */
final class ProtectedKeys
{
    /** @param list<string> $patterns */
    public function __construct(
        private readonly array $patterns = ['APP_KEY', 'DB_PASSWORD'],
    ) {}

    public function isProtected(string $key): bool
    {
        foreach ($this->patterns as $pattern) {
            if (fnmatch($pattern, $key, FNM_CASEFOLD)) {
                return true;
            }
        }

        return false;
    }

    public function guard(string $key): void
    {
        if ($this->isProtected($key)) {
            throw ProtectedKeyException::for($key);
        }
    }
}
