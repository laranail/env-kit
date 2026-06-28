<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Contracts;

use Illuminate\Support\Collection;

/**
 * The public, DI-injectable contract for the EnvKit root service — the single
 * surface the `EnvKit` facade, constructor injection, and the `env_kit()` helper
 * all resolve to.
 *
 * This declares the stable READ API (per §3A). The write/session/backup/schema
 * methods are layered on in later build slices as their behaviour is implemented;
 * this interface grows additively and never breaks the read surface.
 */
interface EnvKitInterface
{
    /** Read a key's logical value (or $default when absent). */
    public function get(string $key, mixed $default = null): mixed;

    public function getString(string $key, ?string $default = null): ?string;

    public function getBool(string $key, ?bool $default = null): ?bool;

    public function getInt(string $key, ?int $default = null): ?int;

    public function getFloat(string $key, ?float $default = null): ?float;

    /**
     * @param  array<int|string, mixed>|null  $default
     * @return array<int|string, mixed>|null
     */
    public function getArray(string $key, ?array $default = null): ?array;

    public function getJson(string $key, mixed $default = null): mixed;

    public function has(string $key): bool;

    public function missing(string $key): bool;

    /** @return array<string, string> */
    public function all(): array;

    /** @return list<string> */
    public function keys(): array;

    /**
     * @param  list<string>  $keys
     * @return array<string, string>
     */
    public function only(array $keys): array;

    /**
     * @param  list<string>  $keys
     * @return array<string, string>
     */
    public function except(array $keys): array;

    /**
     * Keys (and values) sharing a `PREFIX_` group, e.g. group('MAIL').
     *
     * @return array<string, string>
     */
    public function group(string $prefix): array;

    /** The full raw file contents. */
    public function raw(): string;

    /** `${VAR}`-resolved value for a key (brace form only, resolve-on-read). */
    public function interpolated(string $key, mixed $default = null): mixed;

    /**
     * Entry metadata (comments/export flags) as a collection.
     *
     * @return Collection<int, EntryInterface>
     */
    public function entries(): Collection;
}
