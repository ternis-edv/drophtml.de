<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'bin';

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

        $assetId = (string) Str::uuid7();
        $storagePath = $assetId.'.'.$extension;

        Storage::disk($disk)->putFileAs('', $file, $storagePath, 'public');

        $publicUrl = $this->publicUrlFor($storagePath);

        $asset = MediaAsset::create([
            'user_id' => $user->id,
            'site_id' => $site?->id,
            'original_name' => $originalName,
            'id' => $assetId,
            'path' => $storagePath,
            'disk' => $disk,
            'storage_driver' => 'asset_storage',
            'file_type' => $fileType,
            'mime_type' => $mimeType,
            'size' => $size,
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
     * Store a base64 encoded media asset into either cloud Asset Storage or Site Code repository.
     */
    public function storeBase64(
        string $base64Data,
        string $originalName,
        User $user,
        ?Site $site = null,
        string $storageDriver = 'asset_storage',
        ?string $customSubdir = null
    ): MediaAsset {
        $mimeType = null;
        if (preg_match('/^data:([^;]+);base64,(.+)$/s', $base64Data, $matches)) {
            $mimeType = $matches[1];
            $binary = base64_decode($matches[2], true);
        } else {
            $binary = base64_decode($base64Data, true);
        }

        if ($binary === false) {
            throw new InvalidArgumentException('Invalid base64 payload provided.');
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (! $mimeType) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($binary) ?: 'application/octet-stream';
        }

        if (empty($extension)) {
            $extension = match ($mimeType) {
                'image/png' => 'png',
                'image/jpeg' => 'jpg',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'image/svg+xml' => 'svg',
                'video/mp4' => 'mp4',
                'audio/mpeg' => 'mp3',
                'application/pdf' => 'pdf',
                default => 'bin',
            };
            $originalName .= '.'.$extension;
        }

        if ($this->isDangerousExtension($extension)) {
            throw new InvalidArgumentException("Uploading .{$extension} files is not permitted for security reasons.");
        }

        $size = strlen($binary);
        $fileType = $this->determineFileType($mimeType, $extension);

        app(StorageUsageService::class)->enforceQuota($user, $size);

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

            Storage::disk('public')->put($fullDiskPath, $binary);

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
                'metadata' => [
                    'relative_code_path' => $relativeSitePath,
                    'folder' => $subdir,
                    'uploaded_via' => 'base64',
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

        $assetId = (string) Str::uuid7();
        $storagePath = $assetId.'.'.$extension;

        Storage::disk($disk)->put($storagePath, $binary, 'public');

        $publicUrl = $this->publicUrlFor($storagePath);

        $asset = MediaAsset::create([
            'user_id' => $user->id,
            'site_id' => $site?->id,
            'original_name' => $originalName,
            'id' => $assetId,
            'path' => $storagePath,
            'disk' => $disk,
            'storage_driver' => 'asset_storage',
            'file_type' => $fileType,
            'mime_type' => $mimeType,
            'size' => $size,
            'metadata' => [
                'provider' => $this->getActiveStorageProvider(),
                'uploaded_via' => 'base64',
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
        if ($asset->storageDisk()->exists($asset->path)) {
            $asset->storageDisk()->delete($asset->path);
        }

        if ($legacyKey = ($asset->metadata['legacy_storage_key'] ?? null)) {
            $legacy = clone $asset;
            $legacy->path = $legacyKey;
            if ($legacy->storageDisk()->exists($legacyKey)) {
                $legacy->storageDisk()->delete($legacyKey);
            }
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
            if (! $asset->storageDisk()->exists($asset->path)) {
                throw new RuntimeException("Source code file not found: {$asset->path}");
            }

            $content = $asset->storageDisk()->get($asset->path);

            $filename = Str::slug(pathinfo($asset->original_name, PATHINFO_FILENAME)).'-'.$asset->id.'.'.strtolower(pathinfo($asset->path, PATHINFO_EXTENSION));
            $targetPath = $asset->id.'.'.strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            Storage::disk('media')->put($targetPath, $content, 'public');
            $asset->storageDisk()->delete($asset->path);

            $publicUrl = $this->publicUrlFor($targetPath);

            $asset->update([
                'disk' => 'media',
                'storage_driver' => 'asset_storage',
                'path' => $targetPath,
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

            if (! $asset->storageDisk()->exists($asset->path)) {
                throw new RuntimeException("Source asset storage file not found: {$asset->path}");
            }

            $content = $asset->storageDisk()->get($asset->path);
            $subdir = $this->defaultCodeFolder($asset->file_type);
            $filename = Str::slug(pathinfo($asset->original_name, PATHINFO_FILENAME)).'-'.$asset->id.'.'.strtolower(pathinfo($asset->path, PATHINFO_EXTENSION));
            $relativeSitePath = "{$subdir}/{$filename}";
            $targetPath = "{$site->path}/{$relativeSitePath}";

            Storage::disk('public')->put($targetPath, $content);
            $asset->storageDisk()->delete($asset->path);

            $publicUrl = url('/s/'.$site->slug.'/'.ltrim($relativeSitePath, '/'));

            $asset->update([
                'site_id' => $site->id,
                'disk' => 'public',
                'storage_driver' => 'code',
                'path' => $targetPath,
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
        if (! $asset->storageDisk()->exists($asset->path)) {
            throw new RuntimeException("Asset file not found in storage: {$asset->path}");
        }

        $content = $asset->storageDisk()->get($asset->path);
        $folder = trim($subpath ?: $this->defaultCodeFolder($asset->file_type), '/');
        $filename = Str::slug(pathinfo($asset->original_name, PATHINFO_FILENAME)).'-'.$asset->id.'.'.strtolower(pathinfo($asset->path, PATHINFO_EXTENSION));
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

        $assetId = (string) Str::uuid7();
        $targetPath = $assetId.'.'.$ext;

        Storage::disk('media')->put($targetPath, $content, 'public');
        $publicUrl = $this->publicUrlFor($targetPath);

        if ($deleteFromCode) {
            Storage::disk('public')->delete($sourceDiskPath);
        }

        $asset = MediaAsset::create([
            'id' => $assetId,
            'user_id' => $user->id,
            'site_id' => $site->id,
            'original_name' => $originalName,
            'path' => $targetPath,
            'disk' => 'media',
            'storage_driver' => 'asset_storage',
            'file_type' => $fileType,
            'mime_type' => $mimeType,
            'size' => $size,
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
     * Build a public delivery URL for a media-disk path. When a custom
     * domain fronting the object-storage bucket is configured (e.g.
     * assets.drophtml.de on Cloudflare R2), it is preferred over the
     * driver's default URL.
     */
    public function publicUrlFor(string $path): string
    {
        $base = config('media.public_url');

        if (filled($base) && $this->cloudStorageConfigured()) {
            return rtrim($base, '/').'/'.ltrim($path, '/');
        }

        return Storage::disk('media')->url($path);
    }

    /**
     * Whether cloud object storage (S3 / Cloudflare R2) is the active media disk.
     */
    public function cloudStorageConfigured(): bool
    {
        return config('filesystems.disks.media.driver') === 's3';
    }

    /**
     * Automatically offload large media files (images/videos at or above the
     * configured size threshold) from a site's code bundle to cloud asset
     * storage, and rewrite references in the site's text files to the cloud
     * delivery URL.
     *
     * @return array<int, array{path: string, url: string, size: int, replacements: int}>
     */
    public function offloadLargeAssets(Site $site, ?User $actor = null): array
    {
        if (! config('media.offload.enabled', true) || ! $this->cloudStorageConfigured()) {
            return [];
        }

        $threshold = ((int) config('media.offload.threshold_kb', 512)) * 1024;
        $types = config('media.offload.types', ['image', 'video']);

        if ($threshold <= 0 || empty($types)) {
            return [];
        }

        $publicDisk = Storage::disk('public');
        $prefix = rtrim($site->path, '/').'/';
        $candidates = [];

        // Pass 1: collect offload candidates.
        foreach ($publicDisk->allFiles($site->path) as $diskPath) {
            if (! str_starts_with($diskPath, $prefix)) {
                continue;
            }

            $size = $publicDisk->size($diskPath);
            if ($size < $threshold) {
                continue;
            }

            $relativePath = substr($diskPath, strlen($prefix));
            $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
            $mimeType = $publicDisk->mimeType($diskPath) ?: 'application/octet-stream';

            if (! in_array($this->determineFileType($mimeType, $extension), $types, true)) {
                continue;
            }

            $candidates[] = [
                'disk_path' => $diskPath,
                'relative_path' => $relativePath,
                'size' => $size,
                'mime_type' => $mimeType,
            ];
        }

        if (empty($candidates)) {
            return [];
        }

        // Offloaded bytes consume cloud storage quota — abort gracefully when exceeded.
        try {
            app(StorageUsageService::class)->enforceQuota($site->user, (int) collect($candidates)->sum('size'));
        } catch (ValidationException) {
            if ($actor) {
                $this->activityLogService->log(
                    $actor,
                    $site,
                    'media_offload_skipped',
                    'Skipped automatic media offload: cloud storage quota exceeded'
                );
            }

            return [];
        }

        // Pass 2: move to cloud storage and rewrite references.
        $offloaded = [];

        foreach ($candidates as $candidate) {
            $relativePath = $candidate['relative_path'];
            $assetId = (string) Str::uuid7();
            $targetPath = $assetId.'.'.strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

            $content = $publicDisk->get($candidate['disk_path']);
            Storage::disk('media')->put($targetPath, $content, 'public');

            $publicUrl = $this->publicUrlFor($targetPath);

            $publicDisk->delete($candidate['disk_path']);

            MediaAsset::create([
                'id' => $assetId,
                'user_id' => $site->user_id,
                'site_id' => $site->id,
                'original_name' => basename($relativePath),
                'path' => $targetPath,
                'disk' => 'media',
                'storage_driver' => 'asset_storage',
                'file_type' => $this->determineFileType($candidate['mime_type'], pathinfo($relativePath, PATHINFO_EXTENSION)),
                'mime_type' => $candidate['mime_type'],
                'size' => $candidate['size'],
                'metadata' => [
                    'offloaded_from_code' => $relativePath,
                    'relative_code_path' => $relativePath,
                    'provider' => $this->getActiveStorageProvider(),
                    'offloaded_at' => now()->toIso8601String(),
                ],
            ]);

            $replacements = $this->rewriteReferences($site, $relativePath, $publicUrl);

            $offloaded[] = [
                'path' => $relativePath,
                'url' => $publicUrl,
                'size' => $candidate['size'],
                'replacements' => $replacements,
            ];
        }

        app(StorageUsageService::class)->calculateSiteStorageBytes($site);

        if ($actor) {
            $this->activityLogService->log(
                $actor,
                $site,
                'media_offloaded_to_cloud',
                'Offloaded '.count($offloaded).' large media file(s) to Cloud Asset Storage ('.$this->getActiveStorageProvider().')'
            );
        }

        return $offloaded;
    }

    /**
     * Rewrite references to an offloaded code path inside the site's text
     * files (HTML/CSS/JS/...) so they point at the cloud delivery URL.
     * Returns the number of files updated.
     */
    protected function rewriteReferences(Site $site, string $relativePath, string $newUrl): int
    {
        $publicDisk = Storage::disk('public');
        $prefix = rtrim($site->path, '/').'/';
        $textExtensions = ['html', 'htm', 'css', 'js', 'mjs', 'json', 'txt', 'md', 'svg', 'webmanifest', 'xml'];
        $quotedRel = preg_quote($relativePath, '~');
        $replacementUrl = str_replace(['$', '\\'], '', $newUrl);
        $updated = 0;

        foreach ($publicDisk->allFiles($site->path) as $diskPath) {
            if (! str_starts_with($diskPath, $prefix)) {
                continue;
            }

            $extension = strtolower(pathinfo($diskPath, PATHINFO_EXTENSION));
            if (! in_array($extension, $textExtensions, true)) {
                continue;
            }

            $content = $publicDisk->get($diskPath);

            if (! is_string($content) || $content === '' || str_contains($content, "\0")) {
                continue;
            }

            $original = $content;

            // src / href / poster attributes: rel, /rel, ./rel (plus query/fragment suffixes)
            $content = preg_replace(
                '~((?:src|href|poster|data-src|data-href)\s*=\s*["\'])(?:\./)?/?'.$quotedRel.'(["\'?#])~',
                '$1'.$replacementUrl.'$2',
                $content
            );

            // CSS url(...) references
            $content = preg_replace(
                '~url\(\s*["\']?(?:\./)?/?'.$quotedRel.'["\']?\s*\)~',
                'url("'.$replacementUrl.'")',
                $content
            );

            // srcset-style lists: replace the path inside the attribute value only
            $content = preg_replace_callback(
                '~((?:srcset|data-srcset)\s*=\s*["\'])([^"\']*)~',
                function ($matches) use ($quotedRel, $replacementUrl) {
                    return $matches[1].preg_replace('~(?:\./)?/?'.$quotedRel.'(?=[\s,"\'])~', $replacementUrl, $matches[2]);
                },
                $content
            );

            if ($content !== $original) {
                $publicDisk->put($diskPath, $content);
                $updated++;
            }
        }

        return $updated;
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
