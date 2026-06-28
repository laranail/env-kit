<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Backup\BackupManager;

it('creates a timestamped backup of an existing file', function () {
    $path = envkit_temp();
    file_put_contents($path, "A=1\n");

    $backup = (new BackupManager(dirname($path).'/backups'))->backup($path);

    expect($backup)->not->toBeNull()
        ->and(is_file($backup->path))->toBeTrue()
        ->and(file_get_contents($backup->path))->toBe("A=1\n")
        ->and($backup->name)->toEndWith('.bak');
});

it('returns null when there is nothing to back up', function () {
    $path = envkit_temp(); // file intentionally not created

    expect((new BackupManager(dirname($path).'/backups'))->backup($path))->toBeNull();
});

it('lists newest-first and prunes beyond the retention count', function () {
    $path = envkit_temp();
    file_put_contents($path, "A=1\n");
    $manager = new BackupManager(dirname($path).'/backups', retain: 2);

    for ($i = 0; $i < 4; $i++) {
        $manager->backup($path);
        usleep(2000);
    }

    expect($manager->all())->toHaveCount(2)
        ->and($manager->latest())->not->toBeNull();
});
