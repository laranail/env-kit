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

it('masks fully, or keeps a leading hint', function () {
    $redactor = new SecretRedactor(mask: '***');

    expect($redactor->redact('supersecret'))->toBe('***')
        ->and($redactor->redact('supersecret', keep: 4))->toBe('supe***')
        ->and($redactor->redact(''))->toBe('')
        ->and($redactor->redact('ab', keep: 4))->toBe('***'); // shorter than keep → fully masked
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
