<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Exceptions\ValidationException;
use Simtabi\Laranail\EnvKit\Headless\Support\Interpolator;

it('resolves ${VAR} brace references but not bare $VAR', function () {
    $interpolator = new Interpolator;

    expect($interpolator->resolve('${A}/${B}', ['A' => 'x', 'B' => 'y']))->toBe('x/y')
        ->and($interpolator->resolve('$A', ['A' => 'x']))->toBe('$A');
});

it('resolves nested references', function () {
    expect((new Interpolator)->resolve('${A}', ['A' => '${B}', 'B' => 'deep']))->toBe('deep');
});

it('leaves undefined references empty by default', function () {
    expect((new Interpolator)->resolve('a-${MISSING}-b', []))->toBe('a--b');
});

it('throws on undefined references when configured', function () {
    (new Interpolator(throwOnUndefined: true))->resolve('${MISSING}', []);
})->throws(ValidationException::class);

it('detects interpolation cycles', function () {
    (new Interpolator)->resolve('${A}', ['A' => '${B}', 'B' => '${A}']);
})->throws(ValidationException::class);
