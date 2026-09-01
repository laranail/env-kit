<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Console;

use Simtabi\Laranail\EnvKit\Headless\EnvKit;

final class DoctorCommand extends AbstractEnvCommand
{
    /** @var string */
    protected $signature = 'laranail::env-kit.doctor {--file= : operate on a custom .env file}';

    /** @var string */
    protected $description = 'Run health checks over the .env file.';

    /** @var list<string> */
    protected array $commandAliases = ['env:doctor'];

    public function handle(EnvKit $env): int
    {
        return $this->runSafely(function () use ($env): int {
            $diagnostics = $this->targetEnv($env)->inspect();

            if ($diagnostics === []) {
                $this->info('No issues found.');

                return self::EXIT_OK;
            }

            $hasError = false;
            foreach ($diagnostics as $diagnostic) {
                $line = $diagnostic->key !== null
                    ? "[{$diagnostic->key}] {$diagnostic->message}"
                    : $diagnostic->message;

                match ($diagnostic->severity) {
                    'error' => $this->error($line),
                    'warning' => $this->warn($line),
                    default => $this->line($line),
                };

                $hasError = $hasError || $diagnostic->isError();
            }

            return $hasError ? self::EXIT_VALIDATION : self::EXIT_OK;
        });
    }
}
