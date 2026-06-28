<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Exceptions;

final class KeyNotFoundException extends EnvKitException
{
    public static function for(string $key): self
    {
        return new self("Environment key not found: {$key}");
    }
}
