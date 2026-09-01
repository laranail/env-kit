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

/**
 * Builds a non-cyclic chain V1 -> V2 -> ... -> V{n} = 'leaf', so resolving
 * `${V1}` recurses exactly $n levels deep before hitting the literal.
 *
 * @return array<string, string>
 */
function chainVars(int $n): array
{
    $vars = [];
    for ($i = 1; $i < $n; $i++) {
        $vars["V{$i}"] = '${V'.($i + 1).'}';
    }
    $vars["V{$n}"] = 'leaf';

    return $vars;
}

it('resolves a chain that reaches exactly the depth limit boundary', function () {
    // A 7-level chain is the deepest that resolves with the default depth 0 and
    // maxDepth 8; shifting the start depth up by one (mutating the `+ 1` step,
    // or the `$depth = 0` default) would make this throw instead.
    expect((new Interpolator)->resolve('${V1}', chainVars(7)))->toBe('leaf');
});

it('throws when a non-cyclic chain crosses the max depth boundary', function () {
    // An 8-level chain trips the `$depth >= maxDepth` guard exactly at the
    // boundary; relaxing it to `>` or starting depth lower would let it through.
    (new Interpolator)->resolve('${V1}', chainVars(8));
})->throws(ValidationException::class);
