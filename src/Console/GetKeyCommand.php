<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Console;

use Simtabi\Laranail\EnvKit\Headless\EnvKit;

final class GetKeyCommand extends AbstractEnvCommand
{
    /** @var string */
    protected $signature = 'laranail::env-kit-headless.get
        {key : the key to read}
        {--file= : operate on a custom .env file}
        {--default= : value to print when the key is absent}';

    /** @var string */
    protected $description = 'Read a single environment value.';

    /** @var list<string> */
    protected array $commandAliases = ['env:get'];

    public function handle(EnvKit $env): int
    {
        return $this->runSafely(function () use ($env): int {
            $default = $this->option('default');
            $value = $this->targetEnv($env)->getString(
                $this->stringArgument('key'),
                is_string($default) ? $default : null,
            );

            if ($value !== null) {
                $this->line($value);
            }

            return self::EXIT_OK;
        });
    }
}
