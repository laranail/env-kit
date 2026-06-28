<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Contracts;

use Simtabi\Laranail\EnvKit\Headless\Audit\AuditEvent;

/** Destination for audit records. Implementations must treat events as final. */
interface AuditSinkInterface
{
    public function record(AuditEvent $event): void;
}
