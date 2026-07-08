<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Simtabi\Laranail\EnvKit\Headless\Contracts\EnvKitInterface;
use Simtabi\Laranail\EnvKit\Headless\Document\EnvDocument;
use Simtabi\Laranail\EnvKit\Headless\Tests\TestCase;

uses(TestCase::class);

it('sets a key via the env:set alias and the namespaced name', function () {
    $path = $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false]);

    $this->artisan('env:set', ['key' => 'NEW', 'value' => 'val'])->assertExitCode(0);
    $this->artisan('laranail::env-kit.set', ['key' => 'B', 'value' => '2'])->assertExitCode(0);

    $doc = EnvDocument::parse(file_get_contents($path));
    expect($doc->get('NEW'))->toBe('val')
        ->and($doc->get('B'))->toBe('2');
});

it('accepts KEY=VALUE shorthand and rejects the mixed form', function () {
    $path = $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false]);

    $this->artisan('env:set', ['key' => 'FOO=bar'])->assertExitCode(0);
    expect(EnvDocument::parse(file_get_contents($path))->get('FOO'))->toBe('bar');

    // both KEY VALUE and KEY=VALUE at once → usage error (exit 2)
    $this->artisan('env:set', ['key' => 'X=1', 'value' => '2'])->assertExitCode(2);
});

it('rejects an invalid key with the validation exit code', function () {
    $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false]);

    $this->artisan('env:set', ['key' => '1bad', 'value' => 'x'])->assertExitCode(3);
});

it('reads a value with env:get (and a default)', function () {
    $this->bindEnv("APP_NAME=Acme\n");

    $this->artisan('env:get', ['key' => 'APP_NAME'])->expectsOutput('Acme')->assertExitCode(0);
    $this->artisan('env:get', ['key' => 'MISSING', '--default' => 'fallback'])
        ->expectsOutput('fallback')
        ->assertExitCode(0);
});

it('removes a key with env:unset', function () {
    $path = $this->bindEnv("A=1\nB=2\n", ['env-kit.auto_backup' => false]);

    $this->artisan('env:unset', ['key' => 'A'])
        ->expectsOutputToContain('Removed [A].')
        ->assertExitCode(0);

    expect(EnvDocument::parse(file_get_contents($path))->has('A'))->toBeFalse();
});

it('renames a key with env:rename', function () {
    $path = $this->bindEnv("OLD=1\n", ['env-kit.auto_backup' => false]);

    $this->artisan('env:rename', ['from' => 'OLD', 'to' => 'NEW'])
        ->expectsOutputToContain('Renamed [OLD] to [NEW].')
        ->assertExitCode(0);

    expect(EnvDocument::parse(file_get_contents($path))->get('NEW'))->toBe('1');
});

it('lists keys, and masks secret values unless --reveal', function () {
    $this->bindEnv("APP_NAME=Acme\nDB_PASSWORD=topsecret123\n");

    $this->artisan('env:keys')->expectsOutputToContain('APP_NAME')->assertExitCode(0);

    $this->artisan('env:list')
        ->expectsOutputToContain('APP_NAME=Acme')
        ->doesntExpectOutputToContain('topsecret123')
        ->assertExitCode(0);

    $this->artisan('env:list', ['--reveal' => true])
        ->expectsOutputToContain('topsecret123')
        ->assertExitCode(0);
});

it('env:set splits KEY=VALUE on the first equals only and confirms the write', function () {
    $path = $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false]);

    $this->artisan('env:set', ['key' => 'URL=https://ex.test/?a=1'])
        ->expectsOutputToContain('Set [URL].')
        ->assertExitCode(0);

    expect(EnvDocument::parse((string) file_get_contents($path))->get('URL'))->toBe('https://ex.test/?a=1');
});

it('env:set with a bare KEY writes an empty value', function () {
    $path = $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false]);

    $this->artisan('env:set', ['key' => 'EMPTYK'])->assertExitCode(0);

    expect(EnvDocument::parse((string) file_get_contents($path))->get('EMPTYK'))->toBe('');
});

it('env:set --export writes the export prefix', function () {
    $path = $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false]);

    $this->artisan('env:set', ['key' => 'EX', 'value' => '1', '--export' => true])->assertExitCode(0);

    expect((string) file_get_contents($path))->toContain('export EX=1');
});

it('operates on an alternate file with --file and falls back to the default for an empty --file', function () {
    $path = $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false]);
    $alt = dirname($path).'/.env.alt';
    file_put_contents($alt, "B=2\n");

    $this->artisan('env:list', ['--file' => $alt])
        ->expectsOutputToContain('B=2')
        ->doesntExpectOutputToContain('A=1')
        ->assertExitCode(0);

    $this->artisan('env:list', ['--file' => ''])
        ->expectsOutputToContain('A=1')
        ->assertExitCode(0);
});

