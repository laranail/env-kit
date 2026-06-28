<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Audit;

/**
 * An immutable record of one commit. `$changes` carry key names with
 * ALREADY-REDACTED values — a raw secret never reaches a sink or a listener.
 */
final class AuditEvent
{
    /** @param list<array{key: string, old: ?string, new: ?string}> $changes */
    public function __construct(
        public readonly string $path,
        public readonly array $changes,
        public readonly ?string $actor,
        public readonly int $occurredAt,
    ) {}

    /** @return array{path: string, actor: ?string, occurred_at: int, changes: list<array{key: string, old: ?string, new: ?string}>} */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'actor' => $this->actor,
            'occurred_at' => $this->occurredAt,
            'changes' => $this->changes,
        ];
    }
}
