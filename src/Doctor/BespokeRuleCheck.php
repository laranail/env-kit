<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Doctor;

use Closure;
use Simtabi\Laranail\EnvKit\Headless\Contracts\DoctorRuleInterface;
use Simtabi\Laranail\EnvKit\Headless\Document\EnvDocument;
use Simtabi\Laranail\Package\Tools\Services\Doctor\DoctorCheck;
use Simtabi\Laranail\Package\Tools\Services\Doctor\DoctorResult;
use Throwable;

/**
 * Adapter: exposes ONE bespoke {@see DoctorRuleInterface} through the standard
 * package-tools {@see DoctorCheck} surface. The bespoke env-kit doctor (its own
 * Doctor / DoctorCommand / DoctorRuleInterface) stays untouched — this only
 * bridges a rule's diagnostics onto a {@see DoctorResult} so env-kit's health
 * shows up under `php artisan laranail::package-tools.doctor` too.
 *
 * Mapping: any `error` diagnostic → FAIL, else any diagnostic → WARN, else PASS.
 * The .env document is resolved lazily (via the provider) when run() fires, so
 * the check reads the live file at doctor time, not at registration time.
 */
final readonly class BespokeRuleCheck implements DoctorCheck
{
    /**
     * @param  Closure(): EnvDocument  $document  lazy provider for the .env document
     */
    public function __construct(
        private DoctorRuleInterface $rule,
        private Closure $document,
        private string $name,
        private string $description,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function run(): DoctorResult
    {
        try {
            $diagnostics = $this->rule->check(($this->document)());
        } catch (Throwable $e) {
            return DoctorResult::skip('Could not read the .env document: '.$e->getMessage());
        }

        if ($diagnostics === []) {
            return DoctorResult::pass('No issues found.');
        }

        $messages = array_map(
            static fn (Diagnostic $d): string => $d->key !== null ? "[{$d->key}] {$d->message}" : $d->message,
            $diagnostics,
        );
        $summary = implode('; ', $messages);
        $detail = ['findings' => $messages];

        foreach ($diagnostics as $diagnostic) {
            if ($diagnostic->isError()) {
                return DoctorResult::fail($summary, $detail);
            }
        }

        return DoctorResult::warn($summary, $detail);
    }
}
