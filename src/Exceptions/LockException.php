<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Exceptions;

final class LockException extends EnvKitException
{
    public static function for(string $path): self
    {
        return new self("Could not acquire an exclusive lock for: {$path}");
    }
}
