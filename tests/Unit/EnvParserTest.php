<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Contracts\EntryInterface;
use Simtabi\Laranail\EnvKit\Headless\Document\Entry\Comment;
use Simtabi\Laranail\EnvKit\Headless\Document\Entry\EmptyLine;
use Simtabi\Laranail\EnvKit\Headless\Document\EnvDocument;

/**
 * White-box access to the parsed entries. EnvDocument exposes setters/keys/render
 * but not the EmptyLine/Comment entries (nor a comment's stripped text), so these
 * are the only way to assert the parser's documented blank-vs-comment routing.
 *
 * @return list<EntryInterface>
 */
function parserEntries(EnvDocument $doc): array
{
    return (new ReflectionProperty(EnvDocument::class, 'entries'))->getValue($doc);
}

it('parses keys, values, export prefix and empty values', function () {
    $raw = implode("\n", [
        '# app config',
        'APP_NAME=Acme',
        'export APP_ENV="production"',
        'EMPTY=',
        'DB_PASSWORD="p@ss word#1"',
        '',
        'MAIL_HOST=smtp.example.com',
        'S3_BUCKET=my-bucket-1',
    ])."\n";

    $doc = EnvDocument::parse($raw);

    expect($doc->get('APP_NAME'))->toBe('Acme')
        ->and($doc->get('APP_ENV'))->toBe('production')
        ->and($doc->get('EMPTY'))->toBe('')
        ->and($doc->get('DB_PASSWORD'))->toBe('p@ss word#1')
        ->and($doc->get('MAIL_HOST'))->toBe('smtp.example.com')
        ->and($doc->get('S3_BUCKET'))->toBe('my-bucket-1') // keys with digits are allowed
        ->and($doc->has('NOPE'))->toBeFalse()
        ->and($doc->get('NOPE'))->toBeNull()
        ->and($doc->keys())->toBe(['APP_NAME', 'APP_ENV', 'EMPTY', 'DB_PASSWORD', 'MAIL_HOST', 'S3_BUCKET'])
        ->and($doc->toArray())->toHaveKey('APP_NAME', 'Acme');
});

it('updates and removes keys immutably', function () {
    $raw = "A=1\nB=2\n";
    $doc = EnvDocument::parse($raw);

    $updated = $doc->withValue('A', '99')->withValue('C', 'new');

    // original document is unchanged (immutability)
    expect($doc->get('A'))->toBe('1')
        ->and($doc->has('C'))->toBeFalse()
        ->and($updated->get('A'))->toBe('99')
        ->and($updated->get('C'))->toBe('new')
        ->and($updated->without('B')->has('B'))->toBeFalse();
});

it('strips and reproduces a UTF-8 BOM', function () {
    $bom = "\xEF\xBB\xBF";
    $raw = $bom."APP_NAME=Acme\n";

    $doc = EnvDocument::parse($raw);

    // BOM is stripped before parsing (key is clean, not "\u{FEFF}APP_NAME")
    expect($doc->hasBom())->toBeTrue()
        ->and($doc->keys())->toBe(['APP_NAME'])
        ->and($doc->get('APP_NAME'))->toBe('Acme')
        // round-trips byte-for-byte, BOM restored
        ->and($doc->render())->toBe($raw);
});

it('parses a file with no BOM', function () {
    $doc = EnvDocument::parse("APP_NAME=Acme\n");

    expect($doc->hasBom())->toBeFalse()
        ->and($doc->render())->toBe("APP_NAME=Acme\n");
});

it('detects and reproduces LF line endings', function () {
    $raw = "A=1\nB=2\n";
    $doc = EnvDocument::parse($raw);

    expect($doc->eol())->toBe("\n")
        ->and($doc->render())->toBe($raw);
});

it('detects and reproduces CRLF line endings', function () {
    $raw = "A=1\r\nB=2\r\n";
    $doc = EnvDocument::parse($raw);

    expect($doc->eol())->toBe("\r\n")
        // entries keep their original (EOL-less) text; render re-joins with CRLF
        ->and($doc->render())->toBe($raw);
});

it('breaks an EOL tie toward LF when CRLF count is not strictly greater', function () {
    // one CRLF line + one LF line: crlf == lf == 1, so the strict `>` keeps LF.
    $doc = EnvDocument::parse("A=1\r\nB=2\n");

    expect($doc->eol())->toBe("\n");
});

it('prefers CRLF only when it is the strict majority', function () {
    // two CRLF lines vs one LF line -> CRLF wins.
    $doc = EnvDocument::parse("A=1\r\nB=2\r\nC=3\n");

    expect($doc->eol())->toBe("\r\n");
});

it('preserves the absence of a trailing newline on render', function () {
    $raw = "A=1\nB=2";
    $doc = EnvDocument::parse($raw);

    expect($doc->hasTrailingNewline())->toBeFalse()
        ->and($doc->keys())->toBe(['A', 'B'])
        ->and($doc->get('B'))->toBe('2')
        ->and($doc->render())->toBe($raw);
});

it('preserves a trailing newline on render', function () {
    $raw = "A=1\nB=2\n";
    $doc = EnvDocument::parse($raw);

    expect($doc->hasTrailingNewline())->toBeTrue()
        ->and($doc->render())->toBe($raw);
});

