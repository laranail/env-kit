<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Document\Entry;

use Simtabi\Laranail\EnvKit\Headless\Support\ValueFormatter;

/**
 * A `KEY=value` assignment. `$value` is the LOGICAL value (unquoted/unescaped —
 * what `get()` returns); the raw representation is produced by {@see ValueFormatter}.
 */
final class Setter extends AbstractEntry
{
    public function __construct(
        public readonly string $key,
        public readonly string $value,
        public readonly bool $export = false,
        public readonly bool $alwaysQuote = false,
        ?string $original = null,
    ) {
        parent::__construct($original);
    }

    public function render(): string
    {
        // Unchanged → byte-fidelity; changed/new → re-encode per §3B (minimal-diff).
        if ($this->original !== null) {
            return $this->original;
        }

        $prefix = $this->export ? 'export ' : '';

        return $prefix.$this->key.'='.ValueFormatter::encode($this->value, $this->alwaysQuote);
    }

    /** A new, dirty Setter for the same key with a different value. */
    public function withValue(string $value): self
    {
        return new self($this->key, $value, $this->export, $this->alwaysQuote);
    }
}
