<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Doctor;

use Simtabi\Laranail\EnvKit\Headless\Contracts\DoctorRuleInterface;
use Simtabi\Laranail\EnvKit\Headless\Doctor\Rules\BlankValue;
use Simtabi\Laranail\EnvKit\Headless\Doctor\Rules\ByteOrderMark;
use Simtabi\Laranail\EnvKit\Headless\Doctor\Rules\DuplicateKeys;
use Simtabi\Laranail\EnvKit\Headless\Doctor\Rules\MissingTrailingNewline;
use Simtabi\Laranail\EnvKit\Headless\Document\EnvDocument;

/** Runs a set of {@see DoctorRuleInterface} over a document, collecting findings. */
final class Doctor
{
    /** @param list<DoctorRuleInterface> $rules */
    public function __construct(
        private readonly array $rules,
    ) {}

    /** @return list<Diagnostic> */
    public function inspect(EnvDocument $document): array
    {
        $diagnostics = [];
        foreach ($this->rules as $rule) {
            foreach ($rule->check($document) as $diagnostic) {
                $diagnostics[] = $diagnostic;
            }
        }

        return $diagnostics;
    }

    /** The built-in rule set, plus any consumer-registered extras. */
    public static function withDefaults(DoctorRuleInterface ...$extra): self
    {
        return new self(array_values(array_merge([
            new DuplicateKeys,
            new BlankValue,
            new ByteOrderMark,
            new MissingTrailingNewline,
        ], $extra)));
    }
}
