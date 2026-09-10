<?php

namespace App\Services;

use App\Models\Site;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
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

    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Create a site from an uploaded file (ZIP archive or single HTML/asset file).
     */
    public function createSiteFromUpload(
        UploadedFile $file,
        string $originalName,
        ?User $user = null,
        array $options = []
    ): Site {
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
        $slug = $this->generateUniqueSlug($name);
        $storagePath = "sites/{$slug}";

        Storage::disk('public')->makeDirectory($storagePath);
        Storage::disk('public')->put("{$storagePath}/index.html", $content);

        $isPermanent = (bool) ($options['is_permanent'] ?? false);
        $expiresAt = $this->calculateExpiry($user, $isPermanent);

        $site = Site::create([
            'slug' => $slug,
            'original_name' => $name,
            'path' => $storagePath,
            'user_id' => $user?->id,
            'status' => 'active',
            'is_permanent' => $isPermanent,
            'expires_at' => $expiresAt,
            'is_password_protected' => (bool) ($options['is_password_protected'] ?? false),
            'site_password' => ! empty($options['site_password']) ? Hash::make($options['site_password']) : null,
        ]);

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
        }

        if ($actor) {
            $this->activityLogService->log(
                $actor,
                null,
                'site_deleted',
                "Deleted site: {$site->original_name} ({$site->slug})"
            );
        }

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
