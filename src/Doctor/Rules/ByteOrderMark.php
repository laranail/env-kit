<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Doctor\Rules;

use Simtabi\Laranail\EnvKit\Headless\Contracts\DoctorRuleInterface;
use Simtabi\Laranail\EnvKit\Headless\Doctor\Diagnostic;
use Simtabi\Laranail\EnvKit\Headless\Document\EnvDocument;

/** Warns on a leading UTF-8 BOM, which several .env parsers mishandle. */
final class ByteOrderMark implements DoctorRuleInterface
{
    public function check(EnvDocument $document): array
    {
        return $document->hasBom()
            ? [Diagnostic::warning('File begins with a UTF-8 BOM, which some parsers mishandle.')]
            : [];
    }
}
