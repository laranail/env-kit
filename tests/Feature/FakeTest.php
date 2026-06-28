<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Contracts\EnvKitInterface;
use Simtabi\Laranail\EnvKit\Headless\Facades\EnvKit;
use Simtabi\Laranail\EnvKit\Headless\Testing\EnvKitFake;
use Simtabi\Laranail\EnvKit\Headless\Tests\TestCase;

uses(TestCase::class);

it('records mutations and answers reads from memory', function () {
    $fake = EnvKit::fake(['APP_NAME' => 'Acme', 'DEBUG' => 'true']);

    expect(EnvKit::get('APP_NAME'))->toBe('Acme')
        ->and(EnvKit::getBool('DEBUG'))->toBeTrue()
        ->and(EnvKit::has('APP_NAME'))->toBeTrue();

    EnvKit::set('NEW', 'val');
    EnvKit::forget('APP_NAME');

    expect(EnvKit::get('NEW'))->toBe('val');
    $fake->assertSet('NEW', 'val');
    $fake->assertForgotten('APP_NAME');
});

it('touches no disk when faked', function () {
    $path = $this->bindEnv("A=1\n");

    EnvKit::fake();
    EnvKit::set('B', '2');

    expect(file_get_contents($path))->toBe("A=1\n"); // the real file is untouched
});

it('is resolvable via DI and the helper after faking', function () {
    EnvKit::fake(['K' => 'v']);

    expect(app(EnvKitInterface::class))->toBeInstanceOf(EnvKitFake::class)
        ->and(env_kit('K'))->toBe('v');
});
