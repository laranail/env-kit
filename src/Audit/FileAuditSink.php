<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Audit;

use Simtabi\Laranail\EnvKit\Headless\Contracts\AuditSinkInterface;

/** Appends one JSON object per line (JSON-lines) — the zero-config default sink. */
final class FileAuditSink implements AuditSinkInterface
{
    public function __construct(
        private readonly string $path,
    ) {}

    public function record(AuditEvent $event): void
    {
        $dir = \dirname($this->path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $line = json_encode($event->toArray(), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        @file_put_contents($this->path, $line.\PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
