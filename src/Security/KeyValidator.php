<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Security;

use Simtabi\Laranail\EnvKit\Headless\Exceptions\InvalidKeyException;

/**
 * Validates env key identifiers. Keys may contain digits AFTER the first
 * character (so `S3_BUCKET` is valid) — fixing a bug present in some sources
 * whose regex rejected digit-containing keys.
 */
final class KeyValidator
{
    public const PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    public function isValid(string $key): bool
    {
        return preg_match(self::PATTERN, $key) === 1;
    }

    public function validate(string $key): void
    {
        if (! $this->isValid($key)) {
            throw InvalidKeyException::for($key);
        }
    }
}