it('blocks env:set in production without --force-production and allows it with the flag', function () {
    $path = $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false]);
    $this->app['env'] = 'production';
    $this->app->forgetInstance(EnvKitInterface::class);

    $this->artisan('env:set', ['key' => 'A', 'value' => '2'])
        ->expectsOutputToContain('Refusing to modify the .env file in production.')
        ->assertExitCode(3);
    expect(EnvDocument::parse((string) file_get_contents($path))->get('A'))->toBe('1');

    $this->artisan('env:set', ['key' => 'A', 'value' => '2', '--force-production' => true])->assertExitCode(0);
    expect(EnvDocument::parse((string) file_get_contents($path))->get('A'))->toBe('2');
});

it('env:export honours --format, falls back to json for an empty one, and reports --output writes', function () {
    $path = $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false]);

    $this->artisan('env:export', ['--format' => 'csv'])
        ->expectsOutputToContain('KEY,VALUE')
        ->assertExitCode(0);

    $this->artisan('env:export', ['--format' => ''])
        ->expectsOutputToContain('"A"')
        ->doesntExpectOutputToContain('KEY,VALUE')
        ->assertExitCode(0);

    $out = dirname($path).'/export.json';
    $this->artisan('env:export', ['--output' => $out])
        ->expectsOutputToContain("Exported to [{$out}].")
        ->assertExitCode(0);
    expect((string) file_get_contents($out))->toContain('"A"');

    // an empty --output still streams to stdout
    $this->artisan('env:export', ['--output' => ''])
        ->expectsOutputToContain('"A"')
        ->doesntExpectOutputToContain('Exported to')
        ->assertExitCode(0);
});

it('env:import applies a json source and reports it', function () {
    $path = $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false]);
    $src = dirname($path).'/values.json';
    file_put_contents($src, (string) json_encode(['NEW' => 'imported']));

    $this->artisan('env:import', ['source' => $src])
        ->expectsOutputToContain("Imported from [{$src}].")
        ->assertExitCode(0);

    expect(EnvDocument::parse((string) file_get_contents($path))->get('NEW'))->toBe('imported');
});

it('env:generate honours --bytes and writes the value with --set', function () {
    $path = $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false]);

    $this->artisan('env:generate', ['--bytes' => '8', '--set' => 'TOKEN'])
        ->expectsOutputToContain('Generated and wrote [TOKEN].')
        ->assertExitCode(0);

    expect((string) EnvDocument::parse((string) file_get_contents($path))->get('TOKEN'))
        ->toMatch('/^[0-9a-f]{16}$/'); // 8 bytes, hex-encoded
});

it('env:generate prints the value when --set is empty, without writing anything', function () {
    $path = $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false]);

    expect(Artisan::call('env:generate', ['--bytes' => '8', '--set' => '']))->toBe(0)
        ->and(trim(Artisan::output()))->toMatch('/^[0-9a-f]{16}$/')
        ->and((string) file_get_contents($path))->toBe("A=1\n"); // untouched
});

it('env:check and env:sync honour --example and fall back to the sibling example when empty', function () {
    $path = $this->bindEnv("A=1\n", ['env-kit.auto_backup' => false]);
    $custom = dirname($path).'/custom.example';
    file_put_contents($custom, "A=x\nNEWKEY=default\n");
    file_put_contents(dirname($path).'/.env.example', "A=x\nSIBKEY=sib\n");

    // --example drives the comparison
    $this->artisan('env:check', ['--example' => $custom])
        ->expectsOutputToContain('NEWKEY')
        ->assertExitCode(3);

    // an empty --example falls back to the sibling .env.example
    $this->artisan('env:check', ['--example' => ''])
        ->expectsOutputToContain('SIBKEY')
        ->assertExitCode(3);

    // sync honours --example…
    $this->artisan('env:sync', ['--example' => $custom])
        ->expectsOutputToContain('Added 1 missing key(s): NEWKEY')
        ->assertExitCode(0);
    expect(EnvDocument::parse((string) file_get_contents($path))->get('NEWKEY'))->toBe('default');

    // …and an empty --example falls back to the sibling example too
    $this->artisan('env:sync', ['--example' => ''])
        ->expectsOutputToContain('Added 1 missing key(s): SIBKEY')
        ->assertExitCode(0);
    expect(EnvDocument::parse((string) file_get_contents($path))->get('SIBKEY'))->toBe('sib');
});
