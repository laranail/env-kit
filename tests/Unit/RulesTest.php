<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Rules\ValidEnvKey;
use Simtabi\Laranail\EnvKit\Headless\Rules\ValidEnvValue;

/** @return list<string> messages collected from the rule's $fail callback */
function envkit_run_rule(object $rule, mixed $value): array
{
    $messages = [];
    $rule->validate('field', $value, function (string $message) use (&$messages): void {
        $messages[] = $message;
    });

    return $messages;
}

it('ValidEnvKey passes valid keys and fails invalid / non-string', function () {
    expect(envkit_run_rule(new ValidEnvKey, 'APP_NAME'))->toBeEmpty()
        ->and(envkit_run_rule(new ValidEnvKey, 'S3_BUCKET'))->toBeEmpty()
        ->and(envkit_run_rule(new ValidEnvKey, '1bad'))->toHaveCount(1)
        ->and(envkit_run_rule(new ValidEnvKey, 123))->toHaveCount(1);
});

it('ValidEnvValue rejects non-strings and NUL bytes', function () {
    expect(envkit_run_rule(new ValidEnvValue, 'fine'))->toBeEmpty()
        ->and(envkit_run_rule(new ValidEnvValue, "bad\0value"))->toHaveCount(1)
        ->and(envkit_run_rule(new ValidEnvValue, ['array']))->toHaveCount(1);
});
