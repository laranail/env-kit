<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Pipeline\Pipes;

use Closure;
use Simtabi\Laranail\EnvKit\Headless\Backup\BackupManager;
use Simtabi\Laranail\EnvKit\Headless\Pipeline\CommitContext;

/** Snapshots the current file before it is overwritten (when backups are enabled). */
final class Backup
{
    public function __construct(
        private readonly ?BackupManager $backups,
    ) {}

    public function handle(CommitContext $context, Closure $next): mixed
    {
        $this->backups?->backup($context->path);

        return $next($context);
    }
}
