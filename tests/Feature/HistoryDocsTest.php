<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Simtabi\Laranail\EnvKit\Headless\Audit\HistoryReader;
use Simtabi\Laranail\EnvKit\Headless\Contracts\EnvKitInterface;
use Simtabi\Laranail\EnvKit\Headless\Facades\EnvKit;
use Simtabi\Laranail\EnvKit\Headless\Schema\EnvSchema;
use Simtabi\Laranail\EnvKit\Headless\Support\DocsGenerator;
use Simtabi\Laranail\EnvKit\Headless\Tests\TestCase;

uses(TestCase::class);

function envkitAuditDir(): string
{
    $dir = sys_get_temp_dir().'/envkit-hist-'.bin2hex(random_bytes(5));
    @mkdir($dir, 0777, true);

    return $dir;
}

it('records changes and reads them back most-recent-first', function () {
    $dir = envkitAuditDir();
    file_put_contents($dir.'/.env', "A=1\n");
    config([
        'env-kit.path' => $dir.'/.env',
        'env-kit.audit.path' => $dir.'/audit.log',
        'env-kit.audit.enabled' => true,
        'env-kit.auto_backup' => false,
    ]);
    $this->app->forgetInstance(EnvKitInterface::class);

    EnvKit::set('B', '2');
    EnvKit::set('C', '3');

    $entries = (new HistoryReader($dir.'/audit.log'))->recent(10);

    expect($entries)->toHaveCount(2)
        ->and($entries[0]['changes'][0]['key'])->toBe('C')  // newest first (list of {key,old,new})
        ->and($entries[1]['changes'][0]['key'])->toBe('B');
});

it('HistoryReader returns empty for a missing log', function () {
    expect((new HistoryReader('/no/such/envkit-audit.log'))->recent())->toBe([]);
});

it('HistoryReader returns empty for an unreadable log', function () {
    $dir = envkitAuditDir();
    $log = $dir.'/audit.log';
    file_put_contents($log, json_encode(['action' => 'set']).PHP_EOL);
    chmod($log, 0o000);

    set_error_handler(static fn (): bool => true); // swallow the expected read warning

    try {
        expect((new HistoryReader($log))->recent())->toBe([]);
    } finally {
        restore_error_handler();
        chmod($log, 0o644);
    }
});

it('HistoryReader clamps a non-positive limit to one entry and skips trailing junk', function () {
    $dir = envkitAuditDir();
    $log = $dir.'/audit.log';
    file_put_contents($log, json_encode(['key' => 'A']).PHP_EOL.'not-json'.PHP_EOL);

    $entries = (new HistoryReader($log))->recent(0);

    expect($entries)->toHaveCount(1)
        ->and($entries[0]['key'])->toBe('A');
});

it('the env:history command tables changes and reports an empty log', function () {
    $dir = envkitAuditDir();
    file_put_contents($dir.'/.env', "A=1\n");
    config([
        'env-kit.path' => $dir.'/.env',
        'env-kit.audit.path' => $dir.'/audit.log',
        'env-kit.audit.enabled' => true,
        'env-kit.auto_backup' => false,
    ]);
    $this->app->forgetInstance(EnvKitInterface::class);
    EnvKit::set('NEW_FLAG', '2');

    $this->artisan('env:history')
        ->expectsOutputToContain('Keys changed')
        ->expectsOutputToContain('NEW_FLAG') // shows the key NAME, not an index
        ->assertExitCode(0);

    config(['env-kit.audit.path' => $dir.'/empty.log']);
    $this->artisan('env:history')->expectsOutputToContain('No audit history')->assertExitCode(0);
});

it('DocsGenerator renders the schema as a markdown table', function () {
    $schema = (new EnvSchema)->required('APP_KEY')->in('APP_ENV', ['local', 'production'])->integer('PORT');

    $markdown = (new DocsGenerator)->generate($schema);

    expect($markdown)->toContain('# Environment schema')
        ->toContain('`APP_KEY`')->toContain('required')
        ->toContain('one of: local, production')
        ->toContain('`PORT`')->toContain('integer');
});

