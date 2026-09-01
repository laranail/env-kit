<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Document;

use Simtabi\Laranail\EnvKit\Headless\Contracts\EntryInterface;
use Simtabi\Laranail\EnvKit\Headless\Document\Entry\Comment;
use Simtabi\Laranail\EnvKit\Headless\Document\Entry\EmptyLine;
use Simtabi\Laranail\EnvKit\Headless\Document\Entry\Setter;
use Simtabi\Laranail\EnvKit\Headless\Support\ValueFormatter;

/**
 * Parses raw .env text into an immutable {@see EnvDocument}, capturing the
 * document's line-ending, BOM and trailing-newline so the writer can reproduce
 * the file faithfully. Keys may contain digits after the first char (unlike
 * some sources that reject e.g. `S3_BUCKET`).
 */
final class EnvParser
{
    private const BOM = "\xEF\xBB\xBF";

    private const SETTER = '/^(\s*)(export\s+)?([A-Za-z_][A-Za-z0-9_]*)\s*=(.*)$/s';

    public function parse(string $raw): EnvDocument
    {
        $hasBom = str_starts_with($raw, self::BOM);
        if ($hasBom) {
            $raw = substr($raw, strlen(self::BOM));
        }

        $crlf = substr_count($raw, "\r\n");
        $lf = substr_count($raw, "\n") - $crlf;
        $eol = $crlf > $lf ? "\r\n" : "\n";

        $trailingNewline = $raw !== '' && (str_ends_with($raw, "\n") || str_ends_with($raw, "\r"));

        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [''];
        if ($trailingNewline && end($lines) === '') {
            array_pop($lines);
        }

        $entries = array_map($this->parseLine(...), $lines);

        return new EnvDocument($entries, $eol, $hasBom, $trailingNewline);
    }

    private function parseLine(string $line): EntryInterface
    {
        if (trim($line) === '') {
            return new EmptyLine($line);
        }

        $trimmed = ltrim($line);
        if (str_starts_with($trimmed, '#')) {
            return new Comment(ltrim(substr($trimmed, 1)), $line);
        }

        if (preg_match(self::SETTER, $line, $m) === 1) {
            return new Setter(
                key: $m[3],
                value: ValueFormatter::decode($m[4]),
                export: $m[2] !== '',
                original: $line,
            );
        }

        // Malformed/unknown line: preserve verbatim (lossless); doctor flags it.
        return new Comment($trimmed, $line);
    }
}
