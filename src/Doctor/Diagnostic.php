<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Doctor;

/** One health-check finding. Severity is error | warning | info. */
final class Diagnostic
{
    public function __construct(
        public readonly string $severity,
        public readonly string $message,
        public readonly ?string $key = null,
    ) {}

    public static function error(string $message, ?string $key = null): self
    {
        return new self('error', $message, $key);
    }

    public static function warning(string $message, ?string $key = null): self
    {
        return new self('warning', $message, $key);
    }

    public static function info(string $message, ?string $key = null): self
    {
        return new self('info', $message, $key);
    }

    public function isError(): bool
    {
        return $this->severity === 'error';
    }
}
