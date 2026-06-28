<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Contracts\DoctorRuleInterface;
use Simtabi\Laranail\EnvKit\Headless\Doctor\Diagnostic;
use Simtabi\Laranail\EnvKit\Headless\Document\EnvDocument;
use Simtabi\Laranail\EnvKit\Headless\Facades\EnvKit;
use Simtabi\Laranail\EnvKit\Headless\Tests\TestCase;

uses(TestCase::class);

it('reports a clean file with env:doctor', function () {
    $this->bindEnv("APP_NAME=Acme\nDEBUG=true\n");

    $this->artisan('env:doctor')->expectsOutputToContain('No issues found.')->assertExitCode(0);
});

it('flags duplicate keys as an error (exit 3)', function () {
    $this->bindEnv("A=1\nA=2\nB=3\n");

    $this->artisan('env:doctor')->expectsOutputToContain('defined 2 times')->assertExitCode(3);
});

it('warns on a missing trailing newline (non-fatal)', function () {
    $this->bindEnv('A=1'); // no trailing newline

    $this->artisan('env:doctor')->expectsOutputToContain('newline')->assertExitCode(0);
});

it('runs a custom doctor rule registered via configure()', function () {
    $this->bindEnv("FORBIDDEN=1\n");

    EnvKit::configure()->registerDoctorRule(new class implements DoctorRuleInterface
    {
        public function check(EnvDocument $document): array
        {
            return $document->has('FORBIDDEN')
                ? [Diagnostic::error('FORBIDDEN key present.', 'FORBIDDEN')]
                : [];
        }
    });

    $this->artisan('env:doctor')->expectsOutputToContain('FORBIDDEN key present')->assertExitCode(3);
});

it('returns a structured diff between two files', function () {
    $path = $this->bindEnv("A=1\nB=2\nC=3\n");
    $other = dirname($path).'/.env.other';
    file_put_contents($other, "A=1\nB=99\nD=4\n");

    $diff = EnvKit::diff($other);

    expect($diff['only_here'])->toBe(['C'])
        ->and($diff['only_there'])->toBe(['D'])
        ->and($diff['changed'])->toBe(['B']);
});

it('prints the diff with env:diff', function () {
    $path = $this->bindEnv("A=1\nC=3\n");
    $other = dirname($path).'/.env.other';
    file_put_contents($other, "A=1\nD=4\n");

    $this->artisan('env:diff', ['against' => $other])
        ->expectsOutputToContain('C (only here)')
        ->expectsOutputToContain('D')
        ->assertExitCode(0);
});

it('reports no differences for identical files', function () {
    $path = $this->bindEnv("A=1\n");
    $other = dirname($path).'/.env.same';
    file_put_contents($other, "A=1\n");

    $this->artisan('env:diff', ['against' => $other])->expectsOutputToContain('No differences.')->assertExitCode(0);
});
