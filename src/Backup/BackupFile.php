<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Backup;

/** Immutable descriptor of a single backup on disk. */
final class BackupFile
{
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly int $timestamp,
        public readonly int $size,
    ) {}
}
