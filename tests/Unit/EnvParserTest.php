<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Document\EnvDocument;

it('parses keys, values, export prefix and empty values', function () {
    $raw = implode("\n", [
        '# app config',
        'APP_NAME=Acme',
        'export APP_ENV="production"',
        'EMPTY=',
        'DB_PASSWORD="p@ss word#1"',
        '',
        'MAIL_HOST=smtp.example.com',
        'S3_BUCKET=my-bucket-1',
    ])."\n";

    $doc = EnvDocument::parse($raw);

    expect($doc->get('APP_NAME'))->toBe('Acme')
        ->and($doc->get('APP_ENV'))->toBe('production')
        ->and($doc->get('EMPTY'))->toBe('')
        ->and($doc->get('DB_PASSWORD'))->toBe('p@ss word#1')
        ->and($doc->get('MAIL_HOST'))->toBe('smtp.example.com')
        ->and($doc->get('S3_BUCKET'))->toBe('my-bucket-1') // keys with digits are allowed
        ->and($doc->has('NOPE'))->toBeFalse()
        ->and($doc->get('NOPE'))->toBeNull()
        ->and($doc->keys())->toBe(['APP_NAME', 'APP_ENV', 'EMPTY', 'DB_PASSWORD', 'MAIL_HOST', 'S3_BUCKET'])
        ->and($doc->toArray())->toHaveKey('APP_NAME', 'Acme');
});

it('updates and removes keys immutably', function () {
    $raw = "A=1\nB=2\n";
    $doc = EnvDocument::parse($raw);

    $updated = $doc->withValue('A', '99')->withValue('C', 'new');

    // original document is unchanged (immutability)
    expect($doc->get('A'))->toBe('1')
        ->and($doc->has('C'))->toBeFalse()
        ->and($updated->get('A'))->toBe('99')
        ->and($updated->get('C'))->toBe('new')
        ->and($updated->without('B')->has('B'))->toBeFalse();
});
