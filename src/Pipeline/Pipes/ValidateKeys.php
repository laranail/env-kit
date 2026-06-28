<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Pipeline\Pipes;

use Closure;
use Simtabi\Laranail\EnvKit\Headless\Pipeline\CommitContext;
use Simtabi\Laranail\EnvKit\Headless\Security\KeyValidator;

/** Rejects invalid identifiers among the keys being added or updated. */
final class ValidateKeys
{
    public function __construct(
        private readonly KeyValidator $keys,
    ) {}

    public function handle(CommitContext $context, Closure $next): mixed
    {
        foreach ($context->changedKeys() as $key) {
            if ($context->document->has($key)) {
                $this->keys->validate($key);
            }
        }

        return $next($context);
    }
}
