<?php

namespace App\Services;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SiteServingService
{
    protected AnalyticsService $analyticsService;

    public function __construct(?AnalyticsService $analyticsService = null)
    {
        $this->analyticsService = $analyticsService ?? app(AnalyticsService::class);
    }

    protected const MIME_TYPES = [
        'html' => 'text/html; charset=UTF-8',
        'htm' => 'text/html; charset=UTF-8',
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'mjs' => 'application/javascript; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'otf' => 'font/otf',
        'eot' => 'application/vnd.ms-fontobject',
        'pdf' => 'application/pdf',
        'xml' => 'application/xml',
        'txt' => 'text/plain; charset=UTF-8',
        'wasm' => 'application/wasm',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
    ];

    /**
     * Serve content for a given site.
     */
    public function serve(Request $request, Site $site, ?string $path, string $unlockAction, string $baseUrl): SymfonyResponse
    {
        if ($site->status !== 'active') {
            abort(403, 'This site is currently inactive or not deployed.');
        }

        // Check password protection
        if ($site->is_password_protected && ! empty($site->site_password)) {
            if (! $request->session()->get("site_unlocked_{$site->id}")) {
                return response()->view('pages.site-password', [
                    'site' => $site,
                    'unlockAction' => $unlockAction,
                    'redirectTo' => $request->fullUrl(),
                ], 401);
            }
        }

        $resolvedPath = $this->resolveFilePath($site, $path);
        $disk = Storage::disk('public');
        $siteRoot = $disk->path($site->path);

        $isCustom404 = false;

        if ($resolvedPath === null) {
            if ($disk->fileExists("{$site->path}/404.html")) {
                $resolvedPath = '404.html';
                $isCustom404 = true;
            } else {
                abort(404, 'File not found.');
            }
        }

        $fullPath = $disk->path("{$site->path}/{$resolvedPath}");

        // Strict realpath boundary check against directory traversal
        $realSiteRoot = realpath($siteRoot);
        $realFilePath = realpath($fullPath);

        if ($realSiteRoot === false || $realFilePath === false || ! str_starts_with($realFilePath, $realSiteRoot)) {
            if (! $isCustom404 && $disk->fileExists("{$site->path}/404.html")) {
                $resolvedPath = '404.html';
                $realFilePath = realpath($disk->path("{$site->path}/404.html"));
                $isCustom404 = true;
            } else {
                abort(404, 'File not found.');
            }
        }

        $extension = strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION));
        $isHtml = in_array($extension, ['html', 'htm'], true);

        // Record visitor analytics
        $this->recordAnalytics($site, $request, $path ?? $resolvedPath, $isHtml);

        $lastModified = filemtime($realFilePath);
        $fileSize = filesize($realFilePath);
        $etag = '"'.md5($lastModified.$fileSize).'"';

        if (! $isCustom404 && $request->header('If-None-Match') === $etag) {
            return response('', 304, [
                'ETag' => $etag,
                'Cache-Control' => $isHtml ? 'no-cache, must-revalidate' : 'public, max-age=86400, stale-while-revalidate=604800',
            ]);
        }

        $fileContent = file_get_contents($realFilePath);
        $mime = $this->resolveMimeType($extension, "{$site->path}/{$resolvedPath}");

        if ($isHtml) {
            $fileContent = $this->injectBaseTag($fileContent, $baseUrl);
        }

        $statusCode = $isCustom404 ? 404 : 200;

        return response($fileContent, $statusCode, [
            'Content-Type' => $mime,
            'ETag' => $etag,
            'Cache-Control' => $isHtml ? 'no-cache, must-revalidate' : 'public, max-age=86400, stale-while-revalidate=604800',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ]);
    }

    /**
     * Unlock a password protected site.
     */
    public function unlock(Request $request, Site $site, string $fallbackUrl): SymfonyResponse
    {
        $inputPassword = (string) $request->input('password');

        $isValid = false;
        if (! empty($site->site_password)) {
            $isValid = Hash::check($inputPassword, $site->site_password) || ($inputPassword === $site->site_password);
        }

        if (! $isValid) {
            return back()->withInput()->with('error', 'Incorrect password. Please try again.');
        }

        $request->session()->put("site_unlocked_{$site->id}", true);

        $redirectTo = $request->input('redirect_to', $fallbackUrl);

        return redirect()->to($redirectTo);
    }

    /**
     * Resolve the requested relative file path within the site directory.
     */
    public function resolveFilePath(Site $site, ?string $path): ?string
    {
        $disk = Storage::disk('public');

        // Sanitize path
        $cleanPath = $this->sanitizePath($path);

        if (empty($cleanPath)) {
            if ($disk->exists("{$site->path}/index.html")) {
                return 'index.html';
            }

            $allFiles = $disk->allFiles($site->path);
            $htmlFiles = array_filter($allFiles, function ($f) {
                $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));

                return in_array($ext, ['html', 'htm'], true);
            });

            if (! empty($htmlFiles)) {
                $firstHtml = reset($htmlFiles);
                $prefix = rtrim($site->path, '/').'/';

                return str_starts_with($firstHtml, $prefix) ? substr($firstHtml, strlen($prefix)) : basename($firstHtml);
            }

            abort(404, 'No HTML file found in this site.');
        }

        // Direct file match
        if ($disk->fileExists("{$site->path}/{$cleanPath}")) {
            return $cleanPath;
        }

        // Clean URL extension fallback (e.g. /pricing -> /pricing.html)
        if ($disk->fileExists("{$site->path}/{$cleanPath}.html")) {
            return "{$cleanPath}.html";
        }

        // Directory index fallback (e.g. /docs -> /docs/index.html)
        if ($disk->fileExists("{$site->path}/{$cleanPath}/index.html")) {
            return rtrim($cleanPath, '/').'/index.html';
        }

        return null;
    }

    /**
     * Sanitize requested path string to prevent path traversal and hidden file access.
     */
    public function sanitizePath(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        // Normalize separators
        $normalized = str_replace('\\', '/', trim($path));

        // Strip dangerous sequences
        $parts = array_filter(explode('/', $normalized), function ($part) {
            return $part !== '' && $part !== '.' && $part !== '..';
        });

        if (empty($parts)) {
            return null;
        }

        // Disallow hidden files (starting with dot)
        foreach ($parts as $part) {
            if (str_starts_with($part, '.')) {
                abort(404, 'Access denied.');
            }
        }

        return implode('/', $parts);
    }

    /**
     * Resolve MIME type for a file.
     */
    public function resolveMimeType(string $extension, string $fullStoragePath): string
    {
        if (isset(self::MIME_TYPES[$extension])) {
            return self::MIME_TYPES[$extension];
        }

        $detected = Storage::disk('public')->mimeType($fullStoragePath);

        return $detected ?: 'application/octet-stream';
    }

    /**
     * Inject <base> tag into HTML content if missing.
     */
    public function injectBaseTag(string $html, string $baseUrl): string
    {
        if (str_contains($html, '<base ')) {
            return $html;
        }

        $baseTag = "<base href=\"{$baseUrl}\">";

        if (str_contains($html, '<head>')) {
            return str_replace('<head>', "<head>\n    {$baseTag}", $html);
        }

        if (str_contains($html, '<HEAD>')) {
            return str_replace('<HEAD>', "<HEAD>\n    {$baseTag}", $html);
        }

        return "{$baseTag}\n".$html;
    }

    /**
     * Record analytics view for a site visit.
     */
    protected function recordAnalytics(Site $site, Request $request, ?string $path, bool $isHtml): void
    {
        $metadata = $this->analyticsService->extractMetadata($request, $path, $isHtml);

        $site->siteViews()->create($metadata);

        if ($isHtml) {
            $site->increment('views');
        }
    }
}
