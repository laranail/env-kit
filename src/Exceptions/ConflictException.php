<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Exceptions;

/** The file changed on disk since the session loaded it (optimistic-lock failure). */
final class ConflictException extends EnvKitException
{
    public static function for(string $path): self
    {
        return new self("The .env file changed on disk since it was loaded: {$path}");
    }
}
