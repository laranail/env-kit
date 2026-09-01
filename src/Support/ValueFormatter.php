<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Support;

/**
 * Encodes/decodes the value side of a `KEY=value` line per the §3B spec, so our
 * output round-trips through vlucas/phpdotenv. Stateless and deterministic.
 */
final class ValueFormatter
{
    /** Escape sequences inside a double-quoted value (encode direction). */
    private const ENCODE_MAP = [
        '\\' => '\\\\',
        '"' => '\\"',
        '$' => '\\$',
        "\n" => '\\n',
        "\r" => '\\r',
        "\t" => '\\t',
    ];

    /** Inverse of ENCODE_MAP (decode direction). */
    private const DECODE_MAP = [
        '\\\\' => '\\',
        '\\"' => '"',
        '\\$' => '$',
        '\\n' => "\n",
        '\\r' => "\r",
        '\\t' => "\t",
        '\\f' => "\f",
        '\\v' => "\v",
    ];

    /**
     * Does this value require double-quoting on write?
     *
     * Quote when it contains whitespace, a `#` (would start an inline comment),
     * quotes/backtick, `$` (interpolation), a backslash, or has leading/trailing
     * whitespace. A bare `=` does NOT need quoting — dotenv splits a line on the
     * FIRST `=` only, so `URL=https://h/db?a=1` is unambiguous unquoted.
     */
    public static function needsQuoting(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        if ($value !== trim($value)) {
            return true;
        }

        return (bool) preg_match('/[\s#"\'`$\\\\]/', $value);
    }

    /** Encode a logical value into the raw RHS of a `KEY=` line. */
    public static function encode(string $value, bool $alwaysQuote = false): string
    {
        if ($value === '') {
            return '';
        }

        if (! $alwaysQuote && ! self::needsQuoting($value)) {
            return $value;
        }

        return '"'.strtr($value, self::ENCODE_MAP).'"';
    }

    /** Decode the raw RHS of a `KEY=` line back into a logical value. */
    public static function decode(string $raw): string
    {
        $raw = ltrim($raw);

        if ($raw === '') {
            return '';
        }

        $quote = $raw[0];

        if (($quote === '"' || $quote === "'") && ($end = strrpos($raw, $quote)) > 0) {
            $inner = substr($raw, 1, $end - 1);

            return $quote === '"' ? strtr($inner, self::DECODE_MAP) : $inner;
        }

        // Unquoted: an inline ` # comment` ends the value; trailing space is trimmed.
        $value = preg_replace('/\s+#.*$/s', '', $raw) ?? $raw;

        return rtrim($value);
    }
}
