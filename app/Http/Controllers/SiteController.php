<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnlockSiteRequest;
use App\Models\Domain;
use App\Models\Site;
use App\Services\SiteServingService;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function __construct(
        protected SiteServingService $siteServingService
    ) {}

    /**
     * Show a site via path-based URL (/s/{slug}/{path?}).
     */
    public function show(Request $request, string $slug, ?string $path = null)
    {
        $site = Site::where('slug', $slug)->firstOrFail();
        $unlockAction = route('site.unlock', ['slug' => $slug]);
        $baseUrl = url("/s/{$slug}").'/';

        return $this->siteServingService->serve($request, $site, $path, $unlockAction, $baseUrl);
    }

    /**
     * Unlock a password-protected site via path-based URL.
     */
    public function unlock(UnlockSiteRequest $request, string $slug)
    {
        $site = Site::where('slug', $slug)->firstOrFail();
        $fallbackUrl = url("/s/{$slug}");

        return $this->siteServingService->unlock($request, $site, $fallbackUrl);
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

        return $this->siteServingService->serve($request, $site, $path, $unlockAction, $baseUrl);
    }

    /**
     * Unlock a password-protected site via subdomain.
     */
    public function unlockSubdomain(UnlockSiteRequest $request, string $slug)
    {
        $site = Site::where('slug', $slug)->firstOrFail();
        $host = $request->getHost();
        $scheme = $request->getScheme();
        $fallbackUrl = "{$scheme}://{$host}/";

        return $this->siteServingService->unlock($request, $site, $fallbackUrl);
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

        return $this->siteServingService->serve($request, $site, $path, $unlockAction, $baseUrl);
    }

    /**
     * Unlock a password-protected site via custom domain.
     */
    public function unlockCustomDomain(UnlockSiteRequest $request, string $custom_domain)
    {
        $domain = Domain::where('domain', $custom_domain)->firstOrFail();
        $site = $domain->site;

        if (! $site) {
            abort(404, 'Site not found.');
        }

        $scheme = $request->getScheme();
        $fallbackUrl = "{$scheme}://{$custom_domain}/";

        return $this->siteServingService->unlock($request, $site, $fallbackUrl);
    }
}
