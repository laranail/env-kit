<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Facades\EnvKit;
use Simtabi\Laranail\EnvKit\Headless\Tests\TestCase;
use Simtabi\Laranail\Console\Testing\InteractsWithConsole;
use Simtabi\Laranail\EnvKit\Headless\Contracts\EnvKitInterface;

uses(TestCase::class, InteractsWithConsole::class);

it('quits immediately from the editor', function () {
    $this->bindEnv("A=1\n");

    $this->artisan('laranail::env-kit.edit')
        ->expectsQuestion('Choose a key or action', 'Quit')
        ->expectsOutputToContain('Closed EnvKit editor.')
        ->doesntExpectOutputToContain('PRODUCTION')
        ->assertExitCode(0);
});

it('offers every key plus the add and quit actions, and a full per-key menu', function () {
    $this->bindEnv("A=1\nB=2\n", ['laranail.env-kit.auto_backup' => false]);

    $this->artisan('laranail::env-kit.edit')
        ->expectsChoice('Choose a key or action', 'A', ['A', 'B', '＋ Add a new key', 'Quit'])
        ->expectsChoice('Edit [A]', 'Back', ['Edit value', 'Rename', 'Delete', 'Back'])
        ->expectsQuestion('Choose a key or action', 'Quit')
        ->assertExitCode(0);
});

it('trims whitespace around a new key name', function () {
    $this->bindEnv("A=1\n", ['laranail.env-kit.auto_backup' => false]);

    $this->artisan('laranail::env-kit.edit')
        ->expectsQuestion('Choose a key or action', '＋ Add a new key')
        ->expectsQuestion('New key name', '  C  ')
        ->expectsQuestion('Value for C', '3') // prompt interpolates the already-trimmed key
        ->expectsQuestion('Choose a key or action', 'Quit')
        ->expectsOutputToContain('Set [C].')
        ->assertExitCode(0);

    expect(EnvKit::get('C'))->toBe('3');
});

it('edits a value through the interactive editor', function () {
    $this->bindEnv("APP_NAME=Acme\n", ['laranail.env-kit.auto_backup' => false]);

    $this->artisan('laranail::env-kit.edit')
        ->expectsQuestion('Choose a key or action', 'APP_NAME')
        ->expectsQuestion('Edit [APP_NAME]', 'Edit value')
        ->expectsQuestion('New value for [APP_NAME]', 'NewName')
        ->expectsQuestion('Choose a key or action', 'Quit')
        ->expectsOutputToContain('Updated [APP_NAME].')
        ->assertExitCode(0);

    expect(EnvKit::get('APP_NAME'))->toBe('NewName');
});

it('adds a new key through the editor', function () {
    $this->bindEnv("A=1\n", ['laranail.env-kit.auto_backup' => false]);

    $this->artisan('laranail::env-kit.edit')
        ->expectsQuestion('Choose a key or action', '＋ Add a new key')
        ->expectsQuestion('New key name', 'B')
        ->expectsQuestion('Value for B', '2')
        ->expectsQuestion('Choose a key or action', 'Quit')
        ->assertExitCode(0);

    expect(EnvKit::get('B'))->toBe('2');
});

it('deletes a key after confirmation', function () {
    $this->bindEnv("A=1\nB=2\n", ['laranail.env-kit.auto_backup' => false]);

    $this->artisan('laranail::env-kit.edit')
        ->expectsQuestion('Choose a key or action', 'B')
        ->expectsQuestion('Edit [B]', 'Delete')
        ->expectsConfirmation('Delete [B]?', 'yes')
        ->expectsQuestion('Choose a key or action', 'Quit')
        ->expectsOutputToContain('Removed [B].')
        ->assertExitCode(0);

    expect(EnvKit::has('B'))->toBeFalse();
});

it('surfaces an engine error without crashing the loop', function () {
    $this->bindEnv("A=1\n", ['laranail.env-kit.auto_backup' => false]);

    $this->artisan('laranail::env-kit.edit')
        ->expectsQuestion('Choose a key or action', '＋ Add a new key')
        ->expectsQuestion('New key name', '1bad') // invalid → engine rejects
        ->expectsQuestion('Value for 1bad', 'x')
        ->expectsQuestion('Choose a key or action', 'Quit')
        ->expectsOutputToContain('Invalid environment key: 1bad')
        ->assertExitCode(0);

    expect(EnvKit::has('1bad'))->toBeFalse();
});

