<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Console;

use Simtabi\Laranail\EnvKit\Headless\EnvKit;

final class DiffCommand extends AbstractEnvCommand
{
    /** @var string */
    protected $signature = 'laranail::env-kit.diff
        {against : the other .env file to compare against}
        {--file= : operate on a custom .env file}';

    /** @var string */
    protected $description = 'Compare the .env file against another, by key.';

    /** @var list<string> */
    protected array $commandAliases = ['env:diff'];

    public function handle(EnvKit $env): int
    {
        return $this->runSafely(function () use ($env): int {
            $against = $this->stringArgument('against');
            $diff = $this->targetEnv($env)->diff($against);

            $clean = true;
            foreach ($diff['only_here'] as $key) {
                $this->line("+ {$key} (only here)");
                $clean = false;
            }
            foreach ($diff['only_there'] as $key) {
                $this->line("- {$key} (only in {$against})");
                $clean = false;
            }
            foreach ($diff['changed'] as $key) {
                $this->line("~ {$key} (value differs)");
                $clean = false;
            }

            if ($clean) {
                $this->info('No differences.');
            }

            return self::EXIT_OK;
        });
    }
}
