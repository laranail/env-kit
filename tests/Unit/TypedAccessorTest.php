<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Support\TypedAccessor;

it('casts booleans and falls back to the default for null', function () {
    $typed = new TypedAccessor;

    expect($typed->bool('true', null))->toBeTrue()
        ->and($typed->bool('FALSE', null))->toBeFalse()
        ->and($typed->bool('1', null))->toBeTrue()
        ->and($typed->bool('off', null))->toBeFalse()
        ->and($typed->bool(null, true))->toBeTrue();
});

it('casts ints and floats, defaulting on non-numeric', function () {
    $typed = new TypedAccessor;

    expect($typed->int('42', null))->toBe(42)
        ->and($typed->int('nope', 7))->toBe(7)
        ->and($typed->float('3.14', null))->toBe(3.14)
        ->and($typed->float(null, 1.5))->toBe(1.5);
});

it('casts arrays from JSON or comma lists', function () {
    $typed = new TypedAccessor;

    expect($typed->array('["a","b"]', null))->toBe(['a', 'b'])
        ->and($typed->array('a, b ,c', null))->toBe(['a', 'b', 'c'])
        ->and($typed->array('', null))->toBe([])
        ->and($typed->array(null, ['d']))->toBe(['d']);
});

it('casts JSON, defaulting on invalid', function () {
    $typed = new TypedAccessor;

    expect($typed->json('{"x":1}', null))->toBe(['x' => 1])
        ->and($typed->json('not json', 'def'))->toBe('def');
});
