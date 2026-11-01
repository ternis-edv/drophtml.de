<?php

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

it('isolates backup storage from public media and monitors the same destination', function () {
    expect(config('backup.backup.destination.disks'))->toBe(['backups'])
        ->and(config('backup.monitor_backups.0.disks'))->toBe(['backups'])
        ->and(config('filesystems.disks.backups.bucket'))->toBe('drophtml-backup')
        ->and(config('filesystems.disks.backups.driver'))->toBe('s3')
        ->and(config('filesystems.disks.backups.visibility'))->toBe('private')
        ->and(config('filesystems.disks.backups.throw'))->toBeTrue();
});

it('writes a verified backup archive to the backup disk', function () {
    Notification::fake();
    Storage::fake('backups');
    Storage::fake('local');
    Storage::disk('local')->put('backup-fixture.txt', 'backup integration fixture');
    config([
        'backup.backup.name' => 'backup-test',
        'backup.backup.source.files.include' => [Storage::disk('local')->path('backup-fixture.txt')],
        'backup.backup.source.files.exclude' => [],
    ]);

    $this->artisan('backup:run', ['--only-files' => true, '--disable-notifications' => true])
        ->assertSuccessful();

    $files = Storage::disk('backups')->allFiles('backup-test');
    expect($files)->toHaveCount(1);
    $archive = new ZipArchive;
    expect($archive->open(Storage::disk('backups')->path($files[0])))->toBeTrue();
    expect($archive->getFromIndex(0))->toBe('backup integration fixture');
    $archive->close();
});
