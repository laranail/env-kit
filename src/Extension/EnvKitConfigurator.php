<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Extension;

use Simtabi\Laranail\EnvKit\Headless\Contracts\AuditSinkInterface;
use Simtabi\Laranail\EnvKit\Headless\Contracts\WriterInterface;
use Simtabi\Laranail\EnvKit\Headless\EnvKit;

/**
 * The fluent registration DSL (`EnvKit::configure()->…`). A bound singleton a
 * consumer drives from their OWN service provider to reshape EnvKit at runtime —
 * extra protected keys, a custom writer, mutation-pipeline middleware, additional
 * audit sinks, ad-hoc macros — with zero edits to package source (Open/Closed,
 * §2A). EnvKit reads this state when it builds each commit pipeline.
 */
final class EnvKitConfigurator
{
    /** @var list<object> */
    private array $mutationMiddleware = [];

    /** @var list<string> */
    private array $protectedKeys = [];

    private ?WriterInterface $writer = null;

    /** @var list<AuditSinkInterface> */
    private array $auditSinks = [];

    /** Append a pipe to the commit pipeline (runs after the built-in guards, before write). */
    public function pushMutationMiddleware(object $pipe): self
    {
        $this->mutationMiddleware[] = $pipe;

        return $this;
    }

    /** @param list<string> $keys */
    public function protectKeys(array $keys): self
    {
        foreach ($keys as $key) {
            $this->protectedKeys[] = $key;
        }

        return $this;
    }

    /** Swap the writer strategy (e.g. wrap it in a decorator). */
    public function useWriter(WriterInterface $writer): self
    {
        $this->writer = $writer;

        return $this;
    }

    /** Register an additional audit destination (events fan out to all sinks). */
    public function registerAuditSink(AuditSinkInterface $sink): self
    {
        $this->auditSinks[] = $sink;

        return $this;
    }

    /** Add a fluent method to EnvKit without subclassing (Macroable). */
    public function macro(string $name, callable $macro): self
    {
        EnvKit::macro($name, $macro);

        return $this;
    }

    /** @return list<object> */
    public function mutationMiddleware(): array
    {
        return $this->mutationMiddleware;
    }

    /** @return list<string> */
    public function protectedKeys(): array
    {
        return $this->protectedKeys;
    }

    public function writer(): ?WriterInterface
    {
        return $this->writer;
    }

    /** @return list<AuditSinkInterface> */
    public function auditSinks(): array
    {
        return $this->auditSinks;
    }
}