it('persists multiple edits in production with --force-production', function () {
    $this->bindEnv("A=1\nB=2\n", ['laranail.env-kit.auto_backup' => false]);
    $this->app['env'] = 'production';
    $this->app->forgetInstance(EnvKitInterface::class);

    // Leaving the testing environment turns off runningUnitTests(), which is what
    // makes Laravel route prompts through its fakeable fallback. Without it the
    // command renders real prompts, which read STDIN and, with no TTY, fall back
    // to the empty default: select() then throws NonInteractiveValidationException.
    // The fallback flag is sticky (fallbackWhen ORs), so this only passed when an
    // earlier test had already set it -- and failed when run alone or reordered,
    // as the mutation job does. Set it here so the test stands on its own.
    $this->withPromptFallback();

    $this->artisan('laranail::env-kit.edit', ['--force-production' => true])
        ->expectsQuestion('Choose a key or action', 'A')
        ->expectsQuestion('Edit [A]', 'Edit value')
        ->expectsQuestion('New value for [A]', '10')
        ->expectsQuestion('Choose a key or action', 'B')
        ->expectsQuestion('Edit [B]', 'Edit value')
        ->expectsQuestion('New value for [B]', '20')
        ->expectsQuestion('Choose a key or action', 'Quit')
        ->assertExitCode(0);

    // Both edits persisted — the override is re-armed per action (not consumed once).
    expect(EnvKit::get('A'))->toBe('10')
        ->and(EnvKit::get('B'))->toBe('20');
});

it('exits with a usage error and names the non-interactive commands under --no-interaction', function () {
    $path = $this->bindEnv("A=1\n");

    $this->artisan('laranail::env-kit.edit', ['--no-interaction' => true])
        // One expectation: each written line satisfies at most one of them.
        ->expectsOutputToContain('laranail::env-kit.edit needs an interactive terminal. To change keys without one, use laranail::env-kit.set, laranail::env-kit.unset or laranail::env-kit.rename.')
        ->doesntExpectOutputToContain('Closed EnvKit editor.')
        ->assertExitCode(2);

    expect(file_get_contents($path))->toBe("A=1\n");
});

it('exits with a usage error, not "Required.", when run with no TTY', function () {
    if (DIRECTORY_SEPARATOR === '\\' || ! function_exists('proc_open')) {
        $this->markTestSkipped('Needs a POSIX shell with proc_open.');
    }

    $path = envkit_temp();
    file_put_contents($path, "A=1\n");
    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';

    // A real `artisan` run outside the test harness: stdin is /dev/null, so it is not a
    // terminal, and the app is not in the `testing` environment, so Laravel answers no
    // prompt on the user's behalf -- the shape of a cron job or a CI step.
    $worker = dirname($path) . '/edit-worker.php';
    file_put_contents($worker, <<<'PHP'
        <?php
        require $argv[1];
        putenv('APP_ENV=local');
        $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'local';
        $app = \Orchestra\Testbench\Foundation\Application::create(options: ['extra' => ['providers' => [
            \Simtabi\Laranail\EnvKit\Headless\Providers\EnvKitServiceProvider::class,
        ]]]);
        $app['config']->set('laranail.env-kit.backup_path', dirname($argv[2]) . '/backups');
        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
        $input = new \Symfony\Component\Console\Input\ArgvInput(['artisan', 'laranail::env-kit.edit', '--file=' . $argv[2]]);
        exit($kernel->handle($input, new \Symfony\Component\Console\Output\StreamOutput(STDOUT)));
        PHP);

    $process = proc_open(
        [PHP_BINARY, $worker, $autoload, $path],
        [['file', '/dev/null', 'r'], ['pipe', 'w'], ['pipe', 'w']],
        $pipes,
    );
    expect($process)->toBeResource();

    $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);

    expect($output)->toContain('laranail::env-kit.edit needs an interactive terminal')
        ->and($output)->toContain('laranail::env-kit.set, laranail::env-kit.unset or laranail::env-kit.rename')
        ->and($output)->not->toContain('Required.')
        ->and($status)->toBe(2)
        ->and(file_get_contents($path))->toBe("A=1\n");
});
