<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Doctor\Rules;

use Simtabi\Laranail\EnvKit\Headless\Contracts\DoctorRuleInterface;
use Simtabi\Laranail\EnvKit\Headless\Doctor\Diagnostic;
use Simtabi\Laranail\EnvKit\Headless\Document\EnvDocument;

/** Notes keys with an empty value (often an oversight). */
final class BlankValue implements DoctorRuleInterface
{
    public function check(EnvDocument $document): array
    {
        $diagnostics = [];
        foreach ($document->setters() as $setter) {
            if ($setter->value === '') {
                $diagnostics[] = Diagnostic::info("Key [{$setter->key}] has an empty value.", $setter->key);
            }
        }

        return $diagnostics;
    }
}
