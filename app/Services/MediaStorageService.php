<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class MediaStorageService
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Store an uploaded file into either cloud Asset Storage (S3/R2) or Site Code repository.
     */
    public function store(
        UploadedFile $file,
        User $user,
        ?Site $site = null,
        string $storageDriver = 'asset_storage',
        ?string $customSubdir = null
    ): MediaAsset {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($this->isDangerousExtension($extension)) {
            throw new InvalidArgumentException("Uploading .{$extension} files is not permitted for security reasons.");
        }

        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType() ?: 'application/octet-stream';
        $size = $file->getSize() ?: 0;
        $fileType = $this->determineFileType($mimeType, $extension);

        app(StorageUsageService::class)->enforceQuota($user, (int) $size);

        $safeBase = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
        if (empty($safeBase)) {
            $safeBase = 'asset';
        }
        $uniqueSuffix = strtolower(Str::random(6));
        $cleanFileName = "{$safeBase}-{$uniqueSuffix}.{$extension}";

        if ($storageDriver === 'code') {
            if (! $site) {
                throw new InvalidArgumentException('A site must be specified to store a media file in the code repository.');
            }

            $subdir = trim($customSubdir ?: $this->defaultCodeFolder($fileType), '/');
            $relativeSitePath = $subdir ? "{$subdir}/{$cleanFileName}" : $cleanFileName;
            $fullDiskPath = "{$site->path}/{$relativeSitePath}";

            Storage::disk('public')->putFileAs(
                dirname($fullDiskPath),
                $file,
                basename($fullDiskPath)
            );

            $publicUrl = url('/s/'.$site->slug.'/'.ltrim($relativeSitePath, '/'));

            $asset = MediaAsset::create([
                'user_id' => $user->id,
                'site_id' => $site->id,
                'original_name' => $originalName,
                'path' => $fullDiskPath,
                'disk' => 'public',
                'storage_driver' => 'code',
                'file_type' => $fileType,
                'mime_type' => $mimeType,
                'size' => $size,
                'public_url' => $publicUrl,
                'metadata' => [
                    'relative_code_path' => $relativeSitePath,
                    'folder' => $subdir,
                ],
            ]);

            $this->activityLogService->log(
                $user,
                $site,
                'media_uploaded_to_code',
                "Uploaded media '{$originalName}' to site code ({$relativeSitePath})"
            );

            app(StorageUsageService::class)->calculateSiteStorageBytes($site);

            return $asset;
        }

        // Default: Cloud Asset Storage (S3 / Cloudflare R2)
        $disk = 'media';
        $folder = $site
            ? "sites/{$site->id}/media"
            : "users/{$user->id}/media";

        $storagePath = "{$folder}/{$cleanFileName}";

        Storage::disk($disk)->putFileAs($folder, $file, $cleanFileName, 'public');

        $publicUrl = Storage::disk($disk)->url($storagePath);

        $asset = MediaAsset::create([
            'user_id' => $user->id,
            'site_id' => $site?->id,
            'original_name' => $originalName,
            'path' => $storagePath,
            'disk' => $disk,
            'storage_driver' => 'asset_storage',
            'file_type' => $fileType,
            'mime_type' => $mimeType,
            'size' => $size,
            'public_url' => $publicUrl,
            'metadata' => [
                'provider' => $this->getActiveStorageProvider(),
            ],
        ]);

        $this->activityLogService->log(
            $user,
            $site,
            'media_uploaded_to_asset_storage',
            "Uploaded media '{$originalName}' to cloud Asset Storage ({$this->getActiveStorageProvider()})"
        );

        return $asset;
    }

    /**
     * Delete a media asset and remove its physical file.
     */
    public function delete(MediaAsset $asset, ?User $actor = null): bool
    {
        $diskName = $asset->disk ?: ($asset->storage_driver === 'code' ? 'public' : 'media');

        if (Storage::disk($diskName)->exists($asset->path)) {
            Storage::disk($diskName)->delete($asset->path);
        }

        if ($actor) {
            $this->activityLogService->log(
                $actor,
                $asset->site,
                'media_deleted',
                "Deleted media asset: {$asset->original_name} ({$asset->storage_driver})"
            );
        }

        $site = $asset->site;
        $isCode = $asset->storage_driver === 'code';

        $deleted = (bool) $asset->forceDelete();

        if ($deleted && $isCode && $site) {
            app(StorageUsageService::class)->calculateSiteStorageBytes($site);
        }

        return $deleted;
    }

    /**
     * Migrate an asset between Asset Storage (S3/R2) and Site Code bundle.
     */
    public function migrate(
        MediaAsset $asset,
        string $targetDriver,
        ?Site $targetSite = null,
        ?User $actor = null
    ): MediaAsset {
        if ($asset->storage_driver === $targetDriver) {
            return $asset;
        }

        if ($targetDriver === 'asset_storage') {
            // Migrate from Code -> S3/R2 Asset Storage
            $sourceDisk = 'public';
            if (! Storage::disk($sourceDisk)->exists($asset->path)) {
                throw new RuntimeException("Source code file not found: {$asset->path}");
            }

            $content = Storage::disk($sourceDisk)->get($asset->path);
            $folder = $asset->site
                ? "sites/{$asset->site->id}/media"
                : "users/{$asset->user_id}/media";

            $filename = basename($asset->path);
            $targetPath = "{$folder}/{$filename}";

            Storage::disk('media')->put($targetPath, $content, 'public');
            Storage::disk($sourceDisk)->delete($asset->path);

            $publicUrl = Storage::disk('media')->url($targetPath);

            $asset->update([
                'disk' => 'media',
                'storage_driver' => 'asset_storage',
                'path' => $targetPath,
                'public_url' => $publicUrl,
                'metadata' => array_merge($asset->metadata ?? [], [
                    'migrated_from' => 'code',
                    'migrated_at' => now()->toIso8601String(),
                ]),
            ]);

            if ($actor) {
                $this->activityLogService->log(
                    $actor,
                    $asset->site,
                    'media_migrated',
                    "Migrated '{$asset->original_name}' from Code to Cloud Asset Storage"
                );
            }

            return $asset;
        }

        if ($targetDriver === 'code') {
            // Migrate from S3/R2 Asset Storage -> Code
            $site = $targetSite ?: $asset->site;
            if (! $site) {
                throw new InvalidArgumentException('Target site is required to migrate asset to Code.');
            }

            $sourceDisk = $asset->disk ?: 'media';
            if (! Storage::disk($sourceDisk)->exists($asset->path)) {
                throw new RuntimeException("Source asset storage file not found: {$asset->path}");
            }

            $content = Storage::disk($sourceDisk)->get($asset->path);
            $subdir = $this->defaultCodeFolder($asset->file_type);
            $filename = basename($asset->path);
            $relativeSitePath = "{$subdir}/{$filename}";
            $targetPath = "{$site->path}/{$relativeSitePath}";

            Storage::disk('public')->put($targetPath, $content);
            Storage::disk($sourceDisk)->delete($asset->path);

            $publicUrl = url('/s/'.$site->slug.'/'.ltrim($relativeSitePath, '/'));

            $asset->update([
                'site_id' => $site->id,
                'disk' => 'public',
                'storage_driver' => 'code',
                'path' => $targetPath,
                'public_url' => $publicUrl,
                'metadata' => array_merge($asset->metadata ?? [], [
                    'migrated_from' => 'asset_storage',
                    'migrated_at' => now()->toIso8601String(),
                    'relative_code_path' => $relativeSitePath,
                ]),
            ]);

            if ($actor) {
                $this->activityLogService->log(
                    $actor,
                    $site,
                    'media_migrated',
                    "Migrated '{$asset->original_name}' from Cloud Asset Storage to Site Code"
                );
            }

            return $asset;
        }

        throw new InvalidArgumentException("Unknown storage driver: {$targetDriver}");
    }

    /**
     * Copy an asset from cloud Asset Storage to site's local code folder without deleting original.
     */
    public function copyToCode(MediaAsset $asset, Site $site, ?string $subpath = null): string
    {
        $sourceDisk = $asset->disk ?: 'media';
        if (! Storage::disk($sourceDisk)->exists($asset->path)) {
            throw new RuntimeException("Asset file not found in storage: {$asset->path}");
        }

        $content = Storage::disk($sourceDisk)->get($asset->path);
        $folder = trim($subpath ?: $this->defaultCodeFolder($asset->file_type), '/');
        $filename = basename($asset->path);
        $relPath = $folder ? "{$folder}/{$filename}" : $filename;
        $targetDiskPath = "{$site->path}/{$relPath}";

        Storage::disk('public')->put($targetDiskPath, $content);

        return $relPath;
    }

    /**
     * Promote an existing code bundle file to cloud Asset Storage (S3/R2).
     */
    public function promoteCodeFileToAssetStorage(
        Site $site,
        string $relativePath,
        User $user,
        bool $deleteFromCode = false
    ): MediaAsset {
        $sourceDiskPath = "{$site->path}/".ltrim($relativePath, '/');
        if (! Storage::disk('public')->exists($sourceDiskPath)) {
            throw new RuntimeException("Code file not found: {$sourceDiskPath}");
        }

        $content = Storage::disk('public')->get($sourceDiskPath);
        $originalName = basename($relativePath);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        $mimeType = Storage::disk('public')->mimeType($sourceDiskPath) ?: 'application/octet-stream';
        $size = Storage::disk('public')->size($sourceDiskPath) ?: 0;
        $fileType = $this->determineFileType($mimeType, $ext);

        $folder = "sites/{$site->id}/media";
        $safeBase = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
        $cleanFileName = "{$safeBase}-".strtolower(Str::random(6)).".{$ext}";
        $targetPath = "{$folder}/{$cleanFileName}";

        Storage::disk('media')->put($targetPath, $content, 'public');
        $publicUrl = Storage::disk('media')->url($targetPath);

        if ($deleteFromCode) {
            Storage::disk('public')->delete($sourceDiskPath);
        }

        $asset = MediaAsset::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'original_name' => $originalName,
            'path' => $targetPath,
            'disk' => 'media',
            'storage_driver' => 'asset_storage',
            'file_type' => $fileType,
            'mime_type' => $mimeType,
            'size' => $size,
            'public_url' => $publicUrl,
            'metadata' => [
                'promoted_from_code' => $relativePath,
                'provider' => $this->getActiveStorageProvider(),
            ],
        ]);

        $this->activityLogService->log(
            $user,
            $site,
            'media_promoted_to_cloud',
            "Promoted code file '{$relativePath}' to Cloud Asset Storage"
        );

        return $asset;
    }

    /**
     * Determine category of media file.
     */
    public function determineFileType(string $mime, string $extension): string
    {
        $ext = strtolower(trim($extension, '.'));

        if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'avif', 'ico', 'bmp', 'tiff'], true)
            || str_starts_with($mime, 'image/')) {
            return 'image';
        }

        if (in_array($ext, ['mp4', 'webm', 'mov', 'mkv', 'avi', 'ogv', 'm4v', '3gp'], true)
            || str_starts_with($mime, 'video/')) {
            return 'video';
        }

        if (in_array($ext, ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac', 'weba'], true)
            || str_starts_with($mime, 'audio/')) {
            return 'audio';
        }

        if (in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'json', 'xml', 'md', 'zip', 'tar', 'gz'], true)
            || $mime === 'application/pdf') {
            return 'document';
        }

        return 'other';
    }

    /**
     * Default folder in site code repository by file type.
     */
    public function defaultCodeFolder(string $fileType): string
    {
        return match ($fileType) {
            'image' => 'images',
            'video' => 'videos',
            'audio' => 'audio',
            'document' => 'assets',
            default => 'assets',
        };
    }

    /**
     * Check if a file extension is dangerous.
     */
    public function isDangerousExtension(string $extension): bool
    {
        return in_array(strtolower(trim($extension, '.')), SiteDeploymentService::DANGEROUS_EXTENSIONS, true);
    }

    /**
     * Identify active storage provider name for display.
     */
    public function getActiveStorageProvider(): string
    {
        $mediaDiskConfig = config('filesystems.disks.media', []);
        $driver = $mediaDiskConfig['driver'] ?? 'local';

        if ($driver === 's3') {
            $endpoint = $mediaDiskConfig['endpoint'] ?? '';
            if (str_contains($endpoint, 'r2.cloudflarestorage.com') || env('R2_BUCKET')) {
                return 'Cloudflare R2';
            }

            return 'AWS S3';
        }

        return 'Local Object Storage';
    }

    /**
     * Collect summary statistics for media assets.
     */
    public function getStorageStats(?User $user = null, ?Site $site = null): array
    {
        $query = MediaAsset::query();
        if ($user) {
            $query->where('user_id', $user->id);
        }
        if ($site) {
            $query->where('site_id', $site->id);
        }

        $all = $query->get();

        $totalBytes = $all->sum('size');
        $cloudCount = $all->where('storage_driver', 'asset_storage')->count();
        $codeCount = $all->where('storage_driver', 'code')->count();

        $imagesCount = $all->where('file_type', 'image')->count();
        $videosCount = $all->where('file_type', 'video')->count();
        $docsCount = $all->whereIn('file_type', ['document', 'other'])->count();
        $audioCount = $all->where('file_type', 'audio')->count();

        return [
            'total_count' => $all->count(),
            'total_bytes' => $totalBytes,
            'cloud_count' => $cloudCount,
            'code_count' => $codeCount,
            'images_count' => $imagesCount,
            'videos_count' => $videosCount,
            'docs_count' => $docsCount,
            'audio_count' => $audioCount,
            'provider' => $this->getActiveStorageProvider(),
        ];
    }
}
