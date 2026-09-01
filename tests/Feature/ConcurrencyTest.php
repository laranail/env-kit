<?php

declare(strict_types=1);

it('never exposes a partial file under concurrent writers', function () {
    if (DIRECTORY_SEPARATOR === '\\' || ! function_exists('proc_open')) {
        $this->markTestSkipped('Needs a POSIX shell with proc_open.');
    }

    $path = envkit_temp();
    $dir = dirname($path);
    $autoload = dirname(__DIR__, 2).'/vendor/autoload.php';

    // A worker that hammers the SAME file with its own complete document.
    $workerFile = $dir.'/worker.php';
    file_put_contents($workerFile, '<?php require $argv[1];'
        .' $c = "WORKER=".$argv[3]."\nPAYLOAD=".str_repeat($argv[3], 80)."\n";'
        .' $w = new \Simtabi\Laranail\EnvKit\Headless\Writer\AtomicEnvWriter();'
        .' for ($i = 0; $i < (int) $argv[4]; $i++) { $w->write($argv[2], $c); }');

    $tags = ['alpha', 'bravo', 'charlie', 'delta'];
    $candidates = array_map(static fn (string $t): string => "WORKER={$t}\nPAYLOAD=".str_repeat($t, 80)."\n", $tags);
    file_put_contents($path, $candidates[0]); // a valid starting point

    $spec = [['file', '/dev/null', 'r'], ['file', '/dev/null', 'w'], ['file', '/dev/null', 'w']];
    $procs = [];
    foreach ($tags as $tag) {
        $procs[] = proc_open([PHP_BINARY, $workerFile, $autoload, $path, $tag, '350'], $spec, $pipes);
    }

    // While the workers race, every read must be a COMPLETE candidate — never a mix.
    $reads = 0;
    $deadline = microtime(true) + 1.2;
    while (microtime(true) < $deadline) {
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            continue;
        }

        expect($content)->toBeIn($candidates);
        $reads++;
    }

    foreach ($procs as $proc) {
        if (is_resource($proc)) {
            proc_close($proc);
        }
    }

    expect($reads)->toBeGreaterThan(0)
        ->and(file_get_contents($path))->toBeIn($candidates);
});
