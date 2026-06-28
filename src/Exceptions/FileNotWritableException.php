<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Exceptions;

final class FileNotWritableException extends EnvKitException
{
    public static function for(string $path): self
    {
        return new self("Path is not writable: {$path}");
    }
}
