<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Console;

use Simtabi\Laranail\EnvKit\Headless\EnvKit;

final class KeysCommand extends AbstractEnvCommand
{
    /** @var string */
    protected $signature = 'laranail::env-kit.keys {--file= : operate on a custom .env file}';

    /** @var string */
    protected $description = 'List all environment keys.';

    /** @var list<string> */
    protected array $commandAliases = ['env:keys'];

    public function handle(EnvKit $env): int
    {
        return $this->runSafely(function () use ($env): int {
            foreach ($this->targetEnv($env)->keys() as $key) {
                $this->line($key);
            }

            return self::EXIT_OK;
        });
    }
}
