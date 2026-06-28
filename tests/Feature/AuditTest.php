<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Simtabi\Laranail\EnvKit\Headless\Audit\AuditEvent;
use Simtabi\Laranail\EnvKit\Headless\Audit\FileAuditSink;
use Simtabi\Laranail\EnvKit\Headless\Contracts\AuditSinkInterface;
use Simtabi\Laranail\EnvKit\Headless\Events\AfterWrite;
use Simtabi\Laranail\EnvKit\Headless\Facades\EnvKit;
use Simtabi\Laranail\EnvKit\Headless\Tests\TestCase;

uses(TestCase::class);

it('writes a redacted JSON-lines audit record on commit', function () {
    $path = $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false, 'env-kit.audit.enabled' => true]);
    $auditPath = dirname($path).'/audit.log';

    EnvKit::set('DB_PASSWORD', 'topsecret123');

    expect(is_file($auditPath))->toBeTrue();

    $lines = file($auditPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $last = (string) end($lines);
    $record = json_decode($last, true);

    expect($last)->not->toContain('topsecret123') // the raw secret never reaches the log
        ->and($record['changes'][0]['key'])->toBe('DB_PASSWORD')
        ->and($record['changes'][0]['new'])->not->toBe('topsecret123');
});

it('dispatches an AfterWrite event carrying redacted changes', function () {
    Event::fake();
    $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false]);

    EnvKit::set('API_TOKEN', 'abc123');

    Event::assertDispatched(AfterWrite::class, function (AfterWrite $event): bool {
        return $event->changes[0]['key'] === 'API_TOKEN'
            && $event->changes[0]['new'] !== 'abc123'; // masked (API_TOKEN matches *_TOKEN)
    });
});

it('fans out to a sink registered via configure()', function () {
    $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false]);

    $spy = new class implements AuditSinkInterface
    {
        public int $records = 0;

        public function record(AuditEvent $event): void
        {
            $this->records++;
        }
    };

    EnvKit::configure()->registerAuditSink($spy);
    EnvKit::set('B', '2');

    expect($spy->records)->toBe(1);
});

it('does not audit a no-op (unchanged) write', function () {
    $path = $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false, 'env-kit.audit.enabled' => true]);
    $auditPath = dirname($path).'/audit.log';

    EnvKit::set('A', '1'); // same value → no-op, no commit, no audit

    expect(is_file($auditPath))->toBeFalse();
});

it('keeps the FileAuditSink output parseable as JSON lines', function () {
    $path = $this->bindEnv("A=1\n");
    $auditPath = dirname($path).'/audit.log';

    $sink = new FileAuditSink($auditPath);
    $sink->record(new AuditEvent($path, [['key' => 'X', 'old' => null, 'new' => '1']], 'tester', 1700000000));
    $sink->record(new AuditEvent($path, [['key' => 'Y', 'old' => '1', 'new' => '2']], null, 1700000001));

    $lines = file($auditPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    expect($lines)->toHaveCount(2)
        ->and(json_decode($lines[0], true)['actor'])->toBe('tester')
        ->and(json_decode($lines[1], true)['changes'][0]['key'])->toBe('Y');
});
