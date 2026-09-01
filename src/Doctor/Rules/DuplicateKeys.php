<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Doctor\Rules;

use Simtabi\Laranail\EnvKit\Headless\Contracts\DoctorRuleInterface;
use Simtabi\Laranail\EnvKit\Headless\Doctor\Diagnostic;
use Simtabi\Laranail\EnvKit\Headless\Document\EnvDocument;

/** Flags keys defined more than once (the later wins — a silent footgun). */
final class DuplicateKeys implements DoctorRuleInterface
{
    public function check(EnvDocument $document): array
    {
        $counts = [];
        foreach ($document->setters() as $setter) {
            $counts[$setter->key] = ($counts[$setter->key] ?? 0) + 1;
        }

        $diagnostics = [];
        foreach ($counts as $key => $count) {
            if ($count > 1) {
                $diagnostics[] = Diagnostic::error("Key [{$key}] is defined {$count} times.", (string) $key);
            }
        }

        return $diagnostics;
    }
}
