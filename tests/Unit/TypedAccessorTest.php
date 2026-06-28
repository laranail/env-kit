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

it('trims surrounding whitespace before matching bool tokens', function () {
    // Without the trim, '  false  ' would miss every arm and fall through to
    // the truthy `(bool) $raw` default.
    expect((new TypedAccessor)->bool('  false  ', null))->toBeFalse();
});

it('casts unknown non-empty bool tokens via the boolean default', function () {
    // The default arm must (bool)-cast: a non-empty, non-token string is true.
    expect((new TypedAccessor)->bool('maybe', null))->toBeTrue();
});

it('trims control whitespace before numeric parsing', function () {
    $typed = new TypedAccessor;

    // A leading NUL is stripped by trim() but breaks both is_numeric() and the
    // (int)/(float) cast when left in place, so the trims on both operands matter.
    expect($typed->int("\x00".'42', 7))->toBe(42)
        ->and($typed->float("\x00".'42', 7.0))->toBe(42.0);
});

it('decodes JSON objects to associative arrays', function () {
    // json_decode must use assoc=true; with objects, is_array() would fail and
    // the value would be wrongly comma-split.
    expect((new TypedAccessor)->array('{"a":1}', null))->toBe(['a' => 1]);
});

it('returns the default from json() for a null raw value', function () {
    expect((new TypedAccessor)->json(null, 'def'))->toBe('def');
});

it('honours the json() decode depth limit', function () {
    $typed = new TypedAccessor;

    // 511 nested levels decode within the 512 depth budget; 512 levels exceed it
    // and fall back to the default — pinning the literal 512 depth argument.
    $within = str_repeat('[', 511).str_repeat(']', 511);
    $beyond = str_repeat('[', 512).str_repeat(']', 512);

    expect($typed->json($within, 'def'))->toBeArray()
        ->and($typed->json($beyond, 'def'))->toBe('def');
});
