<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Document\EnvDocument;
use Simtabi\Laranail\EnvKit\Headless\Tests\TestCase;

uses(TestCase::class);

it('sets a key via the env:set alias and the namespaced name', function () {
    $path = $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false]);

    $this->artisan('env:set', ['key' => 'NEW', 'value' => 'val'])->assertExitCode(0);
    $this->artisan('laranail::env-kit-headless.set', ['key' => 'B', 'value' => '2'])->assertExitCode(0);

    $doc = EnvDocument::parse(file_get_contents($path));
    expect($doc->get('NEW'))->toBe('val')
        ->and($doc->get('B'))->toBe('2');
});

it('accepts KEY=VALUE shorthand and rejects the mixed form', function () {
    $path = $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false]);

    $this->artisan('env:set', ['key' => 'FOO=bar'])->assertExitCode(0);
    expect(EnvDocument::parse(file_get_contents($path))->get('FOO'))->toBe('bar');

    // both KEY VALUE and KEY=VALUE at once → usage error (exit 2)
    $this->artisan('env:set', ['key' => 'X=1', 'value' => '2'])->assertExitCode(2);
});

it('rejects an invalid key with the validation exit code', function () {
    $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false]);

    $this->artisan('env:set', ['key' => '1bad', 'value' => 'x'])->assertExitCode(3);
});

it('reads a value with env:get (and a default)', function () {
    $this->bindEnv("APP_NAME=Acme\n");

    $this->artisan('env:get', ['key' => 'APP_NAME'])->expectsOutput('Acme')->assertExitCode(0);
    $this->artisan('env:get', ['key' => 'MISSING', '--default' => 'fallback'])
        ->expectsOutput('fallback')
        ->assertExitCode(0);
});

it('removes a key with env:unset', function () {
    $path = $this->bindEnv("A=1\nB=2\n", ['env-kit.auto_backup' => false]);

    $this->artisan('env:unset', ['key' => 'A'])->assertExitCode(0);

    expect(EnvDocument::parse(file_get_contents($path))->has('A'))->toBeFalse();
});

it('renames a key with env:rename', function () {
    $path = $this->bindEnv("OLD=1\n", ['env-kit.auto_backup' => false]);

    $this->artisan('env:rename', ['from' => 'OLD', 'to' => 'NEW'])->assertExitCode(0);

    expect(EnvDocument::parse(file_get_contents($path))->get('NEW'))->toBe('1');
});

it('lists keys, and masks secret values unless --reveal', function () {
    $this->bindEnv("APP_NAME=Acme\nDB_PASSWORD=topsecret123\n");

    $this->artisan('env:keys')->expectsOutputToContain('APP_NAME')->assertExitCode(0);

    $this->artisan('env:list')
        ->expectsOutputToContain('APP_NAME=Acme')
        ->doesntExpectOutputToContain('topsecret123')
        ->assertExitCode(0);

    $this->artisan('env:list', ['--reveal' => true])
        ->expectsOutputToContain('topsecret123')
        ->assertExitCode(0);
});
