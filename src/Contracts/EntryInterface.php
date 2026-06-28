<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Contracts;

use Simtabi\Laranail\EnvKit\Headless\Document\EnvDocument;

/**
 * A single parsed line of an .env document (a setter, comment, or blank line).
 *
 * Entries are immutable value objects. Changing a value produces a NEW entry,
 * which lets {@see EnvDocument} stay
 * immutable and preserve byte-fidelity on lines that were not touched.
 */
interface EntryInterface
{
    /**
     * The line's text WITHOUT a trailing end-of-line marker.
     *
     * Unchanged entries return their original raw text verbatim (minimal-diff);
     * new or modified entries are re-serialized per the §3B encoding spec.
     */
    public function render(): string;
}
