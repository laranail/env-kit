<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Laravel validation rule for an env value: must be a string and free of NUL
 * bytes. (Other control characters are stripped by ValueSanitizer at write time;
 * this rule rejects the unrecoverable case up front.)
 */
final class ValidEnvValue implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        if (str_contains($value, "\0")) {
            $fail('The :attribute must not contain NUL bytes.');
        }
    }
}
