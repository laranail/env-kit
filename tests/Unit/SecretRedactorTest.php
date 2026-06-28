<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Security\SecretRedactor;

it('identifies secret-shaped keys', function () {
    $redactor = new SecretRedactor;

    expect($redactor->isSecretKey('DB_PASSWORD'))->toBeTrue()
        ->and($redactor->isSecretKey('API_TOKEN'))->toBeTrue()
        ->and($redactor->isSecretKey('APP_KEY'))->toBeTrue()
        ->and($redactor->isSecretKey('app_key'))->toBeTrue()
        ->and($redactor->isSecretKey('APP_NAME'))->toBeFalse();
});

it('treats every default secret pattern as secret', function (string $key) {
    // One key per default pattern so dropping any single pattern is detected:
    // *_SECRET, *_TOKEN, *_PASSWORD, *_KEY, *_PRIVATE, *_DSN.
    expect((new SecretRedactor)->isSecretKey($key))->toBeTrue();
})->with(['APP_SECRET', 'API_TOKEN', 'DB_PASSWORD', 'APP_KEY', 'SSH_PRIVATE', 'SENTRY_DSN']);

it('masks fully, or keeps a leading hint', function () {
    $redactor = new SecretRedactor(mask: '***');

    expect($redactor->redact('supersecret'))->toBe('***')
        ->and($redactor->redact('supersecret', keep: 4))->toBe('supe***')
        ->and($redactor->redact(''))->toBe('')
        ->and($redactor->redact('ab', keep: 4))->toBe('***'); // shorter than keep → fully masked
});

it('keeps exactly the requested hint length at the boundary', function () {
    $redactor = new SecretRedactor(mask: '***');

    // keep == strlen → fully masked (the boundary is a strict greater-than).
    expect($redactor->redact('abcd', keep: 4))->toBe('***')
        // keep == 1 keeps exactly one leading character.
        ->and($redactor->redact('abc', keep: 1))->toBe('a***');
});

it('masks values only for secret keys', function () {
    $redactor = new SecretRedactor(mask: '***');

    expect($redactor->forKey('DB_PASSWORD', 'hunter2'))->toBe('***')
        ->and($redactor->forKey('APP_NAME', 'Acme'))->toBe('Acme');
});

it('scrubs secret values out of a free-form message', function () {
    $redactor = new SecretRedactor(mask: '***');

    expect($redactor->scrub('connect failed for hunter2 at host', ['hunter2']))
        ->toBe('connect failed for *** at host');
});
