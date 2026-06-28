<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Contracts;

interface WriterInterface
{
    /**
     * Atomically write $contents to $path (creating or replacing it).
     *
     * Implementations must never leave a partially-written target: a concurrent
     * reader sees either the old file or the complete new one, never a mix.
     */
    public function write(string $path, string $contents): void;
}
