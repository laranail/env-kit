<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Security;

use Simtabi\Laranail\EnvKit\Headless\Exceptions\ProductionGuardException;

/**
 * Blocks writes in production unless explicitly opted in (§9). The environment
 * flag is injected (the service provider passes `app()->isProduction()`), so the
 * engine stays free of framework globals. Reads are never blocked.
 */
final class ProductionGuard
{
    public function __construct(
        private readonly bool $isProduction,
        private readonly bool $protect = true,
    ) {}

    public function guard(bool $allowOverride = false): void
    {
        if ($this->isProduction && $this->protect && ! $allowOverride) {
            throw ProductionGuardException::make();
        }
    }

    /** Whether a production warning banner should be shown (CLI/TUI/Web). */
    public function shouldWarn(): bool
    {
        return $this->isProduction;
    }
}
