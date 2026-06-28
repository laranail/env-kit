<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Document\Entry;

/** A blank (or whitespace-only) line. */
final class EmptyLine extends AbstractEntry
{
    public function render(): string
    {
        return $this->original ?? '';
    }
}
