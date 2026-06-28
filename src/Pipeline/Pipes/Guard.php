<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Pipeline\Pipes;

use Closure;
use Simtabi\Laranail\EnvKit\Headless\Pipeline\CommitContext;
use Simtabi\Laranail\EnvKit\Headless\Security\ProductionGuard;
use Simtabi\Laranail\EnvKit\Headless\Security\ProtectedKeys;

/** Enforces the production guard and the protected-key policy before any write. */
final class Guard
{
    public function __construct(
        private readonly ProductionGuard $production,
        private readonly ProtectedKeys $protected,
    ) {}

    public function handle(CommitContext $context, Closure $next): mixed
    {
        $this->production->guard($context->allowProduction);

        foreach ($context->changedKeys() as $key) {
            $this->protected->guard($key);
        }

        return $next($context);
    }
}
