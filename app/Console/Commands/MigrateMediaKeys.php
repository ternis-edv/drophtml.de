<?php

namespace App\Console\Commands;

use App\Models\MediaAsset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MigrateMediaKeys extends Command
{
    protected $signature = 'media:migrate-keys {--apply : Copy and verify objects, then update records}';

    protected $description = 'Migrate legacy media to UUID keys, retaining original objects and URLs in metadata';

    public function handle(): int
    {
        $failed = false;
        foreach (MediaAsset::where('storage_location', 'asset_storage')->lazyById() as $asset) {
            if ($asset->hasUuidKey()) {
                continue;
            }
            $extension = strtolower(pathinfo($asset->path, PATHINFO_EXTENSION)) ?: 'bin';
            $key = $asset->id.'.'.$extension;
            $this->line($asset->path.' -> '.$key);
            if (! $this->option('apply')) {
                continue;
            }
            try {
                $source = $asset->storageDisk();
                $destination = Storage::disk('media');
                $stream = $source->readStream($asset->path);
                if (! is_resource($stream)) {
                    throw new RuntimeException('Cannot read source object');
                }
                try {
                    $hash = hash_init('sha256');
                    hash_update_stream($hash, $stream);
                    $expected = hash_final($hash);
                    fclose($stream);
                    $stream = $source->readStream($asset->path);
                    if (! is_resource($stream)) {
                        throw new RuntimeException('Cannot reopen source object');
                    }
                    if ($destination->exists($key)) {
                        // A previous run may have copied the object before its database update.
                        $this->verify($destination, $key, $expected);
                    } elseif (! $destination->put($key, $stream, 'public')) {
                        throw new RuntimeException('Copy failed');
                    }
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
                $this->verify($destination, $key, $expected);
                $asset->update([
                    'metadata' => array_merge($asset->metadata ?? [], [
                        'legacy_storage_key' => $asset->path,
                        'legacy_public_url' => $asset->resolved_url,
                    ]),
                    'path' => $key,
                    'public_url' => null,
                ]);
            } catch (\Throwable $exception) {
                $this->error($asset->id.': '.$exception->getMessage());
                $failed = true;
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function verify($disk, string $key, string $expected): void
    {
        $stream = $disk->readStream($key);
        if (! is_resource($stream)) {
            throw new RuntimeException('Cannot verify destination');
        }
        try {
            $hash = hash_init('sha256');
            hash_update_stream($hash, $stream);
            if (hash_final($hash) !== $expected) {
                throw new RuntimeException('Destination checksum mismatch');
            }
        } finally {
            fclose($stream);
        }
    }
}
