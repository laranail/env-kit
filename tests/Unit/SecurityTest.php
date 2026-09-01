<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Exceptions\InvalidKeyException;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\InvalidValueException;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\ProductionGuardException;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\ProtectedKeyException;
use Simtabi\Laranail\EnvKit\Headless\Security\KeyValidator;
use Simtabi\Laranail\EnvKit\Headless\Security\ProductionGuard;
use Simtabi\Laranail\EnvKit\Headless\Security\ProtectedKeys;
use Simtabi\Laranail\EnvKit\Headless\Security\ValueSanitizer;

describe('KeyValidator', function () {
    it('accepts valid keys, including digits after the first char', function (string $key) {
        expect((new KeyValidator)->isValid($key))->toBeTrue();
    })->with(['APP_NAME', '_PRIVATE', 'S3_BUCKET', 'A1', 'MAIL_HOST_2']);

    it('rejects invalid keys', function (string $key) {
        expect((new KeyValidator)->isValid($key))->toBeFalse();
    })->with(['1ABC', 'has space', 'a-b', '', 'MAIL.HOST', 'KEY!']);

    it('throws on validate() of an invalid key', function () {
        (new KeyValidator)->validate('1bad');
    })->throws(InvalidKeyException::class);
});

describe('ValueSanitizer', function () {
    it('keeps tab, newline, CR and ordinary characters', function () {
        expect((new ValueSanitizer)->sanitize("a\tb\nc\rd e#f"))->toBe("a\tb\nc\rd e#f");
    });

    it('strips disallowed control characters', function () {
        expect((new ValueSanitizer)->sanitize("a\x01b\x07c\x1Fd\x7Fe"))->toBe('abcde');
    });

    it('rejects NUL bytes', function () {
        (new ValueSanitizer)->sanitize("a\0b");
    })->throws(InvalidValueException::class);

    it('reports cleanliness', function () {
        expect((new ValueSanitizer)->isClean('clean'))->toBeTrue()
            ->and((new ValueSanitizer)->isClean("dirty\x01"))->toBeFalse();
    });
});

describe('ProtectedKeys', function () {
    it('matches exact and wildcard patterns, case-insensitively', function () {
        $keys = new ProtectedKeys(['APP_KEY', '*_PASSWORD']);

        expect($keys->isProtected('APP_KEY'))->toBeTrue()
            ->and($keys->isProtected('DB_PASSWORD'))->toBeTrue()
            ->and($keys->isProtected('app_key'))->toBeTrue()
            ->and($keys->isProtected('APP_NAME'))->toBeFalse();
    });

    it('guards a protected key', function () {
        (new ProtectedKeys(['APP_KEY']))->guard('APP_KEY');
    })->throws(ProtectedKeyException::class);

    it('lets a non-protected key through', function () {
        (new ProtectedKeys(['APP_KEY']))->guard('APP_NAME');
        expect(true)->toBeTrue();
    });
});

describe('ProductionGuard', function () {
    it('blocks writes in production', function () {
        (new ProductionGuard(isProduction: true))->guard();
    })->throws(ProductionGuardException::class);

    it('allows with override, outside production, or when unprotected', function () {
        (new ProductionGuard(true))->guard(allowOverride: true);
        (new ProductionGuard(false))->guard();
        (new ProductionGuard(true, protect: false))->guard();
        expect(true)->toBeTrue();
    });

    it('reports whether to warn', function () {
        expect((new ProductionGuard(true))->shouldWarn())->toBeTrue()
            ->and((new ProductionGuard(false))->shouldWarn())->toBeFalse();
    });
});
