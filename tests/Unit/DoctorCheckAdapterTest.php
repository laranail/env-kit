<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Contracts\DoctorRuleInterface;
use Simtabi\Laranail\EnvKit\Headless\Doctor\BespokeRuleCheck;
use Simtabi\Laranail\EnvKit\Headless\Doctor\Diagnostic;
use Simtabi\Laranail\EnvKit\Headless\Document\EnvDocument;
use Simtabi\Laranail\Package\Tools\Services\Doctor\DoctorStatus;

/** A bespoke rule that always returns the given diagnostics. */
function adapter_rule(Diagnostic ...$diagnostics): DoctorRuleInterface
{
    return new class(array_values($diagnostics)) implements DoctorRuleInterface
    {
        /** @param list<Diagnostic> $diagnostics */
        public function __construct(private readonly array $diagnostics) {}

        public function check(EnvDocument $document): array
        {
            return $this->diagnostics;
        }
    };
}

function adapter_check(DoctorRuleInterface $rule): BespokeRuleCheck
{
    return new BespokeRuleCheck(
        $rule,
        static fn (): EnvDocument => EnvDocument::parse("A=1\n"),
        'env:test-rule',
        'A test rule.',
    );
}

it('exposes name and description through the package-tools contract', function () {
    $check = adapter_check(adapter_rule());

    expect($check->name())->toBe('env:test-rule')
        ->and($check->description())->toBe('A test rule.');
});

it('maps a bespoke pass (no diagnostics) to a DoctorResult pass', function () {
    $result = adapter_check(adapter_rule())->run();

    expect($result->status)->toBe(DoctorStatus::Pass)
        ->and($result->message)->toBe('No issues found.');
});

it('maps a bespoke error diagnostic to a DoctorResult fail', function () {
    $result = adapter_check(adapter_rule(
        Diagnostic::error('Key [A] is defined 2 times.', 'A'),
    ))->run();

    expect($result->status)->toBe(DoctorStatus::Fail)
        ->and($result->message)->toContain('[A] Key [A] is defined 2 times.')
        ->and($result->detail['findings'])->toBe(['[A] Key [A] is defined 2 times.']);
});

it('maps non-error diagnostics to a DoctorResult warn', function () {
    $result = adapter_check(adapter_rule(
        Diagnostic::warning('File does not end with a newline.'),
        Diagnostic::info('Key [A] has an empty value.', 'A'),
    ))->run();

    expect($result->status)->toBe(DoctorStatus::Warn)
        ->and($result->message)->toContain('File does not end with a newline.');
});

it('never throws — a broken document provider yields a skip', function () {
    $check = new BespokeRuleCheck(
        adapter_rule(Diagnostic::error('unreached')),
        static function (): EnvDocument {
            throw new RuntimeException('cannot read .env');
        },
        'env:test-rule',
        'A test rule.',
    );

    $result = $check->run();

    expect($result->status)->toBe(DoctorStatus::Skip)
        ->and($result->message)->toContain('cannot read .env');
});
