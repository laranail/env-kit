<?php

declare(strict_types=1);

/*
 * env-kit prompts through laranail/console, which wraps laravel/prompts. It does
 * not declare laravel/prompts, so a direct import would lean on a package it never
 * asked for and bypass the wrapper's API (Prompter::canPrompt() among it).
 */

arch('src/ does not use laravel/prompts directly')
    ->expect('Simtabi\Laranail\EnvKit\Headless')
    ->not->toUse('Laravel\Prompts');

it('names Laravel\Prompts in no file under src/', function () {
    // The arch rule reads resolved names. This is the crude check beside it: a raw
    // substring over every PHP file, so an import the rule cannot see still fails.
    $files = new RegexIterator(
        new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2) . '/src')),
        '/\.php$/',
    );

    $inspected = 0;
    $offenders = [];

    foreach ($files as $file) {
        $inspected++;

        if (str_contains((string) file_get_contents((string) $file), 'Laravel\Prompts')) {
            $offenders[] = (string) $file;
        }
    }

    // A glob that stops matching would report a clean tree; src/ holds well over 100.
    expect($inspected)->toBeGreaterThan(100)
        ->and($offenders)->toBe([]);
});
