<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Document\Entry;

/**
 * A comment line (`# ...`). Also used as a lossless carrier for any line that
 * is neither a blank line nor a valid setter, so malformed input round-trips
 * without data loss (the `doctor` linter flags such lines separately).
 */
final class Comment extends AbstractEntry
{
    public function __construct(
        public readonly string $text,
        ?string $original = null,
    ) {
        parent::__construct($original);
    }

    public function render(): string
    {
        if ($this->original !== null) {
            return $this->original;
        }

        return $this->text === '' ? '#' : '# '.$this->text;
    }
}
