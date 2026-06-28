<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Support\ValueFormatter;

it('leaves simple values bare', function () {
    expect(ValueFormatter::encode('simple'))->toBe('simple')
        ->and(ValueFormatter::encode('https://example.com/p?a=1'))->toBe('https://example.com/p?a=1')
        ->and(ValueFormatter::encode(''))->toBe('');
});

it('reports which values need quoting', function (string $value, bool $expected) {
    expect(ValueFormatter::needsQuoting($value))->toBe($expected);
})->with([
    ['simple', false],
    ['', false],
    ['has space', true],
    ['with#hash', true],
    ['a=b', false], // a bare '=' is unambiguous in dotenv (split on first '=')
    [' leading', true],
    ['trailing ', true],
    ["with\ttab", true],
    ['with"quote', true],
    ['with$var', true],
    ['back\\slash', true],
]);

it('quotes values that need it', function (string $value) {
    expect(ValueFormatter::encode($value)[0])->toBe('"');
})->with(['has space', 'with#hash', ' leading', "with\ttab", 'with"quote', 'with$var', 'back\\slash']);

it('round-trips encode then decode for tricky values', function (string $value) {
    expect(ValueFormatter::decode(ValueFormatter::encode($value)))->toBe($value);
})->with([
    'simple', 'has space', 'with#hash', 'a=b', 'with"quote', 'with$var',
    'back\\slash', "new\nline", "tab\there", '',
]);

it('forces quotes when asked', function () {
    expect(ValueFormatter::encode('simple', alwaysQuote: true))->toBe('"simple"');
});

it('decodes single-quoted values literally (no unescaping)', function () {
    expect(ValueFormatter::decode("'literal \\n stays'"))->toBe('literal \\n stays');
});

it('strips an inline comment from an unquoted value', function () {
    expect(ValueFormatter::decode('bare # a comment'))->toBe('bare')
        ->and(ValueFormatter::decode('  spaced  '))->toBe('spaced');
});
