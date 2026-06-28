<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Audit;

use Simtabi\Laranail\EnvKit\Headless\Contracts\AuditSinkInterface;

/** Discards audit events (the default when auditing is disabled). */
final class NullAuditSink implements AuditSinkInterface
{
    public function record(AuditEvent $event): void
    {
        //
    }
}
