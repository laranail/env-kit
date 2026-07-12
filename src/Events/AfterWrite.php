<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Events;

/**
 * Dispatched after a successful commit. `$changes` carry key names with
 * ALREADY-REDACTED values, so queued listeners and logs can't leak secrets.
 */
final class AfterWrite
{
    /** @param list<array{key: string, old: ?string, new: ?string}> $changes */
    public function __construct(
        public readonly string $path,
        public readonly array $changes,
        public readonly ?string $actor = null,
        public readonly string $operation = 'write',
    ) {}
}
