<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Support;

/**
 * Casts raw string env values to typed PHP values (net-new — no source package
 * offers typed getters). A null raw (missing key) returns the caller's default.
 */
final class TypedAccessor
{
    public function string(?string $raw, ?string $default): ?string
    {
        return $raw ?? $default;
    }

    public function bool(?string $raw, ?bool $default): ?bool
    {
        if ($raw === null) {
            return $default;
        }

        return match (strtolower(trim($raw))) {
            'true', '1', 'yes', 'on' => true,
            'false', '0', 'no', 'off', '' => false,
            default => (bool) $raw,
        };
    }

    public function int(?string $raw, ?int $default): ?int
    {
        return ($raw !== null && is_numeric(trim($raw))) ? (int) trim($raw) : $default;
    }

    public function float(?string $raw, ?float $default): ?float
    {
        return ($raw !== null && is_numeric(trim($raw))) ? (float) trim($raw) : $default;
    }

    /**
     * @param  array<int|string, mixed>|null  $default
     * @return array<int|string, mixed>|null
     */
    public function array(?string $raw, ?array $default): ?array
    {
        if ($raw === null) {
            return $default;
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return $raw === '' ? [] : array_map(trim(...), explode(',', $raw));
    }

    public function json(?string $raw, mixed $default): mixed
    {
        if ($raw === null) {
            return $default;
        }

        try {
            return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $default;
        }
    }
}
