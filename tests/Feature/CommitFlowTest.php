<?php

declare(strict_types=1);

use Closure;
use Simtabi\Laranail\EnvKit\Headless\Backup\BackupManager;
use Simtabi\Laranail\EnvKit\Headless\Document\EnvDocument;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\InvalidKeyException;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\ProductionGuardException;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\ProtectedKeyException;
use Simtabi\Laranail\EnvKit\Headless\Pipeline\CommitContext;
use Simtabi\Laranail\EnvKit\Headless\Pipeline\CommitPipeline;
use Simtabi\Laranail\EnvKit\Headless\Security\ProductionGuard;
use Simtabi\Laranail\EnvKit\Headless\Security\ProtectedKeys;
use Simtabi\Laranail\EnvKit\Headless\Session\EditSession;

it('blocks a write in production and leaves the file untouched', function () {
    $path = envkit_temp();
    file_put_contents($path, "A=1\n");

    $production = fn () => CommitPipeline::default(production: new ProductionGuard(true));

    expect(fn () => EditSession::open($path, pipeline: $production())->set('A', '2')->save())
        ->toThrow(ProductionGuardException::class)
        ->and(file_get_contents($path))->toBe("A=1\n");

    // ...but an explicit opt-in is allowed.
    EditSession::open($path, pipeline: $production())->allowProduction()->set('A', '2')->save();
    expect(EnvDocument::parse(file_get_contents($path))->get('A'))->toBe('2');
});

it('refuses to write a protected key', function () {
    $path = envkit_temp();
    file_put_contents($path, "APP_KEY=base64:secret\nA=1\n");

    $pipeline = CommitPipeline::default(protected: new ProtectedKeys(['APP_KEY']));

    expect(fn () => EditSession::open($path, pipeline: $pipeline)->set('APP_KEY', 'new')->save())
        ->toThrow(ProtectedKeyException::class)
        ->and(file_get_contents($path))->toContain('APP_KEY=base64:secret');
});

it('rejects an invalid key at commit time', function () {
    $path = envkit_temp();
    file_put_contents($path, "A=1\n");

    expect(fn () => EditSession::open($path)->set('1bad', 'x')->save())
        ->toThrow(InvalidKeyException::class);
});

it('creates an auto-backup of the pre-write file', function () {
    $path = envkit_temp();
    file_put_contents($path, "A=1\n");
    $dir = dirname($path).'/backups';

    EditSession::open($path, pipeline: CommitPipeline::default(backups: new BackupManager($dir)))
        ->set('A', '2')
        ->save();

    $backups = glob($dir.'/*.bak') ?: [];
    expect($backups)->toHaveCount(1)
        ->and(file_get_contents($backups[0]))->toBe("A=1\n");   // snapshot is the OLD content
    expect(EnvDocument::parse(file_get_contents($path))->get('A'))->toBe('2');
});

it('runs consumer middleware pushed into the pipeline', function () {
    $path = envkit_temp();
    file_put_contents($path, "A=1\n");

    $spy = new class
    {
        public int $calls = 0;

        public function handle(CommitContext $context, Closure $next): mixed
        {
            $this->calls++;

            return $next($context);
        }
    };

    EditSession::open($path, pipeline: CommitPipeline::default()->push($spy))->set('A', '2')->save();

    expect($spy->calls)->toBe(1);
});
