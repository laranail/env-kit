<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Console;

use Closure;

use function is_array;

use Illuminate\Console\Command;
use Simtabi\Laranail\EnvKit\Headless\EnvKit;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\LockException;
use Simtabi\Laranail\EnvKit\Headless\Security\ProductionBanner;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\EnvKitException;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\ConflictException;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\IntegrityException;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\FileNotWritableException;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;

/**
 * Base for the EnvKit Artisan commands. Provides the `laranail::env-kit.*`
 * primary name (via {@see SupportsNamespacedNames}) plus `env:*` short aliases,
 * the documented exit-code contract, and `--file` resolution. Commands stay thin:
 * parse flags → call the same {@see EnvKit} engine the programmatic API uses.
 */
abstract class AbstractEnvCommand extends Command
{
    use SupportsNamespacedNames;

    protected const int EXIT_OK = 0;

    protected const int EXIT_USAGE = 2;

    protected const int EXIT_VALIDATION = 3;

    protected const int EXIT_CONFLICT = 4;

    protected const int EXIT_IO = 5;

    public function __construct()
    {
        parent::__construct();

        $aliases = $this->declaredCommandAliases();

        if ($aliases !== []) {
            $this->setAliases($aliases);
        }
    }

    /** Resolve the target engine, honouring the `--file` option when present. */
    protected function targetEnv(EnvKit $env): EnvKit
    {
        $file = $this->option('file');
        $env = (\is_string($file) && $file !== '') ? $env->file($file) : $env;

        if ($this->hasOption('force-production') && $this->option('force-production')) {
            $env->allowProduction();
        }

        return $env;
    }

    /**
     * Run an action, mapping EnvKit exceptions to the exit-code contract.
     * Exception messages are secret-safe (they carry key names, never values).
     *
     * @param Closure(): int $action
     */
    protected function runSafely(Closure $action): int
    {
        if ($this->laravel->environment('production')) {
            $this->warn(ProductionBanner::line());
        }

        try {
            return $action();
        } catch (ConflictException $e) {
            return $this->failWith($e->getMessage(), self::EXIT_CONFLICT);
        } catch (FileNotWritableException|LockException|IntegrityException $e) {
            return $this->failWith($e->getMessage(), self::EXIT_IO);
        } catch (EnvKitException $e) {
            return $this->failWith($e->getMessage(), self::EXIT_VALIDATION);
        }
    }

    protected function failWith(string $message, int $code): int
    {
        $this->error($message);

        return $code;
    }

    /** A string console argument (empty string when absent/non-scalar). */
    protected function stringArgument(string $name): string
    {
        $value = $this->argument($name);

        return is_string($value) ? $value : '';
    }

    /** The --format option, falling back to $default. */
    protected function formatOption(string $default = 'json'): string
    {
        $format = $this->option('format');

        return is_string($format) && $format !== '' ? $format : $default;
    }

    /**
     * This command's own `$commandAliases`, if it declares one.
     *
     * Read here rather than through the console trait: the trait arrives from
     * `vendor/laranail/console` at the moving `v0.1.0` tag, so relying on a
     * method added there would make this package red until that tag moves.
     * Reading defensively is also what makes an undeclared property safe -- and
     * every declaration in this package was removed with its bare `env:*`
     * aliases, which is precisely when an unguarded read starts throwing.
     *
     * @return list<string>
     */
    private function declaredCommandAliases(): array
    {
        if (! property_exists($this, 'commandAliases') || ! is_array($this->commandAliases)) {
            return [];
        }

        return array_values(array_filter(
            $this->commandAliases,
            static fn (mixed $alias): bool => \is_string($alias) && $alias !== '',
        ));
    }
}
