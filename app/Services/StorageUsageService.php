<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StorageUsageService
{
    /**
     * Default user storage limit (1 GB).
     */
    public const DEFAULT_QUOTA_BYTES = 1073741824;

    /**
     * Get comprehensive storage usage statistics for a user.
     */
    public function getUserStorageStats(User $user): array
    {
        $quotaBytes = (int) ($user->storage_limit_bytes ?: self::DEFAULT_QUOTA_BYTES);

        // 1. Cloud Media Storage
        $mediaAssets = $user->mediaAssets()->get();
        $cloudMediaAssets = $mediaAssets->where('storage_driver', 'asset_storage');
        $cloudMediaBytes = (int) $cloudMediaAssets->sum('size');

        // 2. Site Code Bundles
        $sites = $user->sites()->get();
        $siteCodeBytes = 0;
        $sitesBreakdown = [];
        $localDisk = Storage::disk('local');

        foreach ($sites as $site) {
            $codeBytes = (int) $site->storage_bytes;
            if ($codeBytes === 0 && Storage::disk('public')->exists($site->path)) {
                $codeBytes = $this->calculateSiteStorageBytes($site);
            }
            $siteCodeBytes += $codeBytes;

            // Site cloud media
            $siteCloudMediaBytes = (int) $cloudMediaAssets->where('site_id', $site->id)->sum('size');

            // Site snapshots
            $siteSnapshotsBytes = 0;
            $snapshotDir = "snapshots/{$site->id}";
            if ($localDisk->exists($snapshotDir)) {
                foreach ($localDisk->allFiles($snapshotDir) as $sFile) {
                    $siteSnapshotsBytes += (int) $localDisk->size($sFile);
                }
            }

            $siteTotalBytes = $codeBytes + $siteCloudMediaBytes + $siteSnapshotsBytes;

            $sitesBreakdown[] = [
                'id' => $site->id,
                'slug' => $site->slug,
                'original_name' => $site->original_name ?: $site->slug,
                'status' => $site->status,
                'views' => $site->views,
                'code_bytes' => $codeBytes,
                'cloud_media_bytes' => $siteCloudMediaBytes,
                'snapshots_bytes' => $siteSnapshotsBytes,
                'total_bytes' => $siteTotalBytes,
                'formatted_code' => MediaAsset::formatBytes($codeBytes),
                'formatted_media' => MediaAsset::formatBytes($siteCloudMediaBytes),
                'formatted_snapshots' => MediaAsset::formatBytes($siteSnapshotsBytes),
                'formatted_total' => MediaAsset::formatBytes($siteTotalBytes),
            ];
        }

        // 3. Deployment Rollback Snapshots Total
        $totalSnapshotsBytes = array_sum(array_column($sitesBreakdown, 'snapshots_bytes'));

        // Total used
        $totalUsedBytes = $cloudMediaBytes + $siteCodeBytes + $totalSnapshotsBytes;
        $remainingBytes = max(0, $quotaBytes - $totalUsedBytes);
        $usedPercentage = $quotaBytes > 0 ? min(100.0, round(($totalUsedBytes / $quotaBytes) * 100, 1)) : 0.0;

        // Calculate percentage of account used for each site
        foreach ($sitesBreakdown as &$sData) {
            $sData['percentage_of_used'] = $totalUsedBytes > 0
                ? round(($sData['total_bytes'] / $totalUsedBytes) * 100, 1)
                : 0.0;
        }
        unset($sData);

        // Sort sites by total storage descending
        usort($sitesBreakdown, fn ($a, $b) => $b['total_bytes'] <=> $a['total_bytes']);

        // File type breakdown
        $images = $cloudMediaAssets->where('file_type', 'image');
        $videos = $cloudMediaAssets->where('file_type', 'video');
        $audio = $cloudMediaAssets->where('file_type', 'audio');
        $documents = $cloudMediaAssets->whereIn('file_type', ['document', 'other']);

        $fileTypeBreakdown = [
            'images' => [
                'label' => 'Images',
                'count' => $images->count(),
                'bytes' => (int) $images->sum('size'),
                'formatted' => MediaAsset::formatBytes((int) $images->sum('size')),
                'color' => 'indigo',
            ],
            'videos' => [
                'label' => 'Videos',
                'count' => $videos->count(),
                'bytes' => (int) $videos->sum('size'),
                'formatted' => MediaAsset::formatBytes((int) $videos->sum('size')),
                'color' => 'purple',
            ],
            'audio' => [
                'label' => 'Audio',
                'count' => $audio->count(),
                'bytes' => (int) $audio->sum('size'),
                'formatted' => MediaAsset::formatBytes((int) $audio->sum('size')),
                'color' => 'pink',
            ],
            'documents' => [
                'label' => 'Documents & PDFs',
                'count' => $documents->count(),
                'bytes' => (int) $documents->sum('size'),
                'formatted' => MediaAsset::formatBytes((int) $documents->sum('size')),
                'color' => 'amber',
            ],
            'code' => [
                'label' => 'Hosted Site Code',
                'count' => $sites->count(),
                'bytes' => $siteCodeBytes,
                'formatted' => MediaAsset::formatBytes($siteCodeBytes),
                'color' => 'blue',
            ],
            'snapshots' => [
                'label' => 'Deployment Snapshots',
                'count' => array_sum(array_map(fn ($s) => $s['snapshots_bytes'] > 0 ? 1 : 0, $sitesBreakdown)),
                'bytes' => $totalSnapshotsBytes,
                'formatted' => MediaAsset::formatBytes($totalSnapshotsBytes),
                'color' => 'emerald',
            ],
        ];

        return [
            'total_used_bytes' => $totalUsedBytes,
            'quota_bytes' => $quotaBytes,
            'remaining_bytes' => $remainingBytes,
            'used_percentage' => $usedPercentage,
            'is_near_quota' => $usedPercentage >= 80.0,
            'is_over_quota' => $totalUsedBytes >= $quotaBytes,
            'formatted' => [
                'total_used' => MediaAsset::formatBytes($totalUsedBytes),
                'quota' => MediaAsset::formatBytes($quotaBytes),
                'remaining' => MediaAsset::formatBytes($remainingBytes),
                'cloud_media' => MediaAsset::formatBytes($cloudMediaBytes),
                'site_code' => MediaAsset::formatBytes($siteCodeBytes),
                'snapshots' => MediaAsset::formatBytes($totalSnapshotsBytes),
            ],
            'breakdown' => [
                'cloud_media_bytes' => $cloudMediaBytes,
                'site_code_bytes' => $siteCodeBytes,
                'snapshots_bytes' => $totalSnapshotsBytes,
            ],
            'counts' => [
                'sites' => $sites->count(),
                'media_assets' => $mediaAssets->count(),
                'cloud_media_assets' => $cloudMediaAssets->count(),
            ],
            'file_types' => $fileTypeBreakdown,
            'sites' => $sitesBreakdown,
        ];
    }

    /**
     * Get storage statistics for a single site.
     */
    public function getSiteStorageStats(Site $site): array
    {
        $codeBytes = (int) $site->storage_bytes;
        if ($codeBytes === 0 && Storage::disk('public')->exists($site->path)) {
            $codeBytes = $this->calculateSiteStorageBytes($site);
        }

        $cloudMediaBytes = (int) $site->mediaAssets()
            ->where('storage_driver', 'asset_storage')
            ->sum('size');

        $localDisk = Storage::disk('local');
        $snapshotsBytes = 0;
        $snapshotDir = "snapshots/{$site->id}";
        if ($localDisk->exists($snapshotDir)) {
            foreach ($localDisk->allFiles($snapshotDir) as $sFile) {
                $snapshotsBytes += (int) $localDisk->size($sFile);
            }
        }

        $totalBytes = $codeBytes + $cloudMediaBytes + $snapshotsBytes;

        return [
            'site_id' => $site->id,
            'slug' => $site->slug,
            'code_bytes' => $codeBytes,
            'cloud_media_bytes' => $cloudMediaBytes,
            'snapshots_bytes' => $snapshotsBytes,
            'total_bytes' => $totalBytes,
            'formatted_code' => MediaAsset::formatBytes($codeBytes),
            'formatted_cloud_media' => MediaAsset::formatBytes($cloudMediaBytes),
            'formatted_snapshots' => MediaAsset::formatBytes($snapshotsBytes),
            'formatted_total' => MediaAsset::formatBytes($totalBytes),
        ];
    }

    /**
     * Calculate and cache the exact storage size of a site's deployed files on public disk.
     */
    public function calculateSiteStorageBytes(Site $site): int
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($site->path)) {
            $site->updateQuietly(['storage_bytes' => 0]);

            return 0;
        }

        $total = 0;
        $files = $disk->allFiles($site->path);
        foreach ($files as $file) {
            $total += (int) $disk->size($file);
        }

        $site->updateQuietly(['storage_bytes' => $total]);

        return $total;
    }

    /**
     * Enforce storage quota. Throws ValidationException if quota would be exceeded.
     */
    public function enforceQuota(?User $user, int $additionalBytes = 0): void
    {
        if (! $user) {
            return;
        }

        $stats = $this->getUserStorageStats($user);
        $projected = $stats['total_used_bytes'] + $additionalBytes;

        if ($projected > $stats['quota_bytes']) {
            $usedFormatted = MediaAsset::formatBytes($stats['total_used_bytes']);
            $quotaFormatted = MediaAsset::formatBytes($stats['quota_bytes']);
            $incomingFormatted = MediaAsset::formatBytes($additionalBytes);

            throw ValidationException::withMessages([
                'storage' => "Storage quota exceeded ({$usedFormatted} used of {$quotaFormatted}). Uploading {$incomingFormatted} would exceed your account limit.",
            ]);
        }
    }

    /**
     * Get top largest files across the user's account.
     */
    public function getLargestFiles(User $user, int $limit = 8): array
    {
        $largest = [];

        // 1. Cloud media
        $mediaAssets = $user->mediaAssets()
            ->where('storage_driver', 'asset_storage')
            ->orderByDesc('size')
            ->take($limit)
            ->get();

        foreach ($mediaAssets as $media) {
            $largest[] = [
                'name' => $media->original_name,
                'path' => $media->path,
                'size' => (int) $media->size,
                'formatted_size' => MediaAsset::formatBytes((int) $media->size),
                'type' => $media->file_type,
                'location' => 'Cloud Media',
                'url' => $media->public_url ?? $media->resolved_url,
            ];
        }

        usort($largest, fn ($a, $b) => $b['size'] <=> $a['size']);

        return array_slice($largest, 0, $limit);
    }
}
