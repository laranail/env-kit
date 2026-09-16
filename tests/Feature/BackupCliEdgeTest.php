<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Tests\TestCase;
use Simtabi\Laranail\EnvKit\Headless\Contracts\EnvKitInterface;

uses(TestCase::class);

it('warns when there is nothing to back up (no .env file)', function () {
    $dir = sys_get_temp_dir() . '/envkit-nb-' . bin2hex(random_bytes(5));
    @mkdir($dir, 0777, true);
    config(['laranail.env-kit.path' => $dir . '/.env', 'laranail.env-kit.backup_path' => $dir . '/backups']);
    $this->app->forgetInstance(EnvKitInterface::class);

    $this->artisan('laranail::env-kit.backup')
        ->expectsOutputToContain('Nothing to back up')
        ->assertExitCode(0);
});

it('reports when there are no backups to list', function () {
    $dir = sys_get_temp_dir() . '/envkit-eb-' . bin2hex(random_bytes(5));
    @mkdir($dir, 0777, true);
    file_put_contents($dir . '/.env', "A=1\n");
    config([
        'laranail.env-kit.path'        => $dir . '/.env',
        'laranail.env-kit.backup_path' => $dir . '/no-backups-here',
        'laranail.env-kit.auto_backup' => false,
    ]);
    $this->app->forgetInstance(EnvKitInterface::class);

    $this->artisan('laranail::env-kit.backups')
        ->expectsOutputToContain('No backups found')
        ->assertExitCode(0);
});
