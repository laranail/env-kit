<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Session;

use Simtabi\Laranail\EnvKit\Headless\Contracts\WriterInterface;
use Simtabi\Laranail\EnvKit\Headless\Document\EnvDocument;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\IntegrityException;
use Simtabi\Laranail\EnvKit\Headless\Exceptions\KeyNotFoundException;
use Simtabi\Laranail\EnvKit\Headless\Writer\AtomicEnvWriter;
use Simtabi\Laranail\EnvKit\Headless\Writer\IntegrityVerifier;

/**
 * A transactional editing session over a single .env file.
 *
 * Stages mutations against an in-memory working document (reads reflect staged
 * changes — read-your-writes), then {@see save()} commits in one shot:
 * optimistic-lock check → atomic write → integrity verify → auto-rollback on
 * failure. A `save()` with no changes is a no-op (no write, no churn).
 *
 * Encryption, validation, audit and backups are layered onto the commit path in
 * later slices; this slice owns the durable, crash-safe write mechanics.
 */
final class EditSession
{
    private EnvDocument $working;

    public function __construct(
        private readonly string $path,
        private readonly EnvDocument $original,
        private readonly string $fingerprint,
        private readonly WriterInterface $writer,
        private readonly ConflictDetector $conflicts,
        private readonly IntegrityVerifier $verifier,
    ) {
        $this->working = $original;
    }

    /** Open a session for $path (an absent file starts as an empty document). */
    public static function open(string $path, ?WriterInterface $writer = null): self
    {
        $raw = is_file($path) ? (string) @file_get_contents($path) : '';
        $conflicts = new ConflictDetector;

        return new self(
            path: $path,
            original: EnvDocument::parse($raw),
            fingerprint: $conflicts->fingerprint($path),
            writer: $writer ?? new AtomicEnvWriter,
            conflicts: $conflicts,
            verifier: new IntegrityVerifier,
        );
    }

    public function get(string $key): ?string
    {
        return $this->working->get($key);
    }

    public function has(string $key): bool
    {
        return $this->working->has($key);
    }

    public function set(string $key, string $value, bool $export = false): self
    {
        $this->working = $this->working->withValue($key, $value, $export);

        return $this;
    }

    public function forget(string $key): self
    {
        $this->working = $this->working->without($key);

        return $this;
    }

    public function rename(string $from, string $to): self
    {
        if (! $this->working->has($from)) {
            throw KeyNotFoundException::for($from);
        }

        $this->working = $this->working->renamed($from, $to);

        return $this;
    }

    public function isDirty(): bool
    {
        return $this->working->render() !== $this->original->render();
    }

    /**
     * Key-level diff of staged changes.
     *
     * @return array<string, array{old: ?string, new: ?string}>
     */
    public function changes(): array
    {
        $before = $this->original->toArray();
        $after = $this->working->toArray();
        $changes = [];

        foreach ($after as $key => $value) {
            if (! \array_key_exists($key, $before) || $before[$key] !== $value) {
                $changes[$key] = ['old' => $before[$key] ?? null, 'new' => $value];
            }
        }

        foreach ($before as $key => $value) {
            if (! \array_key_exists($key, $after)) {
                $changes[$key] = ['old' => $value, 'new' => null];
            }
        }

        return $changes;
    }

    /** Abandon staged changes. */
    public function discard(): self
    {
        $this->working = $this->original;

        return $this;
    }

    /** The exact text that would be written. */
    public function preview(): string
    {
        return $this->working->render();
    }

    /**
     * Commit staged changes atomically. No-op when nothing changed. Throws
     * ConflictException (file changed underneath us) or IntegrityException
     * (post-write check failed → rolled back).
     */
    public function save(): self
    {
        if (! $this->isDirty()) {
            return $this;
        }

        $this->conflicts->ensureUnchanged($this->path, $this->fingerprint);

        $previous = is_file($this->path) ? (string) @file_get_contents($this->path) : null;

        $this->writer->write($this->path, $this->working->render());

        if (! $this->verifier->verify($this->path, $this->working)) {
            $this->rollback($previous);

            throw IntegrityException::for($this->path);
        }

        return $this;
    }

    private function rollback(?string $previous): void
    {
        if ($previous !== null) {
            $this->writer->write($this->path, $previous);
        } elseif (is_file($this->path)) {
            @unlink($this->path);
        }
    }
}
