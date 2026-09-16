<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Tests\TestCase;
use Simtabi\Laranail\EnvKit\Headless\Security\ProductionBanner;

uses(TestCase::class);

it('exposes a single warning line', function () {
    expect(ProductionBanner::line())->toBe(ProductionBanner::MESSAGE)
        ->and(ProductionBanner::line())->toContain('PRODUCTION');
});

it('shows the production banner on CLI commands in production', function () {
    $this->bindEnv("A=1\n", ['laranail.env-kit.auto_backup' => false]);
    $this->app['env'] = 'production';

    $this->artisan('laranail::env-kit.keys')
        ->expectsOutputToContain('PRODUCTION')
        ->assertExitCode(0);
});

it('shows no banner outside production', function () {
    $this->bindEnv("A=1\n", ['laranail.env-kit.auto_backup' => false]);

    $this->artisan('laranail::env-kit.keys')
        ->doesntExpectOutputToContain('PRODUCTION')
        ->assertExitCode(0);
});
