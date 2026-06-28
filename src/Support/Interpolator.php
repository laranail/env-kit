<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Support;

use Simtabi\Laranail\EnvKit\Headless\Exceptions\ValidationException;

/**
 * Resolves `${VAR}` references against other keys (phpdotenv-style: brace form
 * only, never bare `$VAR`). Values are stored literally and resolved on read.
 * Undefined references become '' (or throw when configured); cycles are detected.
 */
final class Interpolator
{
    public function __construct(
        private readonly bool $throwOnUndefined = false,
        private readonly int $maxDepth = 8,
    ) {}

    /** @param array<string, string> $vars */
    public function resolve(string $value, array $vars, int $depth = 0): string
    {
        if ($depth >= $this->maxDepth) {
            throw new ValidationException('Interpolation exceeded max depth (possible cycle).');
        }

        return preg_replace_callback(
            '/\$\{([A-Za-z_][A-Za-z0-9_]*)\}/',
            function (array $matches) use ($vars, $depth): string {
                $name = $matches[1];

                if (! \array_key_exists($name, $vars)) {
                    if ($this->throwOnUndefined) {
                        throw new ValidationException("Undefined interpolation variable: \${{$name}}.");
                    }

                    return '';
                }

                return $this->resolve($vars[$name], $vars, $depth + 1);
            },
            $value,
        ) ?? $value;
    }
}
