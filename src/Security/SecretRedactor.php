<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Security;

/**
 * Masks secret-shaped values for logs, audit records and UI listings. Exceptions
 * never carry raw values in the first place; this redactor is the second line of
 * defence for everything else.
 */
final class SecretRedactor
{
    /** @param list<string> $secretKeyPatterns wildcard key patterns treated as secret */
    public function __construct(
        private readonly array $secretKeyPatterns = ['*_SECRET', '*_TOKEN', '*_PASSWORD', '*_KEY', '*_PRIVATE', '*_DSN'],
        private readonly string $mask = '••••••',
    ) {}

    public function isSecretKey(string $key): bool
    {
        foreach ($this->secretKeyPatterns as $pattern) {
            if (fnmatch($pattern, $key, FNM_CASEFOLD)) {
                return true;
            }
        }

        return false;
    }

    /** Mask a value, optionally keeping the first $keep characters as a hint. */
    public function redact(string $value, int $keep = 0): string
    {
        if ($value === '') {
            return '';
        }

        if ($keep > 0 && \strlen($value) > $keep) {
            return substr($value, 0, $keep).$this->mask;
        }

        return $this->mask;
    }

    /** Mask the value only when the key looks secret; otherwise return it as-is. */
    public function forKey(string $key, string $value, int $keep = 0): string
    {
        return $this->isSecretKey($key) ? $this->redact($value, $keep) : $value;
    }

    /**
     * Replace any occurrence of the given secret values in a free-form string
     * (e.g. a third-party log line) with the mask.
     *
     * @param  list<string>  $secrets
     */
    public function scrub(string $message, array $secrets): string
    {
        foreach ($secrets as $secret) {
            if ($secret !== '') {
                $message = str_replace($secret, $this->mask, $message);
            }
        }

        return $message;
    }
}
