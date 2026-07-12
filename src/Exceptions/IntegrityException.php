<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Exceptions;

/** A post-write verification failed; the write was rolled back. */
final class IntegrityException extends EnvKitException
{
    public static function for(string $path): self
    {
        return new self("Post-write integrity check failed; rolled back: {$path}");
    }
}
