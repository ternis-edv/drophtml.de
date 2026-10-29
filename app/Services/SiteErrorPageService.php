<?php

namespace App\Services;

use App\Http\Controllers\SiteController;
use App\Models\Domain;
use App\Models\Site;
use App\Models\SiteErrorPage;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SiteErrorPageService
{
    /** Resolve only public hosted-site routes, never dashboard/API errors. */
    public function context(Request $request): ?array
    {
        $route = $request->route();
        if (! $route instanceof Route || ! str_starts_with($route->getActionName(), SiteController::class.'@')) {
            return null;
        }
        $slug = $route->parameter('slug');
        if ($slug !== null) {
            $site = Site::where('slug', $slug)->first();
            $pathBased = in_array($route->getActionMethod(), ['show', 'unlock'], true);
            $baseUrl = $request->getSchemeAndHttpHost().($pathBased ? '/s/'.rawurlencode($slug).'/' : '/');
        } else {
            $site = Domain::where('domain', $route->parameter('custom_domain'))->first()?->site;
            $baseUrl = $request->getSchemeAndHttpHost().'/';
        }

        return compact('site', 'baseUrl');
    }

    public function response(Request $request, ?Site $site, int $status, string $baseUrl): Response
    {
        $headers = [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
            'X-Robots-Tag' => 'noindex, nofollow',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ];

        if ($status === 503) {
            $headers['Retry-After'] = '60';
        }
        if ($request->expectsJson()) {
            $headers['Content-Type'] = 'application/json';

            return response()->json(['message' => Response::$statusTexts[$status] ?? 'Request failed'], $status, $headers);
        }

        // Entitlement is checked again at serving time, including after a downgrade.
        if ($site?->user?->hasPaidPlan() && in_array($status, SiteErrorPage::STATUSES, true)) {
            $page = $site->errorPages()->where('status_code', $status)->where('enabled', true)->first();
            if ($page && trim($page->html) !== '') {
                return response($this->renderHtml($page->html, $site, $status, $baseUrl, $request->getPathInfo()), $status, $headers);
            }
        }

        // Preserve the existing bundled 404.html feature on every plan.
        if ($site && $status === 404) {
            $disk = Storage::disk('public');
            $root = realpath($disk->path($site->path));
            $file = realpath($disk->path($site->path.'/404.html'));
            if ($root !== false && $file !== false && is_file($file) && str_starts_with($file, $root.DIRECTORY_SEPARATOR)) {
                return response(app(SiteServingService::class)->injectBaseTag(file_get_contents($file), $baseUrl), 404, $headers);
            }
        }

        return response()->view('errors.site', [
            'site' => $site, 'status' => $status, 'baseUrl' => $baseUrl,
            'requestedPath' => $request->getPathInfo(),
        ], $status, $headers);
    }

    public function renderHtml(string $html, Site $site, int $status, string $baseUrl, string $path): string
    {
        $html = strtr($html, [
            '{{site_name}}' => e($site->original_name ?: $site->slug),
            '{{site_url}}' => e($baseUrl),
            '{{status_code}}' => (string) $status,
            '{{requested_path}}' => e($path),
        ]);

        // Stored HTML is static content, never evaluated as Blade or PHP.
        return app(SiteServingService::class)->injectBaseTag($html, $baseUrl);
    }

    public function starter(int $status): string
    {
        $title = match ($status) {
            403 => 'This page is unavailable',
            500 => 'Something went wrong',
            503 => 'We will be right back',
            default => 'This page is off the map',
        };

        return view('errors.custom-starter', compact('title', 'status'))->render();
    }
}
