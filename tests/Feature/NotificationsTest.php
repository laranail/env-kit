<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\AnonymousNotifiable;
use Simtabi\Laranail\EnvKit\Headless\Facades\EnvKit;
use Simtabi\Laranail\EnvKit\Headless\Tests\TestCase;
use Simtabi\Laranail\EnvKit\Headless\Extension\EnvKitConfigurator;
use Simtabi\Laranail\EnvKit\Headless\Notifications\EnvKitEventNotification;

uses(TestCase::class);

function enableEnvKitNotifications(): array
{
    return [
        'laranail.env-kit.auto_backup'                   => false,
        'laranail.env-kit.notifications.enabled'         => true,
        'laranail.env-kit.notifications.channels'        => ['mail'],
        'laranail.env-kit.notifications.routes'          => ['mail' => 'ops@example.com'],
        'laranail.env-kit.notifications.events'          => ['after_write'],
        'laranail.env-kit.notifications.production_only' => false,
    ];
}

it('sends an on-demand notification on a configured event', function () {
    $this->bindEnv("A=1\n", enableEnvKitNotifications());
    Notification::fake();

    EnvKit::set('A', '2');

    Notification::assertSentOnDemand(
        EnvKitEventNotification::class,
        fn (EnvKitEventNotification $n) => $n->summary['event'] === 'after_write',
    );
});

it('sends nothing when disabled', function () {
    $this->bindEnv("A=1\n", ['laranail.env-kit.auto_backup' => false, 'laranail.env-kit.notifications.enabled' => false]);
    Notification::fake();

    EnvKit::set('A', '2');

    Notification::assertNothingSent();
});

it('suppresses notifications outside production when production_only', function () {
    $this->bindEnv("A=1\n", [...enableEnvKitNotifications(), 'laranail.env-kit.notifications.production_only' => true]);
    Notification::fake();

    EnvKit::set('A', '2'); // tests do not run as production

    Notification::assertNothingSent();
});

it('skips a channel whose route target is null', function () {
    $this->bindEnv("A=1\n", [...enableEnvKitNotifications(), 'laranail.env-kit.notifications.routes' => ['mail' => null]]);
    Notification::fake();

    EnvKit::set('A', '2');

    Notification::assertNothingSent();
});

it('summarizes the event with redacted, attributed content', function () {
    $this->bindEnv("WIDGET_PASSWORD=old\n", enableEnvKitNotifications());
    app(EnvKitConfigurator::class)->resolveActorUsing(fn () => 'alice');
    Notification::fake();

    EnvKit::set('WIDGET_PASSWORD', 'newsecret');

    Notification::assertSentOnDemand(
        EnvKitEventNotification::class,
        function (EnvKitEventNotification $n): bool {
            return $n->summary['event'] === 'after_write'
                && $n->summary['actor'] === 'alice'
                && $n->summary['changes'][0]['new'] === '••••••'
                && $n->via(new AnonymousNotifiable) === ['mail']
                && $n->toArray(new AnonymousNotifiable) === $n->summary;
        },
    );
});

it('notifies each routed channel and includes the rejection reason', function () {
    $this->bindEnv("A=1\n", [
        ...enableEnvKitNotifications(),
        'laranail.env-kit.notifications.channels' => ['mail', 'slack'],
        'laranail.env-kit.notifications.routes'   => ['mail' => 'ops@example.com', 'slack' => 'https://hooks.example/x'],
        'laranail.env-kit.notifications.events'   => ['write_rejected'],
        'laranail.env-kit.protected_keys'         => ['LOCKED'],
    ]);
    Notification::fake();

    try {
        EnvKit::set('LOCKED', 'x');
    } catch (Throwable) {
        // expected — we only care that the rejection notified
    }

    Notification::assertSentOnDemandTimes(EnvKitEventNotification::class, 2); // one per channel
    Notification::assertSentOnDemand(
        EnvKitEventNotification::class,
        fn (EnvKitEventNotification $n): bool => $n->summary['event'] === 'write_rejected' && $n->summary['reason'] === 'protected',
    );
});
