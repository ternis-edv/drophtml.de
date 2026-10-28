<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Services\MediaStorageService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sites:offload-media {--site= : Only process a single site by its slug}')]
#[Description('Offload large media files (images/videos) from site code bundles to cloud asset storage (R2/S3).')]
class OffloadLargeAssets extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(MediaStorageService $mediaStorageService): int
    {
        if (! $mediaStorageService->cloudStorageConfigured()) {
            $this->warn('Cloud asset storage (S3 / Cloudflare R2) is not configured. Nothing to do.');

            return self::SUCCESS;
        }

        $slug = $this->option('site');
        $query = Site::query()->orderBy('created_at');

        if ($slug) {
            $query->where('slug', $slug);
        }

        $sites = $query->get();

        if ($sites->isEmpty()) {
            $this->info($slug ? "Site '{$slug}' not found." : 'No sites found.');

            return $slug ? self::FAILURE : self::SUCCESS;
        }

        $totalFiles = 0;
        $totalBytes = 0;

        foreach ($sites as $site) {
            $offloaded = $mediaStorageService->offloadLargeAssets($site, $site->user);

            if (empty($offloaded)) {
                continue;
            }

            $siteBytes = (int) collect($offloaded)->sum('size');
            $totalFiles += count($offloaded);
            $totalBytes += $siteBytes;

            $this->info(sprintf(
                '%s: offloaded %d file(s) (%s)',
                $site->slug,
                count($offloaded),
                $this->humanBytes($siteBytes)
            ));
        }

        if ($totalFiles === 0) {
            $this->info('No large media files found in site code bundles.');
        } else {
            $this->info("Done. Offloaded {$totalFiles} file(s) ({$this->humanBytes($totalBytes)}) to cloud asset storage.");
        }

        return self::SUCCESS;
    }

    protected function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;
        $value = (float) $bytes;

        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        return round($value, 1).' '.$units[$index];
    }
}
