<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Contracts;

use Simtabi\Laranail\EnvKit\Headless\Doctor\Diagnostic;
use Simtabi\Laranail\EnvKit\Headless\Document\EnvDocument;

/** A single health-check rule. Consumers register their own via configure(). */
interface DoctorRuleInterface
{
    /** @return list<Diagnostic> */
    public function check(EnvDocument $document): array;
}
