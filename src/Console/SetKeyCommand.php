<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Console;

use Simtabi\Laranail\EnvKit\Headless\EnvKit;

final class SetKeyCommand extends AbstractEnvCommand
{
    /** @var string */
    protected $signature = 'laranail::env-kit-headless.set
        {key : KEY, or KEY=VALUE shorthand}
        {value? : the value (omit when using KEY=VALUE)}
        {--file= : operate on a custom .env file}
        {--export : write the value with an export prefix}
        {--force-production : allow the write in production}';

    /** @var string */
    protected $description = 'Set or create an environment key.';

    /** @var list<string> */
    protected array $commandAliases = ['env:set'];

    public function handle(EnvKit $env): int
    {
        return $this->runSafely(function () use ($env): int {
            $key = $this->stringArgument('key');
            $rawValue = $this->argument('value');
            $value = is_string($rawValue) ? $rawValue : null;

            if (str_contains($key, '=')) {
                if ($value !== null) {
                    return $this->failWith('Use either `KEY VALUE` or `KEY=VALUE`, not both.', self::EXIT_USAGE);
                }

                [$key, $value] = explode('=', $key, 2);
            }

            $this->targetEnv($env)->set($key, $value ?? '', ['export' => (bool) $this->option('export')]);
            $this->info("Set [{$key}].");

            return self::EXIT_OK;
        });
    }
}
