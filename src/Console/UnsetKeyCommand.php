<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Console;

use Simtabi\Laranail\EnvKit\Headless\EnvKit;

final class UnsetKeyCommand extends AbstractEnvCommand
{
    /** @var string */
    protected $signature = 'laranail::env-kit.unset
        {key : the key to remove}
        {--file= : operate on a custom .env file}
        {--force-production : allow the write in production}';

    /** @var string */
    protected $description = 'Remove an environment key.';

    /** @var list<string> */
    protected array $commandAliases = ['env:unset'];

    public function handle(EnvKit $env): int
    {
        return $this->runSafely(function () use ($env): int {
            $key = $this->stringArgument('key');
            $this->targetEnv($env)->forget($key);
            $this->info("Removed [{$key}].");

            return self::EXIT_OK;
        });
    }
}
