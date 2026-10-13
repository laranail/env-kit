<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Console;

use Closure;
use Simtabi\Laranail\EnvKit\Headless\EnvKit;
use Simtabi\Laranail\Console\Prompter\Prompter;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\EnvKitException;

/**
 * The interactive TUI (`env:edit`): a browse/edit loop on laranail/console's
 * {@see Prompter}, which wraps laravel/prompts. Pure orchestration — every mutation goes
 * through the same {@see EnvKit} engine, so atomicity/guards/audit/events all
 * apply. Engine errors are shown inline and the loop keeps running.
 *
 * Without a terminal to prompt on it exits with {@see self::EXIT_USAGE} and names
 * the non-interactive commands, rather than letting the first prompt fail.
 */
final class EditCommand extends AbstractEnvCommand
{
    private const ADD = '＋ Add a new key';

    private const QUIT = 'Quit';

    /** @var string */
    protected $signature = 'laranail::env-kit.edit
        {--file= : operate on a custom .env file}
        {--force-production : allow writes in production}';

    /** @var string */
    protected $description = 'Interactively browse and edit the .env file.';

    private bool $force = false;

    public function handle(EnvKit $env): int
    {
        if (! Prompter::canPrompt($this->input)) {
            return $this->failWith(
                'laranail::env-kit.edit needs an interactive terminal. To change keys without one, use '
                . 'laranail::env-kit.set, laranail::env-kit.unset or laranail::env-kit.rename.',
                self::EXIT_USAGE,
            );
        }

        $this->force = (bool) $this->option('force-production');
        $target = $this->targetEnv($env);

        if ($this->laravel->environment('production')) {
            $this->warn('⚠  You are editing a PRODUCTION environment.');
        }

        while (true) {
            $choice = $this->choose('Choose a key or action', [...$target->keys(), self::ADD, self::QUIT]);

            if ($choice === self::QUIT) {
                break;
            }

            $this->guard(fn () => $choice === self::ADD ? $this->addKey($target) : $this->editKey($target, $choice));
        }

        $this->info('Closed EnvKit editor.');

        return self::EXIT_OK;
    }

    /**
     * One menu choice. A fresh {@see Prompter} per prompt, so one answer never
     * stands in for another.
     *
     * @param list<string> $options
     */
    private function choose(string $label, array $options): string
    {
        $choice = Prompter::create()->select($label, $options)->getResult();

        return is_string($choice) || is_int($choice) ? (string) $choice : '';
    }

    /** One line of text, through a fresh {@see Prompter}. */
    private function askText(string $label, bool $required = false, string $default = ''): string
    {
        $answer = Prompter::create()->text($label, default: $default, required: $required)->getResult();

        return is_string($answer) ? $answer : '';
    }

    /** A yes/no question that defaults to no, through a fresh {@see Prompter}. */
    private function confirmNo(string $label): bool
    {
        return Prompter::create()->confirm($label, default: false)->getResult() === true;
    }

    /** Re-arm the production override before each mutation (each commit consumes it). */
    private function allowing(EnvKit $env): EnvKit
    {
        if ($this->force) {
            $env->allowProduction();
        }

        return $env;
    }

    private function addKey(EnvKit $env): void
    {
        $key = trim($this->askText('New key name', required: true));
        $this->allowing($env)->set($key, $this->askText("Value for {$key}"));
        $this->info("Set [{$key}].");
    }

    private function editKey(EnvKit $env, string $key): void
    {
        match ($this->choose("Edit [{$key}]", ['Edit value', 'Rename', 'Delete', 'Back'])) {
            'Edit value' => $this->editValue($env, $key),
            'Rename'     => $this->renameKey($env, $key),
            'Delete'     => $this->deleteKey($env, $key),
            default      => null,
        };
    }

    private function editValue(EnvKit $env, string $key): void
    {
        $this->allowing($env)->set($key, $this->askText("New value for [{$key}]", default: $env->getString($key) ?? ''));
        $this->info("Updated [{$key}].");
    }

    private function renameKey(EnvKit $env, string $key): void
    {
        $to = trim($this->askText("Rename [{$key}] to", required: true));
        $this->allowing($env)->rename($key, $to);
        $this->info("Renamed [{$key}] to [{$to}].");
    }

    private function deleteKey(EnvKit $env, string $key): void
    {
        if ($this->confirmNo("Delete [{$key}]?")) {
            $this->allowing($env)->forget($key);
            $this->info("Removed [{$key}].");
        }
    }

    /** Run a menu action, surfacing engine errors inline without breaking the loop. */
    private function guard(Closure $action): void
    {
        try {
            $action();
        } catch (EnvKitException $e) {
            $this->error($e->getMessage()); // secret-safe: messages carry key names, never values
        }
    }
}
