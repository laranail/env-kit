<?php

declare(strict_types=1);

use Dotenv\Parser\Parser as PhpDotenvParser;
use Simtabi\Laranail\EnvKit\Headless\Document\EnvDocument;

dataset('fixtures', [
    'lf' => ["# header\nAPP_NAME=Acme\nexport APP_ENV=production\nEMPTY=\nQUOTED=\"a b#c\"\n\nLAST=1\n"],
    'crlf' => ["A=1\r\nB=2\r\n"],
    'no-trailing-newline' => ["A=1\nB=2"],
    'bom' => ["\xEF\xBB\xBF"."A=1\nB=2\n"],
    'blank-only' => ["\n"],
    'comments-and-blanks' => ["# one\n\n#two\nK=v\n"],
    'empty' => [''],
]);

it('round-trips byte-for-byte', function (string $raw) {
    expect(EnvDocument::parse($raw)->render())->toBe($raw);
})->with('fixtures');

it('preserves line-ending and BOM metadata', function () {
    $doc = EnvDocument::parse("\xEF\xBB\xBF"."A=1\r\nB=2\r\n");

    expect($doc->eol())->toBe("\r\n")
        ->and($doc->hasBom())->toBeTrue();
});

it('re-encodes changed lines while leaving others byte-identical', function () {
    $raw = "# keep\nA=1\nB=plain\n";
    $rendered = EnvDocument::parse($raw)->withValue('B', 'now has spaces')->render();

    expect($rendered)->toBe("# keep\nA=1\nB=\"now has spaces\"\n");
});

it('emits output that vlucas/phpdotenv parses to the same values', function () {
    $raw = "APP_NAME=Acme\nexport APP_ENV=\"production\"\nDB_PASSWORD=\"p@ss word#1\"\n";
    $doc = EnvDocument::parse($raw);

    $map = [];
    foreach ((new PhpDotenvParser)->parse($doc->render()) as $entry) {
        $value = $entry->getValue();
        $map[$entry->getName()] = $value->isDefined() ? $value->get()->getChars() : null;
    }

    expect($map['APP_NAME'])->toBe('Acme')
        ->and($map['APP_ENV'])->toBe('production')
        ->and($map['DB_PASSWORD'])->toBe('p@ss word#1');
});