it('DocsGenerator renders a placeholder when no schema is defined', function () {
    expect((new DocsGenerator)->generate(new EnvSchema))->toContain('No schema rules');
});

it('the env:docs command prints markdown and writes to a file', function () {
    $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false]);
    EnvKit::schema()->required('A');

    $this->artisan('env:docs')->expectsOutputToContain('Environment schema')->assertExitCode(0);

    $out = sys_get_temp_dir().'/envkit-docs-'.bin2hex(random_bytes(5)).'.md';
    $this->artisan('env:docs', ['--output' => $out])->expectsOutputToContain('Wrote')->assertExitCode(0);
    expect((string) file_get_contents($out))->toContain('`A`');
    @unlink($out);

    // an empty --output still streams to stdout instead of writing a file
    $this->artisan('env:docs', ['--output' => ''])
        ->expectsOutputToContain('Environment schema')
        ->doesntExpectOutputToContain('Wrote')
        ->assertExitCode(0);
});

it('env:history renders when, actor and changed keys, tolerating malformed entries', function () {
    $dir = envkitAuditDir();
    file_put_contents($dir.'/.env', "A=1\n");
    $log = $dir.'/audit.log';
    file_put_contents($log, implode(PHP_EOL, [
        json_encode(['occurred_at' => 'later', 'actor' => 7, 'changes' => 'none']),
        json_encode(['occurred_at' => 1700000000, 'actor' => 'alice', 'changes' => [['key' => 'A'], 'junk', ['label' => 'no-key'], ['key' => 'B']]]),
    ]).PHP_EOL);
    config(['env-kit.path' => $dir.'/.env', 'env-kit.audit.path' => $log]);

    expect(Artisan::call('env:history'))->toBe(0);

    $when = date('Y-m-d H:i:s', 1700000000);
    $output = Artisan::output();
    expect($output)->toContain('When')
        ->toContain('Actor')
        ->toContain('Keys changed')
        // the well-formed entry renders as a full row, junk change entries filtered out
        ->toMatch('~\| '.preg_quote($when, '~').' \| alice \| A, B *\|~')
        // the malformed entry renders as placeholders with an empty keys cell
        ->toMatch('~\| — *\| — *\| *\|~');
});

it('env:history defaults to exactly 20 entries when --limit is not numeric', function () {
    $dir = envkitAuditDir();
    file_put_contents($dir.'/.env', "A=1\n");
    $log = $dir.'/audit.log';
    $lines = [];
    for ($i = 1; $i <= 22; $i++) {
        $lines[] = json_encode(['occurred_at' => 1700000000 + $i, 'actor' => 'a', 'changes' => [['key' => sprintf('K%02d', $i)]]]);
    }
    file_put_contents($log, implode(PHP_EOL, $lines).PHP_EOL);
    config(['env-kit.path' => $dir.'/.env', 'env-kit.audit.path' => $log]);

    expect(Artisan::call('env:history', ['--limit' => 'lots']))->toBe(0);

    $output = Artisan::output();
    expect($output)->toContain('K03')    // the 20th-newest entry is included…
        ->not->toContain('K02');         // …but the 21st is not
});

it('env:history honours a numeric --limit, newest first', function () {
    $dir = envkitAuditDir();
    file_put_contents($dir.'/.env', "A=1\n");
    $log = $dir.'/audit.log';
    file_put_contents($log, implode(PHP_EOL, [
        json_encode(['occurred_at' => 1700000001, 'actor' => 'a', 'changes' => [['key' => 'KA']]]),
        json_encode(['occurred_at' => 1700000002, 'actor' => 'a', 'changes' => [['key' => 'KB']]]),
        json_encode(['occurred_at' => 1700000003, 'actor' => 'a', 'changes' => [['key' => 'KC']]]),
    ]).PHP_EOL);
    config(['env-kit.path' => $dir.'/.env', 'env-kit.audit.path' => $log]);

    expect(Artisan::call('env:history', ['--limit' => '2']))->toBe(0);

    $output = Artisan::output();
    expect($output)->toContain('KC')
        ->toContain('KB')
        ->not->toContain('KA');
});
