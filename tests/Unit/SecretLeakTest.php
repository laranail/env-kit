<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Exceptions\InvalidValueException;
use Simtabi\Laranail\EnvKit\Headless\Security\SecretRedactor;
use Simtabi\Laranail\EnvKit\Headless\Security\ValueSanitizer;

it('never puts a raw secret value in an exception message', function () {
    $secret = 'super-secret-token-ABC123';

    $thrown = null;
    try {
        (new ValueSanitizer)->sanitize($secret."\0", key: 'API_TOKEN');
    } catch (InvalidValueException $e) {
        $thrown = $e;
    }

    expect($thrown)->not->toBeNull()
        ->and($thrown->getMessage())->not->toContain($secret) // value never leaks
        ->and($thrown->getMessage())->toContain('API_TOKEN');  // key name is fine
});

it('scrubs a secret out of any message via the redactor', function () {
    $secret = 'super-secret-token-ABC123';

    $scrubbed = (new SecretRedactor)->scrub("dsn=postgres://u:{$secret}@h/db", [$secret]);

    expect($scrubbed)->not->toContain($secret);
});
