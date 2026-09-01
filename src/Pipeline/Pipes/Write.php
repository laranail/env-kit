<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Pipeline\Pipes;

use Closure;
use Simtabi\Laranail\EnvKit\Headless\Contracts\WriterInterface;
use Simtabi\Laranail\EnvKit\Headless\Pipeline\CommitContext;

/** Captures the current bytes (for rollback) then atomically writes the new document. */
final class Write
{
    public function __construct(
        private readonly WriterInterface $writer,
    ) {}

    public function handle(CommitContext $context, Closure $next): mixed
    {
        $context->previous = is_file($context->path) ? (string) @file_get_contents($context->path) : null;

        $this->writer->write($context->path, $context->document->render());

        return $next($context);
    }
}
