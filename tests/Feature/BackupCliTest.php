<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Facades\EnvKit;
use Simtabi\Laranail\EnvKit\Headless\Tests\TestCase;

uses(TestCase::class);

it('creates a backup with env:backup', function () {
    $path = $this->bindEnv("A=1\n");

    $this->artisan('laranail::env-kit.backup')->assertExitCode(0);

    expect(glob(dirname($path) . '/backups/*.bak'))->toHaveCount(1);
});

it('lists backups with env:backups', function () {
    $this->bindEnv("A=1\n");

    $this->artisan('laranail::env-kit.backup')->assertExitCode(0);
    $this->artisan('laranail::env-kit.backups')->expectsOutputToContain('bytes')->assertExitCode(0);
});

it('restores the latest backup with env:restore', function () {
    $this->bindEnv("A=1\n", ['laranail.env-kit.auto_backup' => false]);

    $this->artisan('laranail::env-kit.backup')->assertExitCode(0); // snapshot the A=1 state
    EnvKit::set('B', '2');
    expect(EnvKit::has('B'))->toBeTrue();

    $this->artisan('laranail::env-kit.restore')->assertExitCode(0); // back to A=1

    expect(EnvKit::has('B'))->toBeFalse()
        ->and(EnvKit::get('A'))->toBe('1');
});

it('fails env:restore when no backups exist', function () {
    $this->bindEnv("A=1\n");

    $this->artisan('laranail::env-kit.restore')->assertExitCode(3);
});

it('passes env:validate on a clean file', function () {
    $this->bindEnv("APP_NAME=Acme\nDEBUG=true\n");

    $this->artisan('laranail::env-kit.validate')->assertExitCode(0);
});

it('flags an unsafe value with env:validate (exit 3)', function () {
    $this->bindEnv("GOOD=1\nBAD=a\x01b\n");

    $this->artisan('laranail::env-kit.validate')->assertExitCode(3);
});

it('reports the backup and restore file names', function () {
    $this->bindEnv("A=1\n", ['laranail.env-kit.auto_backup' => false]);

    $this->artisan('laranail::env-kit.backup')->expectsOutputToContain('Backed up to [')->assertExitCode(0);
    $this->artisan('laranail::env-kit.restore')->expectsOutputToContain('Restored from [')->assertExitCode(0);
});

it('prunes backups older than N days via --older-than', function () {
    $path = $this->bindEnv("A=1\n", ['laranail.env-kit.auto_backup' => false]);

    $this->artisan('laranail::env-kit.backup')->assertExitCode(0);
    $backup = glob(dirname($path) . '/backups/*.bak')[0];
    touch($backup, time() - 3_600); // an hour old

    $this->artisan('laranail::env-kit.backup-delete', ['--older-than' => '0'])
        ->expectsOutputToContain('Deleted 1 backup(s) older than 0 day(s).')
        ->assertExitCode(0);

    expect(glob(dirname($path) . '/backups/*.bak'))->toBe([]);
});

it('demands a backup name when neither a name nor --older-than is usable', function () {
    $this->bindEnv("A=1\n", ['laranail.env-kit.auto_backup' => false]);

    $this->artisan('laranail::env-kit.backup-delete', ['name' => ''])
        ->expectsOutputToContain('Provide a backup name, or --older-than=DAYS.')
        ->assertExitCode(2);
});

it('env:validate reports the number of valid entries', function () {
    $this->bindEnv("A=1\nB=2\n");

    $this->artisan('laranail::env-kit.validate')
        ->expectsOutputToContain('All 2 entries are valid.')
        ->assertExitCode(0);
});
