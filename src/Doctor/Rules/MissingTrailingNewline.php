<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Doctor\Rules;

use Simtabi\Laranail\EnvKit\Headless\Contracts\DoctorRuleInterface;
use Simtabi\Laranail\EnvKit\Headless\Doctor\Diagnostic;
use Simtabi\Laranail\EnvKit\Headless\Document\EnvDocument;

/** Warns when the file does not end with a trailing newline. */
final class MissingTrailingNewline implements DoctorRuleInterface
{
    public function check(EnvDocument $document): array
    {
        return (! $document->hasTrailingNewline() && $document->setters() !== [])
            ? [Diagnostic::warning('File does not end with a newline.')]
            : [];
    }
}