it('preserves a trailing carriage return as a trailing newline', function () {
    $raw = "A=1\r\nB=2\r";
    $doc = EnvDocument::parse($raw);

    expect($doc->hasTrailingNewline())->toBeTrue();
});

it('does not flag a trailing newline on a final non-empty line', function () {
    // last split segment is "B=2" (non-empty) so no phantom blank entry is dropped/added.
    $doc = EnvDocument::parse("A=1\nB=2");

    expect($doc->setters())->toHaveCount(2)
        ->and($doc->render())->toBe("A=1\nB=2");
});

it('reads the export prefix on setters', function () {
    $doc = EnvDocument::parse("export FOO=bar\n");

    $setters = $doc->setters();

    expect($setters)->toHaveCount(1)
        ->and($setters[0]->key)->toBe('FOO')
        ->and($setters[0]->value)->toBe('bar')
        ->and($setters[0]->export)->toBeTrue()
        ->and($doc->render())->toBe("export FOO=bar\n");
});

it('does not treat leading whitespace as an export prefix', function () {
    // The export flag must come from capture group 2 (the `export ` token),
    // NOT group 1 (the leading indentation). An indented, un-exported setter.
    $doc = EnvDocument::parse("  FOO=bar\n");

    $setters = $doc->setters();

    expect($setters)->toHaveCount(1)
        ->and($setters[0]->key)->toBe('FOO')
        ->and($setters[0]->export)->toBeFalse()
        ->and($doc->render())->toBe("  FOO=bar\n");
});

it('parses a setter with surrounding whitespace and trims the value', function () {
    $doc = EnvDocument::parse("KEY = value  \n");

    expect($doc->get('KEY'))->toBe('value')
        ->and($doc->render())->toBe("KEY = value  \n");
});

it('preserves malformed lines verbatim without creating keys', function () {
    $raw = "VALID=1\nthis is not a setter\n=missingkey\nALSO=2\n";
    $doc = EnvDocument::parse($raw);

    expect($doc->keys())->toBe(['VALID', 'ALSO'])
        ->and($doc->has('this'))->toBeFalse()
        // malformed/unknown lines round-trip byte-for-byte
        ->and($doc->render())->toBe($raw);
});

it('preserves blank and comment lines on round-trip', function () {
    $raw = "# header\nA=1\n\n# mid comment\nB=2\n";
    $doc = EnvDocument::parse($raw);

    expect($doc->keys())->toBe(['A', 'B'])
        ->and($doc->render())->toBe($raw);
});

it('treats a leading-hash line as a comment, not a setter', function () {
    // `# NOPE=1` starts with '#', so it is a comment and NOPE is never a key.
    $doc = EnvDocument::parse("# NOPE=1\nYES=2\n");

    expect($doc->keys())->toBe(['YES'])
        ->and($doc->has('NOPE'))->toBeFalse()
        ->and($doc->render())->toBe("# NOPE=1\nYES=2\n");
});

it('keeps a setter whose value ends with a hash a setter', function () {
    // The comment check uses str_starts_with('#'), not str_ends_with: a trailing
    // '#' inside an unquoted value must NOT demote the line to a comment.
    $doc = EnvDocument::parse("KEY=val#\n");

    expect($doc->has('KEY'))->toBeTrue()
        ->and($doc->keys())->toBe(['KEY'])
        ->and($doc->get('KEY'))->toBe('val#')
        ->and($doc->render())->toBe("KEY=val#\n");
});

it('parses blank and whitespace-only lines as empty-line entries', function () {
    // A truly empty line AND a whitespace-only line both become EmptyLine entries
    // (not comments): trim($line) === '' must use trim, and must keep the early
    // return, otherwise these fall through to the comment/malformed branch.
    $doc = EnvDocument::parse("A=1\n\n   \nB=2\n");

    $entries = parserEntries($doc);

    expect($entries)->toHaveCount(4)
        ->and($entries[1])->toBeInstanceOf(EmptyLine::class)   // the '' line
        ->and($entries[2])->toBeInstanceOf(EmptyLine::class)   // the '   ' line
        ->and($entries[0])->not->toBeInstanceOf(EmptyLine::class)
        ->and($doc->render())->toBe("A=1\n\n   \nB=2\n");
});

it('strips the hash (and following whitespace) from comment text', function () {
    // The comment text is substr($trimmed, 1) with the leading whitespace ltrim'd:
    // exactly one '#' is removed and the remainder is left-trimmed. Pins down the
    // offset (1), the ltrim, and the comment-branch early return.
    $doc = EnvDocument::parse("# hello\n#hello\n#   hello\n   # hi\nA=1\n");

    $entries = parserEntries($doc);

    expect($entries[0])->toBeInstanceOf(Comment::class)
        ->and($entries[0]->text)->toBe('hello')   // "# hello"  -> "hello"
        ->and($entries[1]->text)->toBe('hello')   // "#hello"   -> "hello"
        ->and($entries[2]->text)->toBe('hello')   // "#   hello"-> "hello"
        ->and($entries[3]->text)->toBe('hi')      // "   # hi"  -> "hi"
        ->and($doc->render())->toBe("# hello\n#hello\n#   hello\n   # hi\nA=1\n");
});
