<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SiteController extends Controller
{
    /**
     * Show a site via path-based URL (/s/{slug}/{path?}).
     */
    public function show(Request $request, string $slug, ?string $path = null)
    {
        $site = Site::where('slug', $slug)->firstOrFail();
        $unlockAction = route('site.unlock', ['slug' => $slug]);
        $baseUrl = url("/s/{$slug}").'/';

        return $this->serveSiteContent($request, $site, $path, $unlockAction, $baseUrl);
    }

    /**
     * Unlock a password-protected site via path-based URL.
     */
    public function unlock(Request $request, string $slug)
    {
        $site = Site::where('slug', $slug)->firstOrFail();
        $fallbackUrl = url("/s/{$slug}");

        return $this->handleUnlock($request, $site, $fallbackUrl);
    }

    /**
     * Show a site via subdomain URL ({slug}.domain.com/{path?}).
     */
    public function showSubdomain(Request $request, string $slug, ?string $path = null)
    {
        $site = Site::where('slug', $slug)->firstOrFail();
        $host = $request->getHost();
        $scheme = $request->getScheme();
        $unlockAction = "{$scheme}://{$host}/_unlock";
        $baseUrl = "{$scheme}://{$host}/";

        return $this->serveSiteContent($request, $site, $path, $unlockAction, $baseUrl);
    }

    /**
     * Unlock a password-protected site via subdomain.
     */
    public function unlockSubdomain(Request $request, string $slug)
    {
        $site = Site::where('slug', $slug)->firstOrFail();
        $host = $request->getHost();
        $scheme = $request->getScheme();
        $fallbackUrl = "{$scheme}://{$host}/";

        return $this->handleUnlock($request, $site, $fallbackUrl);
    }

    /**
     * Show a site via custom domain.
     */
    public function showCustomDomain(Request $request, string $custom_domain, ?string $path = null)
    {
        $domain = Domain::where('domain', $custom_domain)->firstOrFail();
        $site = $domain->site;

        if (! $site) {
            abort(404, 'Site not found for this custom domain.');
        }

        $scheme = $request->getScheme();
        $unlockAction = "{$scheme}://{$custom_domain}/_unlock";
        $baseUrl = "{$scheme}://{$custom_domain}/";

        return $this->serveSiteContent($request, $site, $path, $unlockAction, $baseUrl);
    }

    /**
     * Unlock a password-protected site via custom domain.
     */
    public function unlockCustomDomain(Request $request, string $custom_domain)
    {
        $domain = Domain::where('domain', $custom_domain)->firstOrFail();
        $site = $domain->site;

        if (! $site) {
            abort(404, 'Site not found.');
        }

        $scheme = $request->getScheme();
        $fallbackUrl = "{$scheme}://{$custom_domain}/";

        return $this->handleUnlock($request, $site, $fallbackUrl);
    }

    /**
     * Core logic to serve site content.
     */
    protected function serveSiteContent(Request $request, Site $site, ?string $path, string $unlockAction, string $baseUrl)
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

        // Sanitize path to prevent directory traversal
        $requestedPath = $path ? ltrim(str_replace(['../', '..\\'], '', $path), '/') : null;

        if (empty($requestedPath)) {
            if (Storage::disk('public')->exists("{$site->path}/index.html")) {
                $requestedPath = 'index.html';
            } else {
                $files = Storage::disk('public')->allFiles($site->path);
                $htmlFiles = array_filter($files, fn ($f) => str_ends_with(strtolower($f), '.html') || str_ends_with(strtolower($f), '.htm'));

                if (! empty($htmlFiles)) {
                    // Extract relative path from site root
                    $firstHtml = reset($htmlFiles);
                    $requestedPath = str_starts_with($firstHtml, $site->path.'/')
                        ? substr($firstHtml, strlen($site->path) + 1)
                        : basename($firstHtml);
                } else {
                    abort(404, 'No HTML file found in this site.');
                }
            }
        }

        $fullPath = "{$site->path}/{$requestedPath}";

        if (! Storage::disk('public')->fileExists($fullPath)) {
            if (Storage::disk('public')->fileExists("{$fullPath}/index.html")) {
                $requestedPath = rtrim($requestedPath, '/').'/index.html';
                $fullPath = "{$site->path}/{$requestedPath}";
            } else {
                abort(404, 'File not found.');
            }
        }

        $extension = strtolower(pathinfo($requestedPath, PATHINFO_EXTENSION));
        $isHtml = in_array($extension, ['html', 'htm']);

        // Record detailed analytics
        $site->siteViews()->create([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'is_quiet' => ! $isHtml,
        ]);

        if ($isHtml) {
            $site->increment('views');
        }

        $fileContent = Storage::disk('public')->get($fullPath);
        $mime = $this->resolveMimeType($extension, $fullPath);

        if ($isHtml) {
            // Inject <base> tag if not present
            if (! str_contains($fileContent, '<base ')) {
                $baseTag = "<base href=\"{$baseUrl}\">";
                if (str_contains($fileContent, '<head>')) {
                    $fileContent = str_replace('<head>', "<head>\n    {$baseTag}", $fileContent);
                } elseif (str_contains($fileContent, '<HEAD>')) {
                    $fileContent = str_replace('<HEAD>', "<HEAD>\n    {$baseTag}", $fileContent);
                } else {
                    $fileContent = "{$baseTag}\n".$fileContent;
                }
            }
        }

        return response($fileContent, 200, [
            'Content-Type' => $mime,
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Handle site password verification.
     */
    protected function handleUnlock(Request $request, Site $site, string $fallbackUrl)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $inputPassword = $request->input('password');

        $isValid = Hash::check($inputPassword, $site->site_password) || ($inputPassword === $site->site_password);

        if (! $isValid) {
            return back()->withInput()->with('error', 'Incorrect password. Please try again.');
        }

        $request->session()->put("site_unlocked_{$site->id}", true);

        $redirectTo = $request->input('redirect_to', $fallbackUrl);

        return redirect()->to($redirectTo);
    }

    /**
     * Resolve reliable MIME types for web files.
     */
    protected function resolveMimeType(string $extension, string $fullPath): string
    {
        $mimes = [
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
            'pdf' => 'application/pdf',
            'xml' => 'application/xml',
            'txt' => 'text/plain; charset=UTF-8',
        ];

        if (isset($mimes[$extension])) {
            return $mimes[$extension];
        }

        $detected = Storage::disk('public')->mimeType($fullPath);

        return $detected ?: 'application/octet-stream';
    }
}
