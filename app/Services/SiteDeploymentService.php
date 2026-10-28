<?php

namespace App\Services;

use App\Jobs\DispatchSiteWebhookJob;
use App\Models\Deployment;
use App\Models\MediaAsset;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class SiteDeploymentService
{
    /**
     * Dangerous file extensions that must never be allowed in hosted sites.
     */
    public const DANGEROUS_EXTENSIONS = [
        'php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'phps',
        'sh', 'bash', 'zsh', 'exe', 'bat', 'cmd', 'dll', 'cgi', 'pl', 'py',
        'env', 'htaccess', 'htpasswd', 'conf', 'config',
    ];

    protected StorageUsageService $storageUsageService;

    public function __construct(
        protected ActivityLogService $activityLogService,
        ?StorageUsageService $storageUsageService = null
    ) {
        $this->storageUsageService = $storageUsageService ?? app(StorageUsageService::class);
    }

    /**
     * Create a site from an uploaded file (ZIP archive or single HTML/asset file).
     */
    public function createSiteFromUpload(
        UploadedFile $file,
        string $originalName,
        ?User $user = null,
        array $options = []
    ): Site {
        if ($user) {
            $this->storageUsageService->enforceQuota($user, (int) $file->getSize());
        }

        $slug = $this->generateUniqueSlug($originalName);
        $storagePath = "sites/{$slug}";

        Storage::disk('public')->makeDirectory($storagePath);
        $fullStoragePath = Storage::disk('public')->path($storagePath);

        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'zip') {
            $this->extractZipSafely($file->getRealPath(), $fullStoragePath);
        } else {
            if ($this->isDangerousExtension($extension)) {
                Storage::disk('public')->deleteDirectory($storagePath);
                throw new InvalidArgumentException("Uploading .{$extension} files is not permitted for security reasons.");
            }

            $sanitizedFilename = $this->sanitizeFileName($file->getClientOriginalName());
            $file->storeAs($storagePath, $sanitizedFilename, 'public');
        }

        $isPermanent = (bool) ($options['is_permanent'] ?? false);
        $expiresAt = $this->calculateExpiry($user, $isPermanent);

        $site = Site::create([
            'slug' => $slug,
            'original_name' => $originalName,
            'path' => $storagePath,
            'entry_path' => SiteServingService::detectEntryPath($storagePath),
            'user_id' => $user?->id,
            'status' => 'active',
            'is_permanent' => $isPermanent,
            'expires_at' => $expiresAt,
            'github_repo_full_name' => $options['github_repo_full_name'] ?? null,
            'github_branch' => $options['github_branch'] ?? null,
            'auto_deploy' => (bool) ($options['auto_deploy'] ?? false),
            'is_password_protected' => (bool) ($options['is_password_protected'] ?? false),
            'site_password' => ! empty($options['site_password']) ? Hash::make($options['site_password']) : null,
        ]);

        $deployment = $site->deployments()->create([
            'status' => 'success',
            'source' => $options['source'] ?? 'upload',
            'commit_message' => "Uploaded site: {$originalName}",
            'metadata' => [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ],
        ]);
        $this->createDeploymentSnapshot($site, $deployment);
        $this->storageUsageService->calculateSiteStorageBytes($site);

        // Automatically offload large media (images/videos) to cloud asset storage.
        app(MediaStorageService::class)->offloadLargeAssets($site, $user);

        if ($user) {
            $this->activityLogService->log(
                $user,
                $site,
                'site_uploaded',
                "Uploaded site: {$originalName} ({$slug})"
            );
        }

        return $site;
    }

    /**
     * Create a site from raw HTML code or content.
     */
    public function createSiteFromContent(
        string $content,
        string $name,
        ?User $user = null,
        array $options = []
    ): Site {
        if ($user) {
            $this->storageUsageService->enforceQuota($user, strlen($content));
        }

        $slug = ! empty($options['custom_slug'])
            ? Str::slug($options['custom_slug'])
            : $this->generateUniqueSlug($name);

        if (Site::where('slug', $slug)->exists()) {
            $slug = $this->generateUniqueSlug($slug);
        }
        $storagePath = "sites/{$slug}";

        Storage::disk('public')->makeDirectory($storagePath);
        Storage::disk('public')->put("{$storagePath}/index.html", $content);

        $isPermanent = (bool) ($options['is_permanent'] ?? false);
        $expiresAt = $this->calculateExpiry($user, $isPermanent);

        $site = Site::create([
            'slug' => $slug,
            'original_name' => $name,
            'path' => $storagePath,
            'entry_path' => 'index.html',
            'user_id' => $user?->id,
            'status' => 'active',
            'is_permanent' => $isPermanent,
            'expires_at' => $expiresAt,
            'is_password_protected' => (bool) ($options['is_password_protected'] ?? false),
            'site_password' => ! empty($options['site_password']) ? Hash::make($options['site_password']) : null,
        ]);

        $deployment = $site->deployments()->create([
            'status' => 'success',
            'source' => 'editor',
            'commit_message' => "Initial deployment from web editor: {$name}",
            'metadata' => [
                'editor' => true,
            ],
        ]);
        $this->createDeploymentSnapshot($site, $deployment);
        $this->storageUsageService->calculateSiteStorageBytes($site);

        if ($user) {
            $this->activityLogService->log(
                $user,
                $site,
                'site_uploaded',
                "Created site from editor: {$name} ({$slug})"
            );
        }

        return $site;
    }

    /**
     * Extract a ZIP archive safely with Zip Slip protection and dangerous file filtering.
     */
    public function extractZipSafely(string $zipFilePath, string $destinationPath): void
    {
        $zip = new ZipArchive;
        $status = $zip->open($zipFilePath);

        if ($status !== true) {
            throw new RuntimeException("Unable to open ZIP archive (code: {$status}).");
        }

        $realDestination = realpath($destinationPath);
        if ($realDestination === false) {
            @mkdir($destinationPath, 0755, true);
            $realDestination = realpath($destinationPath);
        }

        if ($realDestination === false) {
            $zip->close();
            throw new RuntimeException("Destination directory does not exist or is not accessible: {$destinationPath}");
        }

        // Validate all entries before extraction
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);

            // Normalize path separators
            $normalizedEntry = str_replace('\\', '/', $entryName);

            // Prevent Zip Slip (path traversal)
            if (str_contains($normalizedEntry, '../') || str_starts_with($normalizedEntry, '/')) {
                $zip->close();
                throw new InvalidArgumentException("Security violation: ZIP contains path traversal entry '{$entryName}'.");
            }

            // Check for dangerous file extension
            $extension = strtolower(pathinfo($normalizedEntry, PATHINFO_EXTENSION));
            if ($this->isDangerousExtension($extension)) {
                $zip->close();
                throw new InvalidArgumentException("Security violation: ZIP contains executable or dangerous file '{$entryName}'.");
            }

            // Reject hidden critical files
            $basename = basename($normalizedEntry);
            if (in_array(strtolower($basename), ['.env', '.htaccess', '.htpasswd', '.git'])) {
                $zip->close();
                throw new InvalidArgumentException("Security violation: ZIP contains forbidden file '{$entryName}'.");
            }
        }

        // Safely extract file by file
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            $normalizedEntry = str_replace('\\', '/', $entryName);

            $targetPath = $realDestination.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $normalizedEntry);

            // Extra boundary check
            $targetDir = dirname($targetPath);
            if (! is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }

            // If it is a directory entry
            if (str_ends_with($normalizedEntry, '/')) {
                if (! is_dir($targetPath)) {
                    @mkdir($targetPath, 0755, true);
                }

                continue;
            }

            $content = $zip->getFromIndex($i);
            if ($content !== false) {
                file_put_contents($targetPath, $content);
            }
        }

        $zip->close();

        // Automatically hoist single top-level directory if present
        $this->hoistSingleDirectory($realDestination);
    }

    /**
     * Hoist inner directory if the root contains only a single directory.
     */
    public function hoistSingleDirectory(string $directoryPath): void
    {
        $items = array_diff(scandir($directoryPath), ['.', '..', '__MACOSX', '.DS_Store']);

        if (count($items) === 1) {
            $singleItem = array_shift($items);
            $innerDir = $directoryPath.DIRECTORY_SEPARATOR.$singleItem;

            if (is_dir($innerDir)) {
                $files = array_diff(scandir($innerDir), ['.', '..']);
                foreach ($files as $file) {
                    rename(
                        $innerDir.DIRECTORY_SEPARATOR.$file,
                        $directoryPath.DIRECTORY_SEPARATOR.$file
                    );
                }
                @rmdir($innerDir);
            }
        }
    }

    /**
     * Delete a site and all its physical files.
     */
    public function deleteSite(Site $site, ?User $actor = null): void
    {
        if ($site->path) {
            Storage::disk('public')->deleteDirectory($site->path);
            Storage::disk('local')->deleteDirectory("snapshots/{$site->id}");
        }

        foreach ($site->mediaAssets()->withTrashed()->get() as $asset) {
            app(MediaStorageService::class)->delete($asset);
        }

        // Clean up cloud media assets directory for this site
        Storage::disk('media')->deleteDirectory("sites/{$site->id}");

        if ($actor) {
            $this->activityLogService->log(
                $actor,
                null,
                'site_deleted',
                "Deleted site: {$site->original_name} ({$site->slug})"
            );
        }

        $site->mediaAssets()->forceDelete();
        $site->redirects()->delete();
        $site->domains()->delete();
        $site->deployments()->delete();
        $site->siteViews()->delete();
        $site->delete();
    }

    /**
     * Check if a file extension is dangerous.
     */
    public function isDangerousExtension(string $extension): bool
    {
        return in_array(strtolower(trim($extension, '.')), self::DANGEROUS_EXTENSIONS, true);
    }

    /**
     * Sanitize a file name to prevent path traversal and odd characters.
     */
    public function sanitizeFileName(string $filename): string
    {
        $cleaned = ltrim(str_replace(['../', '..\\'], '', trim($filename)), '/\\');
        $basename = basename($cleaned);

        return preg_replace('/[^\w\.\-_]/', '_', $basename);
    }

    /**
     * Generate a unique slug for a site.
     */
    public function generateUniqueSlug(string $name): string
    {
        $base = Str::slug(pathinfo($name, PATHINFO_FILENAME));
        if (empty($base)) {
            $base = 'site';
        }

        do {
            $slug = $base.'-'.strtolower(Str::random(6));
        } while (Site::where('slug', $slug)->exists());

        return $slug;
    }

    /**
     * Package the site's deployed files into a ZIP archive and stream download.
     * When $includeMedia is true, bundles both site code and cloud media assets with a manifest.
     */
    public function downloadZip(Site $site, bool $includeMedia = false): BinaryFileResponse
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($site->path)) {
            abort(404, 'Site files directory not found.');
        }

        $allFiles = $disk->allFiles($site->path);
        if (empty($allFiles)) {
            abort(404, 'No files found in site to archive.');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'site_zip_');
        $zip = new ZipArchive;

        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Failed to initialize ZIP archive for site download.');
        }

        $prefix = rtrim($site->path, '/').'/';
        foreach ($allFiles as $file) {
            $relativePath = str_starts_with($file, $prefix) ? substr($file, strlen($prefix)) : $file;
            $fullFilePath = $disk->path($file);
            if (file_exists($fullFilePath) && is_readable($fullFilePath)) {
                $zip->addFile($fullFilePath, $relativePath);
            } else {
                $zip->addFromString($relativePath, $disk->get($file));
            }
        }

        if ($includeMedia) {
            $mediaAssets = $site->mediaAssets()->get();
            $manifestAssets = [];

            foreach ($mediaAssets as $media) {
                $targetDisk = $media->storageDisk();
                if ($targetDisk->exists($media->path)) {
                    $mediaContent = $targetDisk->get($media->path);
                    $archivePath = '_cloud_media/'.$media->original_name;
                    $zip->addFromString($archivePath, $mediaContent);

                    $manifestAssets[] = [
                        'id' => $media->id,
                        'original_name' => $media->original_name,
                        'file_type' => $media->file_type,
                        'mime_type' => $media->mime_type,
                        'size' => $media->size,
                        'archive_path' => $archivePath,
                        'public_url' => $media->public_url ?? $media->resolved_url,
                    ];
                }
            }

            $manifest = [
                'drophtml_export_version' => '1.0',
                'site_id' => $site->id,
                'slug' => $site->slug,
                'original_name' => $site->original_name,
                'exported_at' => now()->toIso8601String(),
                'includes_cloud_media' => true,
                'stats' => [
                    'code_files_count' => count($allFiles),
                    'cloud_media_count' => count($manifestAssets),
                    'total_media_size_bytes' => array_sum(array_column($manifestAssets, 'size')),
                ],
                'media_assets' => $manifestAssets,
            ];

            $zip->addFromString('export-manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $zip->close();

        $prefixType = $includeMedia ? '-universal-backup-' : '-backup-';
        $downloadFilename = $site->slug.$prefixType.now()->format('Y-m-d').'.zip';

        return response()->download($tempFile, $downloadFilename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Duplicate an existing site to a new site with a unique slug.
     */
    public function duplicateSite(Site $site, User $user, ?string $customSlug = null): Site
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($site->path)) {
            throw new RuntimeException('Source site directory not found.');
        }

        $newSlug = ! empty($customSlug)
            ? Str::slug($customSlug)
            : $this->generateUniqueSlug($site->slug.'-copy');

        if (Site::where('slug', $newSlug)->exists()) {
            $newSlug = $this->generateUniqueSlug($newSlug);
        }

        $siteStorageStats = $this->storageUsageService->getSiteStorageStats($site);
        $this->storageUsageService->enforceQuota($user, $siteStorageStats['total_bytes']);

        $newPath = "sites/{$newSlug}";
        $disk->makeDirectory($newPath);

        $prefix = rtrim($site->path, '/').'/';
        $files = $disk->allFiles($site->path);

        foreach ($files as $file) {
            $rel = str_starts_with($file, $prefix) ? substr($file, strlen($prefix)) : $file;
            $disk->copy($file, "{$newPath}/{$rel}");
        }

        $clonedSite = Site::create([
            'slug' => $newSlug,
            'original_name' => ($site->original_name ?: $site->slug).' (Copy)',
            'path' => $newPath,
            'entry_path' => $site->entry_path,
            'user_id' => $user->id,
            'status' => 'active',
            'is_permanent' => false,
            'expires_at' => now()->addDays(30),
            'views' => 0,
        ]);

        // Duplicate media assets associated with the site
        foreach ($site->mediaAssets as $mediaAsset) {
            if ($mediaAsset->storage_driver === 'code') {
                $relPath = $mediaAsset->metadata['relative_code_path'] ?? basename($mediaAsset->path);
                MediaAsset::create([
                    'user_id' => $user->id,
                    'site_id' => $clonedSite->id,
                    'original_name' => $mediaAsset->original_name,
                    'path' => "{$newPath}/{$relPath}",
                    'disk' => 'public',
                    'storage_driver' => 'code',
                    'file_type' => $mediaAsset->file_type,
                    'mime_type' => $mediaAsset->mime_type,
                    'size' => $mediaAsset->size,
                    'public_url' => url("/s/{$clonedSite->slug}/".ltrim($relPath, '/')),
                    'metadata' => $mediaAsset->metadata,
                ]);
            } else {
                $cloudSourcePath = $mediaAsset->path;
                $cloudFileName = basename($cloudSourcePath);
                $newAssetId = (string) Str::uuid7();
                $newCloudPath = $newAssetId.'.'.strtolower(pathinfo($cloudFileName, PATHINFO_EXTENSION));

                if ($mediaAsset->storageDisk()->exists($cloudSourcePath)) {
                    $stream = $mediaAsset->storageDisk()->readStream($cloudSourcePath);
                    try {
                        Storage::disk('media')->put($newCloudPath, $stream, 'public');
                    } finally {
                        if (is_resource($stream)) {
                            fclose($stream);
                        }
                    }
                    $newPublicUrl = app(MediaStorageService::class)->publicUrlFor($newCloudPath);
                } else {
                    throw new RuntimeException('Source media file missing: '.$cloudSourcePath);
                }

                MediaAsset::create([
                    'id' => $newAssetId,
                    'user_id' => $user->id,
                    'site_id' => $clonedSite->id,
                    'original_name' => $mediaAsset->original_name,
                    'path' => $newCloudPath,
                    'disk' => 'media',
                    'storage_driver' => 'asset_storage',
                    'file_type' => $mediaAsset->file_type,
                    'mime_type' => $mediaAsset->mime_type,
                    'size' => $mediaAsset->size,
                    'public_url' => $newPublicUrl,
                    'metadata' => $mediaAsset->metadata,
                ]);
            }
        }

        $this->activityLogService->log(
            $user,
            $clonedSite,
            'site_duplicated',
            "Duplicated site from {$site->slug} to {$clonedSite->slug}"
        );

        if (! empty($site->webhook_url)) {
            DispatchSiteWebhookJob::dispatch($site, 'site.duplicated', [
                'cloned_site_id' => $clonedSite->id,
                'cloned_site_slug' => $clonedSite->slug,
            ]);
        }

        $this->storageUsageService->calculateSiteStorageBytes($clonedSite);

        return $clonedSite;
    }

    /**
     * Create a ZIP snapshot of current deployed site files for deployment history.
     */
    public function createDeploymentSnapshot(Site $site, Deployment $deployment): ?string
    {
        $publicDisk = Storage::disk('public');
        $localDisk = Storage::disk('local');

        if (! $publicDisk->exists($site->path)) {
            return null;
        }

        $allFiles = $publicDisk->allFiles($site->path);
        if (empty($allFiles)) {
            return null;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'snap_');
        $zip = new ZipArchive;

        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        $prefix = rtrim($site->path, '/').'/';
        $totalBytes = 0;

        foreach ($allFiles as $file) {
            $relativePath = str_starts_with($file, $prefix) ? substr($file, strlen($prefix)) : $file;
            $fullFilePath = $publicDisk->path($file);
            if (file_exists($fullFilePath) && is_readable($fullFilePath)) {
                $zip->addFile($fullFilePath, $relativePath);
                $totalBytes += filesize($fullFilePath);
            }
        }

        $zip->close();

        $snapshotRelPath = "snapshots/{$site->id}/{$deployment->id}.zip";
        $localDisk->put($snapshotRelPath, file_get_contents($tempFile));
        @unlink($tempFile);

        $metadata = $deployment->metadata ?? [];
        $metadata['snapshot_path'] = $snapshotRelPath;
        $metadata['file_count'] = count($allFiles);
        $metadata['total_bytes'] = $totalBytes;

        $deployment->update(['metadata' => $metadata]);

        return $snapshotRelPath;
    }

    /**
     * Deploy new files to an existing site (from upload, CI/CD, or CLI).
     */
    public function deployToExistingSite(
        Site $site,
        UploadedFile $file,
        ?User $actor = null,
        ?string $commitMessage = null,
        string $source = 'upload'
    ): Deployment {
        if ($actor) {
            $this->storageUsageService->enforceQuota($actor, (int) $file->getSize());
        }

        $publicDisk = Storage::disk('public');
        $fullStoragePath = $publicDisk->path($site->path);

        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'zip') {
            $publicDisk->deleteDirectory($site->path);
            $publicDisk->makeDirectory($site->path);
            $this->extractZipSafely($file->getRealPath(), $fullStoragePath);
        } else {
            if ($this->isDangerousExtension($extension)) {
                throw new InvalidArgumentException("Uploading .{$extension} files is not permitted for security reasons.");
            }

            $publicDisk->deleteDirectory($site->path);
            $publicDisk->makeDirectory($site->path);
            $sanitizedFilename = $this->sanitizeFileName($file->getClientOriginalName());
            $file->storeAs($site->path, $sanitizedFilename, 'public');
        }

        $message = $commitMessage ?: 'Redeployed site files via '.$source;

        $site->update(['entry_path' => SiteServingService::detectEntryPath($site->path)]);

        $deployment = $site->deployments()->create([
            'status' => 'success',
            'source' => $source,
            'commit_message' => $message,
            'metadata' => [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ],
        ]);

        $this->createDeploymentSnapshot($site, $deployment);
        $this->storageUsageService->calculateSiteStorageBytes($site);

        // Automatically offload large media (images/videos) to cloud asset storage.
        app(MediaStorageService::class)->offloadLargeAssets($site, $actor);

        if ($actor) {
            $this->activityLogService->log(
                $actor,
                $site,
                'site_redeployed',
                "Redeployed site: {$site->original_name} ({$site->slug})"
            );
        }

        if (! empty($site->webhook_url)) {
            DispatchSiteWebhookJob::dispatch($site, 'site.deployed', [
                'deployment_id' => $deployment->id,
                'source' => $source,
                'commit_message' => $message,
            ]);
        }

        return $deployment;
    }

    /**
     * Rollback a site to a previous deployment snapshot.
     */
    public function rollbackToDeployment(Site $site, Deployment $targetDeployment, ?User $actor = null): Deployment
    {
        if ($targetDeployment->site_id !== $site->id) {
            throw new InvalidArgumentException('Deployment does not belong to this site.');
        }

        $localDisk = Storage::disk('local');
        $snapshotPath = $targetDeployment->metadata['snapshot_path'] ?? "snapshots/{$site->id}/{$targetDeployment->id}.zip";

        if (! $localDisk->exists($snapshotPath)) {
            throw new RuntimeException('Snapshot archive for this deployment was not found.');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'rollback_');
        file_put_contents($tempFile, $localDisk->get($snapshotPath));

        $publicDisk = Storage::disk('public');
        $destinationPath = $publicDisk->path($site->path);

        $publicDisk->deleteDirectory($site->path);
        $publicDisk->makeDirectory($site->path);

        $this->extractZipSafely($tempFile, $destinationPath);
        @unlink($tempFile);

        $site->update(['entry_path' => SiteServingService::detectEntryPath($site->path)]);

        $shortId = substr($targetDeployment->id, 0, 8);
        $newDeployment = $site->deployments()->create([
            'status' => 'success',
            'source' => 'rollback',
            'commit_message' => "Rolled back to deployment #{$shortId}",
            'metadata' => [
                'target_deployment_id' => $targetDeployment->id,
                'target_commit_message' => $targetDeployment->commit_message,
            ],
        ]);

        $this->createDeploymentSnapshot($site, $newDeployment);
        $this->storageUsageService->calculateSiteStorageBytes($site);

        if ($actor) {
            $this->activityLogService->log(
                $actor,
                $site,
                'site_rollback',
                "Rolled back site {$site->slug} to deployment #{$shortId}"
            );
        }

        if (! empty($site->webhook_url)) {
            DispatchSiteWebhookJob::dispatch($site, 'site.rolled_back', [
                'deployment_id' => $newDeployment->id,
                'target_deployment_id' => $targetDeployment->id,
            ]);
        }

        return $newDeployment;
    }

    /**
     * Download a specific deployment ZIP snapshot.
     */
    public function downloadDeploymentZip(Site $site, Deployment $deployment): BinaryFileResponse
    {
        if ($deployment->site_id !== $site->id) {
            abort(404, 'Deployment does not belong to this site.');
        }

        $localDisk = Storage::disk('local');
        $snapshotPath = $deployment->metadata['snapshot_path'] ?? "snapshots/{$site->id}/{$deployment->id}.zip";

        if (! $localDisk->exists($snapshotPath)) {
            abort(404, 'Snapshot file not found for this deployment.');
        }

        $filename = $site->slug.'-deploy-'.substr($deployment->id, 0, 8).'.zip';

        return response()->download($localDisk->path($snapshotPath), $filename, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * Create a site from an associative array of relative file paths and contents.
     * Supports ['index.html' => '...', 'css/style.css' => '...'] or [['path' => '...', 'content' => '...']].
     */
    public function createSiteFromFiles(
        array $files,
        string $name,
        ?User $user = null,
        array $options = []
    ): Site {
        $normalizedFiles = [];
        $totalBytes = 0;

        foreach ($files as $key => $value) {
            if (is_array($value) && isset($value['path'])) {
                $path = (string) $value['path'];
                $content = (string) ($value['content'] ?? '');
            } else {
                $path = (string) $key;
                $content = (string) $value;
            }

            $path = str_replace('\\', '/', trim($path));

            if (str_contains($path, '../') || str_starts_with($path, '/') || str_contains($path, '..\\')) {
                throw new InvalidArgumentException("Security violation: path traversal detected in '{$path}'.");
            }

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if ($this->isDangerousExtension($ext)) {
                throw new InvalidArgumentException("Uploading .{$ext} files is not permitted for security reasons.");
            }

            $basename = strtolower(basename($path));
            if (in_array($basename, ['.env', '.htaccess', '.htpasswd', '.git'], true)) {
                throw new InvalidArgumentException("Uploading forbidden system file '{$path}' is not permitted.");
            }

            $normalizedFiles[$path] = $content;
            $totalBytes += strlen($content);
        }

        if (empty($normalizedFiles)) {
            throw new InvalidArgumentException('No valid files provided for site creation.');
        }

        if ($user) {
            $this->storageUsageService->enforceQuota($user, $totalBytes);
        }

        $slug = ! empty($options['custom_slug'])
            ? Str::slug($options['custom_slug'])
            : $this->generateUniqueSlug($name);

        if (Site::where('slug', $slug)->exists()) {
            $slug = $this->generateUniqueSlug($slug);
        }

        $storagePath = "sites/{$slug}";
        $publicDisk = Storage::disk('public');
        $publicDisk->makeDirectory($storagePath);

        foreach ($normalizedFiles as $relPath => $fileContent) {
            $dir = dirname("{$storagePath}/{$relPath}");
            if (! $publicDisk->exists($dir)) {
                $publicDisk->makeDirectory($dir);
            }
            $publicDisk->put("{$storagePath}/{$relPath}", $fileContent);
        }

        $isPermanent = (bool) ($options['is_permanent'] ?? false);
        $expiresAt = $this->calculateExpiry($user, $isPermanent);

        $site = Site::create([
            'slug' => $slug,
            'original_name' => $name,
            'path' => $storagePath,
            'entry_path' => SiteServingService::detectEntryPath($storagePath),
            'user_id' => $user?->id,
            'status' => 'active',
            'is_permanent' => $isPermanent,
            'expires_at' => $expiresAt,
            'is_password_protected' => (bool) ($options['is_password_protected'] ?? false),
            'site_password' => ! empty($options['site_password']) ? Hash::make($options['site_password']) : null,
        ]);

        $deployment = $site->deployments()->create([
            'status' => 'success',
            'source' => $options['source'] ?? 'api',
            'commit_message' => "Deployed site: {$name} (".count($normalizedFiles).' files)',
            'metadata' => [
                'files_count' => count($normalizedFiles),
                'total_bytes' => $totalBytes,
            ],
        ]);

        $this->createDeploymentSnapshot($site, $deployment);
        $this->storageUsageService->calculateSiteStorageBytes($site);

        app(MediaStorageService::class)->offloadLargeAssets($site, $user);

        if ($user) {
            $this->activityLogService->log(
                $user,
                $site,
                'site_uploaded',
                "Created site from files: {$name} ({$slug})"
            );
        }

        return $site;
    }

    /**
     * Deploy updated content string to an existing site.
     */
    public function deployContentToExistingSite(
        Site $site,
        string $content,
        ?User $actor = null,
        ?string $commitMessage = null,
        string $source = 'api',
        string $filename = 'index.html'
    ): Deployment {
        if ($actor) {
            $this->storageUsageService->enforceQuota($actor, strlen($content));
        }

        $publicDisk = Storage::disk('public');
        $publicDisk->makeDirectory($site->path);
        $publicDisk->put("{$site->path}/{$filename}", $content);

        $message = $commitMessage ?: 'Updated site content via '.$source;
        $site->update(['entry_path' => SiteServingService::detectEntryPath($site->path)]);

        $deployment = $site->deployments()->create([
            'status' => 'success',
            'source' => $source,
            'commit_message' => $message,
            'metadata' => [
                'filename' => $filename,
                'size' => strlen($content),
            ],
        ]);

        $this->createDeploymentSnapshot($site, $deployment);
        $this->storageUsageService->calculateSiteStorageBytes($site);

        if ($actor) {
            $this->activityLogService->log(
                $actor,
                $site,
                'site_redeployed',
                "Redeployed site content: {$site->original_name} ({$site->slug})"
            );
        }

        if (! empty($site->webhook_url)) {
            DispatchSiteWebhookJob::dispatch($site, 'site.deployed', [
                'deployment_id' => $deployment->id,
                'source' => $source,
                'commit_message' => $message,
            ]);
        }

        return $deployment;
    }

    /**
     * Deploy updated files array to an existing site.
     */
    public function deployFilesToExistingSite(
        Site $site,
        array $files,
        ?User $actor = null,
        ?string $commitMessage = null,
        string $source = 'api'
    ): Deployment {
        $normalizedFiles = [];
        $totalBytes = 0;

        foreach ($files as $key => $value) {
            if (is_array($value) && isset($value['path'])) {
                $path = (string) $value['path'];
                $content = (string) ($value['content'] ?? '');
            } else {
                $path = (string) $key;
                $content = (string) $value;
            }

            $path = str_replace('\\', '/', trim($path));

            if (str_contains($path, '../') || str_starts_with($path, '/') || str_contains($path, '..\\')) {
                throw new InvalidArgumentException("Security violation: path traversal detected in '{$path}'.");
            }

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if ($this->isDangerousExtension($ext)) {
                throw new InvalidArgumentException("Uploading .{$ext} files is not permitted for security reasons.");
            }

            $basename = strtolower(basename($path));
            if (in_array($basename, ['.env', '.htaccess', '.htpasswd', '.git'], true)) {
                throw new InvalidArgumentException("Uploading forbidden system file '{$path}' is not permitted.");
            }

            $normalizedFiles[$path] = $content;
            $totalBytes += strlen($content);
        }

        if (empty($normalizedFiles)) {
            throw new InvalidArgumentException('No valid files provided for site deployment.');
        }

        if ($actor) {
            $this->storageUsageService->enforceQuota($actor, $totalBytes);
        }

        $publicDisk = Storage::disk('public');
        $publicDisk->makeDirectory($site->path);

        foreach ($normalizedFiles as $relPath => $fileContent) {
            $dir = dirname("{$site->path}/{$relPath}");
            if (! $publicDisk->exists($dir)) {
                $publicDisk->makeDirectory($dir);
            }
            $publicDisk->put("{$site->path}/{$relPath}", $fileContent);
        }

        $message = $commitMessage ?: 'Redeployed '.count($normalizedFiles).' site files via '.$source;
        $site->update(['entry_path' => SiteServingService::detectEntryPath($site->path)]);

        $deployment = $site->deployments()->create([
            'status' => 'success',
            'source' => $source,
            'commit_message' => $message,
            'metadata' => [
                'files_count' => count($normalizedFiles),
                'total_bytes' => $totalBytes,
            ],
        ]);

        $this->createDeploymentSnapshot($site, $deployment);
        $this->storageUsageService->calculateSiteStorageBytes($site);

        app(MediaStorageService::class)->offloadLargeAssets($site, $actor);

        if ($actor) {
            $this->activityLogService->log(
                $actor,
                $site,
                'site_redeployed',
                "Redeployed site files: {$site->original_name} ({$site->slug})"
            );
        }

        if (! empty($site->webhook_url)) {
            DispatchSiteWebhookJob::dispatch($site, 'site.deployed', [
                'deployment_id' => $deployment->id,
                'source' => $source,
                'commit_message' => $message,
            ]);
        }

        return $deployment;
    }

    /**
     * Calculate expiry time based on user and permanent setting.
     */
    protected function calculateExpiry(?User $user, bool $isPermanent): ?\DateTimeInterface
    {
        if ($isPermanent) {
            return null;
        }

        return $user ? now()->addDays(30) : now()->addHours(24);
    }
}
