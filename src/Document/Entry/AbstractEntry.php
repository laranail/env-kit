<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Document\Entry;

use Simtabi\Laranail\EnvKit\Headless\Contracts\EntryInterface;

/**
 * Base for all entry value objects.
 *
 * `$original` is the raw line text as parsed (without EOL), or null for an entry
 * created in code. When present, {@see render()} returns it verbatim so untouched
 * lines round-trip byte-for-byte.
 */
abstract class AbstractEntry implements EntryInterface
{
    public function __construct(
        public readonly ?string $original = null,
    ) {}

    /** Was this entry created/modified in code (rather than parsed unchanged)? */
    public function isDirty(): bool
    {
        return $this->original === null;
    }
}
