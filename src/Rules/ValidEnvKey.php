<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Simtabi\Laranail\EnvKit\Headless\Security\KeyValidator;

/**
 * Laravel validation rule wrapping {@see KeyValidator}, so CLI input,
 * programmatic calls, and WebUI FormRequests all validate keys identically.
 */
final class ValidEnvKey implements ValidationRule
{
    public function __construct(
        private readonly KeyValidator $validator = new KeyValidator,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! $this->validator->isValid($value)) {
            $fail('The :attribute must be a valid environment key (letters, digits and underscores, not starting with a digit).');
        }
    }
}
