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

        $cleanPath = $this->sanitizePath($path);
        $requestPath = '/'.ltrim($path ?? '', '/');
        $disk = Storage::disk('public');

        // Dynamic SEO generation if not provided in site bundle
        if ($cleanPath === 'robots.txt' && ! $disk->fileExists("{$site->path}/robots.txt")) {
            $robotsTxt = "User-agent: *\nAllow: /\nSitemap: ".rtrim($baseUrl, '/')."/sitemap.xml\n";

            return response($robotsTxt, 200, [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        if ($cleanPath === 'sitemap.xml' && ! $disk->fileExists("{$site->path}/sitemap.xml")) {
            $sitemapXml = $this->generateDynamicSitemap($site, $baseUrl);

            return response($sitemapXml, 200, [
                'Content-Type' => 'application/xml; charset=UTF-8',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        $directExists = $cleanPath !== null && $disk->fileExists("{$site->path}/{$cleanPath}");

        // 1. Evaluate database-driven redirect rules if configured
        $dbRedirects = $site->redirects()->where('is_active', true)->get();
        if ($dbRedirects->isNotEmpty()) {
            $dbRules = $dbRedirects->map(function ($r) {
                return [
                    'id' => $r->id,
                    'model' => $r,
                    'from' => $r->from_path,
                    'to' => $r->to_url,
                    'status' => (int) $r->status_code,
                    'force' => true,
                ];
            })->all();

            $matchedDbRedirect = $this->matchRedirect($dbRules, $requestPath, $directExists);

            if ($matchedDbRedirect) {
                $ruleModel = $matchedDbRedirect['rule']['model'] ?? null;
                if ($ruleModel) {
                    $ruleModel->increment('hit_count');
                    $ruleModel->update(['last_accessed_at' => now()]);
                }

                if ($matchedDbRedirect['status'] === 200) {
                    // Internal Rewrite (e.g. SPA fallback /* /index.html 200)
                    $cleanPath = $this->sanitizePath(ltrim($matchedDbRedirect['to'], '/'));
                } else {
                    // HTTP 301, 302, 307, 308 Redirect
                    $this->recordAnalytics(
                        $site,
                        $request,
                        $requestPath,
                        true,
                        $matchedDbRedirect['status'],
                        true,
                        $ruleModel?->id
                    );

                    $to = $matchedDbRedirect['to'];
                    $targetUrl = (str_starts_with($to, 'http://') || str_starts_with($to, 'https://'))
                        ? $to
                        : rtrim($baseUrl, '/').'/'.ltrim($to, '/');

                    $qs = $request->getQueryString();
                    if (! empty($qs) && ! str_contains($targetUrl, '?')) {
                        $targetUrl .= '?'.$qs;
                    }

                    return redirect()->to($targetUrl, $matchedDbRedirect['status']);
                }
            }
        }

        // 2. Evaluate file-based _redirects if configured
        if ($disk->fileExists("{$site->path}/_redirects")) {
            $redirectRules = $this->parseRedirectRules($disk->get("{$site->path}/_redirects"));
            $matchedRedirect = $this->matchRedirect($redirectRules, $requestPath, $directExists);

            if ($matchedRedirect) {
                if ($matchedRedirect['status'] === 200) {
                    // Internal Rewrite (e.g. SPA fallback /* /index.html 200)
                    $cleanPath = $this->sanitizePath(ltrim($matchedRedirect['to'], '/'));
                } else {
                    // HTTP 301, 302, 307, 308 Redirect
                    $this->recordAnalytics(
                        $site,
                        $request,
                        $requestPath,
                        true,
                        $matchedRedirect['status'],
                        true,
                        null
                    );

                    $to = $matchedRedirect['to'];
                    $targetUrl = (str_starts_with($to, 'http://') || str_starts_with($to, 'https://'))
                        ? $to
                        : rtrim($baseUrl, '/').'/'.ltrim($to, '/');

                    $qs = $request->getQueryString();
                    if (! empty($qs) && ! str_contains($targetUrl, '?')) {
                        $targetUrl .= '?'.$qs;
                    }

                    return redirect()->to($targetUrl, $matchedRedirect['status']);
                }
            }
        }

        $resolvedPath = $this->resolveFilePath($site, $cleanPath);

        // SPA fallback mode: if route not matched and site has is_spa enabled
        if ($resolvedPath === null && $site->is_spa) {
            $ext = strtolower(pathinfo($cleanPath ?? '', PATHINFO_EXTENSION));
            if (empty($ext) || in_array($ext, ['html', 'htm'], true)) {
                if ($disk->fileExists("{$site->path}/index.html")) {
                    $resolvedPath = 'index.html';
                }
            }
        }

        $siteRoot = $disk->path($site->path);

        if ($resolvedPath === null) {
            $this->recordAnalytics($site, $request, $requestPath, false, 404);

            return app(SiteErrorPageService::class)->response($request, $site, 404, $baseUrl);
        }

        $fullPath = $disk->path("{$site->path}/{$resolvedPath}");

        // Strict realpath boundary check against directory traversal
        $realSiteRoot = realpath($siteRoot);
        $realFilePath = realpath($fullPath);

        if ($realSiteRoot === false || $realFilePath === false || ! str_starts_with($realFilePath, $realSiteRoot.DIRECTORY_SEPARATOR)) {
            $this->recordAnalytics($site, $request, $requestPath, false, 404);

            return app(SiteErrorPageService::class)->response($request, $site, 404, $baseUrl);
        }

        $extension = strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION));
        $isHtml = in_array($extension, ['html', 'htm'], true);

        // Record visitor analytics
        $statusCode = 200;
        $this->recordAnalytics($site, $request, $path ?? $resolvedPath, $isHtml, $statusCode);

        $lastModified = filemtime($realFilePath);
        $fileSize = filesize($realFilePath);
        $etag = '"'.md5($lastModified.$fileSize).'"';

        // Base response headers
        $responseHeaders = [
            'Content-Type' => $this->resolveMimeType($extension, "{$site->path}/{$resolvedPath}"),
            'ETag' => $etag,
            'Cache-Control' => $isHtml ? 'no-cache, must-revalidate' : 'public, max-age=86400, stale-while-revalidate=604800',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ];

        // Apply custom _headers rules if present
        if ($disk->fileExists("{$site->path}/_headers")) {
            $headerBlocks = $this->parseHeaderRules($disk->get("{$site->path}/_headers"));
            $customHeaders = $this->matchHeaders($headerBlocks, $requestPath);
            foreach ($customHeaders as $hKey => $hVal) {
                $responseHeaders[$hKey] = $hVal;
            }
        }

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304, [
                'ETag' => $etag,
                'Cache-Control' => $responseHeaders['Cache-Control'],
            ]);
        }

        $fileContent = file_get_contents($realFilePath);

        if ($isHtml) {
            $fileContent = $this->injectBaseTag($fileContent, $baseUrl);
        }

        $statusCode = 200;

        return response($fileContent, $statusCode, $responseHeaders);
    }

    /**
     * Unlock a password protected site.
     */
    public function unlock(Request $request, Site $site, string $fallbackUrl): SymfonyResponse
    {
        $inputPassword = (string) $request->input('password');

        $isValid = false;
        if (! empty($site->site_password)) {
            $isValid = Hash::check($inputPassword, $site->site_password);
        }

        if (! $isValid) {
            return back()->withInput()->with('error', 'Incorrect password. Please try again.');
        }

        $request->session()->put("site_unlocked_{$site->id}", true);

        $redirectTo = $this->sanitizeRedirectTarget($request, $request->input('redirect_to'), $fallbackUrl);

        return redirect()->to($redirectTo);
    }

    /**
     * Restrict the post-unlock redirect to local paths or trusted hosts to prevent open redirects.
     */
    private function sanitizeRedirectTarget(Request $request, mixed $target, string $fallbackUrl): string
    {
        if (! is_string($target) || $target === '') {
            return $fallbackUrl;
        }

        // Local relative paths are safe as long as they cannot escape the origin ("//", "/\").
        if (str_starts_with($target, '/') && preg_match('#^//|^/\\\\#', $target) !== 1) {
            return $target;
        }

        $allowedHosts = array_filter([
            $request->host(),
            parse_url(config('app.url'), PHP_URL_HOST),
        ]);

        $targetHost = parse_url($target, PHP_URL_HOST);

        if (is_string($targetHost) && in_array($targetHost, $allowedHosts, true)) {
            return $target;
        }

        return $fallbackUrl;
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
            return $this->resolveEntryPath($site);
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
     * Resolve (and cache) the entry document served for the site root.
     */
    public function resolveEntryPath(Site $site): string
    {
        $disk = Storage::disk('public');

        if (! empty($site->entry_path) && $disk->fileExists("{$site->path}/{$site->entry_path}")) {
            return $site->entry_path;
        }

        $entryPath = self::detectEntryPath($site->path);

        if ($entryPath === null) {
            abort(404, 'No HTML file found in this site.');
        }

        $site->forceFill(['entry_path' => $entryPath])->saveQuietly();

        return $entryPath;
    }

    /**
     * Determine the entry document for a stored site directory.
     */
    public static function detectEntryPath(string $storagePath): ?string
    {
        $disk = Storage::disk('public');

        if ($disk->fileExists("{$storagePath}/index.html")) {
            return 'index.html';
        }

        $htmlFiles = array_values(array_filter($disk->allFiles($storagePath), function ($f) {
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));

            return in_array($ext, ['html', 'htm'], true);
        }));

        if (empty($htmlFiles)) {
            return null;
        }

        $prefix = rtrim($storagePath, '/').'/';

        return str_starts_with($htmlFiles[0], $prefix)
            ? substr($htmlFiles[0], strlen($prefix))
            : basename($htmlFiles[0]);
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

        // Disallow internal config files like _redirects and _headers from direct download
        $targetFile = strtolower(end($parts));
        if (in_array($targetFile, ['_redirects', '_headers', 'web.config', 'nginx.conf', '.env'], true)) {
            abort(404, 'File not found.');
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
    protected function recordAnalytics(
        Site $site,
        Request $request,
        ?string $path,
        bool $isHtml,
        int $statusCode = 200,
        bool $isRedirect = false,
        ?string $redirectId = null
    ): void {
        $metadata = $this->analyticsService->extractMetadata(
            $request,
            $path,
            $isHtml,
            $statusCode,
            $isRedirect,
            $redirectId
        );

        $site->siteViews()->create($metadata);

        if ($isHtml && ! $isRedirect && $statusCode === 200) {
            $site->increment('views');
        }
    }

    /**
     * Generate dynamic XML sitemap for a static site.
     */
    public function generateDynamicSitemap(Site $site, string $baseUrl): string
    {
        $disk = Storage::disk('public');
        $cleanBaseUrl = rtrim($baseUrl, '/');

        $allFiles = $disk->exists($site->path) ? $disk->allFiles($site->path) : [];
        $prefix = rtrim($site->path, '/').'/';

        $urls = [];

        foreach ($allFiles as $file) {
            $relPath = str_starts_with($file, $prefix) ? substr($file, strlen($prefix)) : $file;
            $ext = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));

            if (! in_array($ext, ['html', 'htm'], true)) {
                continue;
            }

            // Exclude 404 error page from sitemap
            if (basename($relPath) === '404.html' || basename($relPath) === '404.htm') {
                continue;
            }

            $fullFilePath = $disk->path($file);
            $lastMod = file_exists($fullFilePath) ? date('Y-m-d', filemtime($fullFilePath)) : date('Y-m-d');

            if ($relPath === 'index.html' || $relPath === 'index.htm') {
                $loc = $cleanBaseUrl.'/';
                $priority = '1.0';
            } else {
                $cleanUrlPath = preg_replace('/\.html?$/', '', $relPath);
                $loc = $cleanBaseUrl.'/'.$cleanUrlPath;
                $priority = '0.8';
            }

            $urls[] = [
                'loc' => htmlspecialchars($loc, ENT_XML1, 'UTF-8'),
                'lastmod' => $lastMod,
                'priority' => $priority,
            ];
        }

        // If no HTML files found, at least provide root
        if (empty($urls)) {
            $urls[] = [
                'loc' => htmlspecialchars($cleanBaseUrl.'/', ENT_XML1, 'UTF-8'),
                'lastmod' => date('Y-m-d'),
                'priority' => '1.0',
            ];
        }

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$url['loc']}</loc>\n";
            $xml .= "    <lastmod>{$url['lastmod']}</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>{$url['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= "</urlset>\n";

        return $xml;
    }

    /**
     * Parse _redirects file content into structured rules.
     */
    public function parseRedirectRules(string $content): array
    {
        $rules = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $tokens = preg_split('/\s+/', $line);
            if (count($tokens) < 2) {
                continue;
            }

            $from = $tokens[0];
            $to = $tokens[1];
            $statusToken = $tokens[2] ?? '301';
            $force = str_ends_with($statusToken, '!');
            $cleanStatus = rtrim($statusToken, '!');
            $status = is_numeric($cleanStatus) ? (int) $cleanStatus : 301;

            $rules[] = [
                'from' => $from,
                'to' => $to,
                'status' => $status,
                'force' => $force,
            ];
        }

        return $rules;
    }

    /**
     * Match request path against redirect rules.
     */
    public function matchRedirect(array $rules, string $requestPath, bool $directFileExists): ?array
    {
        $normalized = '/'.ltrim($requestPath, '/');

        foreach ($rules as $rule) {
            $from = '/'.ltrim($rule['from'], '/');

            // 200 rewrites do not shadow existing files unless forced (!)
            if ($rule['status'] === 200 && ! ($rule['force'] ?? false) && $directFileExists) {
                continue;
            }

            // Exact path match
            if ($from === $normalized) {
                return [
                    'to' => $rule['to'],
                    'status' => $rule['status'],
                    'rule' => $rule,
                ];
            }

            // Wildcard splat (*) or named parameter (:param) match
            if (str_contains($from, '*') || str_contains($from, ':')) {
                $pattern = preg_quote($from, '#');
                $pattern = str_replace('\\*', '(?P<splat>.*)', $pattern);
                $pattern = preg_replace('/\\\:([a-zA-Z0-9_]+)/', '(?P<$1>[^/]+)', $pattern);

                if (preg_match('#^'.$pattern.'$#', $normalized, $matches)) {
                    $target = $rule['to'];
                    foreach ($matches as $key => $val) {
                        if (is_string($key)) {
                            $target = str_replace(':'.$key, $val, $target);
                            if ($key === 'splat') {
                                $target = str_replace('*', $val, $target);
                            }
                        }
                    }

                    return [
                        'to' => $target,
                        'status' => $rule['status'],
                        'rule' => $rule,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Parse _headers file content into path-based header blocks.
     */
    public function parseHeaderRules(string $content): array
    {
        $blocks = [];
        $currentPattern = null;
        $currentHeaders = [];

        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $rawLine = rtrim($line, "\r\n");
            $trimmed = trim($rawLine);

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            // A line starting with non-whitespace is a new pattern
            if (! str_starts_with($rawLine, ' ') && ! str_starts_with($rawLine, "\t")) {
                if ($currentPattern !== null && ! empty($currentHeaders)) {
                    $blocks[] = [
                        'pattern' => $currentPattern,
                        'headers' => $currentHeaders,
                    ];
                }
                $currentPattern = $trimmed;
                $currentHeaders = [];
            } else {
                $parts = explode(':', $trimmed, 2);
                if (count($parts) === 2 && $currentPattern !== null) {
                    $name = trim($parts[0]);
                    $val = trim($parts[1]);
                    if ($name !== '') {
                        $currentHeaders[$name] = $val;
                    }
                }
            }
        }

        if ($currentPattern !== null && ! empty($currentHeaders)) {
            $blocks[] = [
                'pattern' => $currentPattern,
                'headers' => $currentHeaders,
            ];
        }

        return $blocks;
    }

    /**
     * Match custom headers by path pattern.
     */
    public function matchHeaders(array $blocks, string $requestPath): array
    {
        $headers = [];
        $normalized = '/'.ltrim($requestPath, '/');

        foreach ($blocks as $block) {
            $pattern = '/'.ltrim($block['pattern'], '/');

            $isMatch = false;
            if ($pattern === '/*' || $pattern === $normalized) {
                $isMatch = true;
            } elseif (str_contains($pattern, '*')) {
                $regex = preg_quote($pattern, '#');
                $regex = str_replace('\\*', '.*', $regex);
                if (preg_match('#^'.$regex.'$#', $normalized)) {
                    $isMatch = true;
                }
            }

            if ($isMatch) {
                foreach ($block['headers'] as $name => $value) {
                    $headers[$name] = $value;
                }
            }
        }

        return $headers;
    }
}
