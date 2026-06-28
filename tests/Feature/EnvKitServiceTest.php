<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Contracts\EnvKitInterface;
use Simtabi\Laranail\EnvKit\Headless\Facades\EnvKit;
use Simtabi\Laranail\EnvKit\Headless\Tests\TestCase;

uses(TestCase::class);

it('reads via the facade, DI, and the env_kit() helper', function () {
    $this->bindEnv("APP_NAME=Acme\nDEBUG=true\nPORT=8080\n");

    // facade + typed getters
    expect(EnvKit::get('APP_NAME'))->toBe('Acme')
        ->and(EnvKit::getBool('DEBUG'))->toBeTrue()
        ->and(EnvKit::getInt('PORT'))->toBe(8080)
        ->and(EnvKit::get('MISSING', 'def'))->toBe('def');

    // constructor DI of the contract
    expect(app(EnvKitInterface::class)->getString('APP_NAME'))->toBe('Acme');

    // helper (read shortcut + accessor)
    expect(env_kit('APP_NAME'))->toBe('Acme')
        ->and(env_kit('MISSING', 'fallback'))->toBe('fallback')
        ->and(env_kit())->toBeInstanceOf(EnvKitInterface::class);
});

it('writes immediately under auto_commit', function () {
    $path = $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false]);

    EnvKit::set('B', 'two');

    expect(EnvKit::get('B'))->toBe('two')
        ->and(file_get_contents($path))->toContain('B=two');
});

it('commits a batch as one transaction', function () {
    $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false]);

    EnvKit::transaction(function ($session) {
        $session->set('B', '2')->set('C', '3');
    });

    expect(EnvKit::get('B'))->toBe('2')
        ->and(EnvKit::get('C'))->toBe('3');
});

it('supports group / only / except / interpolated reads', function () {
    $this->bindEnv(
        "MAIL_HOST=smtp\nMAIL_PORT=587\nAPP_NAME=Acme\n".'URL=${MAIL_HOST}:${MAIL_PORT}'."\n"
    );

    expect(EnvKit::group('MAIL'))->toBe(['MAIL_HOST' => 'smtp', 'MAIL_PORT' => '587'])
        ->and(EnvKit::only(['APP_NAME']))->toBe(['APP_NAME' => 'Acme'])
        ->and(EnvKit::except(['MAIL_HOST', 'MAIL_PORT', 'URL']))->toBe(['APP_NAME' => 'Acme'])
        ->and(EnvKit::interpolated('URL'))->toBe('smtp:587');
});

it('takes an auto-backup before an immediate write', function () {
    $path = $this->bindEnv("A=1\n"); // auto_backup defaults to true

    EnvKit::set('A', '2');

    expect(glob(dirname($path).'/backups/*.bak'))->toHaveCount(1)
        ->and(EnvKit::get('A'))->toBe('2');
});
