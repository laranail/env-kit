<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Contracts\WriterInterface;
use Simtabi\Laranail\EnvKit\Headless\Document\EnvDocument;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\ConflictException;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\IntegrityException;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\KeyNotFoundException;
use Simtabi\Laranail\EnvKit\Headless\Session\EditSession;

it('reads-your-writes while staging, and persists on save', function () {
    $path = envkit_temp();
    file_put_contents($path, "A=1\nB=2\n");

    $session = EditSession::open($path)->set('A', '99')->set('C', 'new');

    // staged reads reflect uncommitted changes
    expect($session->get('A'))->toBe('99')
        ->and($session->get('C'))->toBe('new')
        // ...but the file on disk is untouched until save()
        ->and(file_get_contents($path))->toBe("A=1\nB=2\n");

    $session->save();

    $reloaded = EnvDocument::parse(file_get_contents($path));
    expect($reloaded->get('A'))->toBe('99')
        ->and($reloaded->get('C'))->toBe('new')
        ->and($reloaded->get('B'))->toBe('2');
});

it('is a no-op when nothing changed (no write, no churn)', function () {
    $path = envkit_temp();
    file_put_contents($path, "A=1\n");

    $spy = new class implements WriterInterface
    {
        public int $calls = 0;

        public function write(string $path, string $contents): void
        {
            $this->calls++;
        }
    };

    EditSession::open($path, $spy)->save();

    expect($spy->calls)->toBe(0);
});

it('renames a key in place, preserving surrounding lines', function () {
    $path = envkit_temp();
    file_put_contents($path, "# header\nA=1\nB=2\n");

    EditSession::open($path)->rename('A', 'RENAMED')->save();

    expect(file_get_contents($path))->toBe("# header\nRENAMED=1\nB=2\n");
});

it('throws when renaming an absent key', function () {
    $path = envkit_temp();
    file_put_contents($path, "A=1\n");

    EditSession::open($path)->rename('NOPE', 'X');
})->throws(KeyNotFoundException::class);

it('refuses to clobber a file changed underneath it', function () {
    $path = envkit_temp();
    file_put_contents($path, "A=1\n");

    $session = EditSession::open($path)->set('A', '2');

    // another process edits the file after we opened it
    file_put_contents($path, "A=999\n");

    $session->save();
})->throws(ConflictException::class);

it('rolls back and throws when post-write verification fails', function () {
    $path = envkit_temp();
    file_put_contents($path, "A=1\n");

    // corrupts on the first write (the commit), writes honestly on the second (the rollback)
    $flaky = new class implements WriterInterface
    {
        public int $calls = 0;

        public function write(string $path, string $contents): void
        {
            $this->calls++;
            file_put_contents($path, $this->calls === 1 ? "CORRUPT broken line\n" : $contents);
        }
    };

    $session = EditSession::open($path, $flaky)->set('A', '2');

    expect(fn () => $session->save())->toThrow(IntegrityException::class);

    // the rollback restored the original content (second write)
    expect(file_get_contents($path))->toBe("A=1\n")
        ->and($flaky->calls)->toBe(2);
});
