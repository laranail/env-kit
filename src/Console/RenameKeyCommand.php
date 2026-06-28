<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Console;

use Simtabi\Laranail\EnvKit\Headless\EnvKit;

final class RenameKeyCommand extends AbstractEnvCommand
{
    /** @var string */
    protected $signature = 'laranail::env-kit-headless.rename
        {from : current key name}
        {to : new key name}
        {--file= : operate on a custom .env file}
        {--force-production : allow the write in production}';

    /** @var string */
    protected $description = 'Rename an environment key in place.';

    /** @var list<string> */
    protected array $commandAliases = ['env:rename'];

    public function handle(EnvKit $env): int
    {
        return $this->runSafely(function () use ($env): int {
            $from = $this->stringArgument('from');
            $to = $this->stringArgument('to');
            $this->targetEnv($env)->rename($from, $to);
            $this->info("Renamed [{$from}] to [{$to}].");

            return self::EXIT_OK;
        });
    }
}
