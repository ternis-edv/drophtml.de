<?php

namespace App\Services;

use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsService
{
    /**
     * Map of common country codes to display names.
     */
    protected const COUNTRY_NAMES = [
        'US' => 'United States',
        'DE' => 'Germany',
        'GB' => 'United Kingdom',
        'FR' => 'France',
        'CA' => 'Canada',
        'AU' => 'Australia',
        'NL' => 'Netherlands',
        'IN' => 'India',
        'JP' => 'Japan',
        'BR' => 'Brazil',
        'ES' => 'Spain',
        'IT' => 'Italy',
        'CH' => 'Switzerland',
        'SE' => 'Sweden',
        'PL' => 'Poland',
        'AT' => 'Austria',
        'BE' => 'Belgium',
        'DK' => 'Denmark',
        'FI' => 'Finland',
        'NO' => 'Norway',
        'IE' => 'Ireland',
        'SG' => 'Singapore',
        'NZ' => 'New Zealand',
        'MX' => 'Mexico',
        'ZA' => 'South Africa',
        'KR' => 'South Korea',
        'CN' => 'China',
        'PT' => 'Portugal',
        'CZ' => 'Czech Republic',
        'RO' => 'Romania',
    ];

    /**
     * Parse incoming request to extract rich analytics metadata.
     */
    public function extractMetadata(
        Request $request,
        ?string $path,
        bool $isHtml,
        int $statusCode = 200,
        bool $isRedirect = false,
        ?string $redirectId = null
    ): array {
        $userAgent = (string) $request->userAgent();
        $referer = (string) $request->header('referer');
        $isBot = $this->detectIsBot($userAgent);

        $normalizedPath = $this->normalizePath($path);
        $refererDomain = $this->extractRefererDomain($referer);

        return [
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent ?: null,
            'referer' => $referer ?: null,
            'referer_domain' => $refererDomain,
            'path' => $normalizedPath,
            'status_code' => $statusCode,
            'is_redirect' => $isRedirect,
            'redirect_id' => $redirectId,
            'country_code' => $this->detectCountryCode($request),
            'browser' => $this->detectBrowser($userAgent),
            'os' => $this->detectOs($userAgent),
            'device' => $this->detectDevice($userAgent, $isBot),
            'is_bot' => $isBot,
            'utm_source' => $request->query('utm_source') ? substr((string) $request->query('utm_source'), 0, 100) : null,
            'utm_medium' => $request->query('utm_medium') ? substr((string) $request->query('utm_medium'), 0, 100) : null,
            'utm_campaign' => $request->query('utm_campaign') ? substr((string) $request->query('utm_campaign'), 0, 100) : null,
            'utm_term' => $request->query('utm_term') ? substr((string) $request->query('utm_term'), 0, 100) : null,
            'utm_content' => $request->query('utm_content') ? substr((string) $request->query('utm_content'), 0, 100) : null,
            'is_quiet' => ! $isHtml && ! $isRedirect,
        ];
    }

    /**
     * Normalize URL path for analytics aggregation.
     */
    public function normalizePath(?string $path): string
    {
        if (empty($path)) {
            return '/';
        }

        $trimmed = trim($path, '/');

        if ($trimmed === '' || $trimmed === 'index.html' || $trimmed === 'index.htm') {
            return '/';
        }

        return '/'.$trimmed;
    }

    /**
     * Extract clean referer domain from full URL.
     */
    public function extractRefererDomain(?string $referer): ?string
    {
        if (empty($referer)) {
            return 'Direct';
        }

        $host = parse_url($referer, PHP_URL_HOST);
        if (empty($host)) {
            return 'Direct';
        }

        $host = strtolower($host);
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return $host;
    }

    /**
     * Detect country code from common proxy/CDN headers.
     */
    public function detectCountryCode(Request $request): ?string
    {
        $headerCandidates = [
            'CF-IPCountry',               // Cloudflare
            'CloudFront-Viewer-Country',   // AWS CloudFront
            'X-Country-Code',             // Various load balancers
            'X-Forwarded-Country',
            'X-Geo-Country',
        ];

        foreach ($headerCandidates as $header) {
            $code = $request->header($header);
            if ($code && is_string($code)) {
                $code = strtoupper(trim($code));
                if (preg_match('/^[A-Z]{2}$/', $code) && $code !== 'XX' && $code !== 'T1') {
                    return $code;
                }
            }
        }

        return null;
    }

    /**
     * Detect browser from User Agent string.
     */
    public function detectBrowser(string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Unknown';
        }

        if (preg_match('/Edg(?:e)?\/([0-9]+)/i', $userAgent)) {
            return 'Edge';
        }
        if (preg_match('/OPR\/([0-9]+)|Opera/i', $userAgent)) {
            return 'Opera';
        }
        if (preg_match('/Brave/i', $userAgent)) {
            return 'Brave';
        }
        if (preg_match('/Vivaldi/i', $userAgent)) {
            return 'Vivaldi';
        }
        if (preg_match('/DuckDuckGo/i', $userAgent)) {
            return 'DuckDuckGo';
        }
        if (preg_match('/Chrome\/([0-9]+)/i', $userAgent)) {
            return 'Chrome';
        }
        if (preg_match('/Firefox\/([0-9]+)/i', $userAgent)) {
            return 'Firefox';
        }
        if (preg_match('/Version\/([0-9]+).*Safari/i', $userAgent)) {
            return 'Safari';
        }
        if (preg_match('/MSIE|Trident/i', $userAgent)) {
            return 'Internet Explorer';
        }

        return 'Other';
    }

    /**
     * Detect operating system from User Agent string.
     */
    public function detectOs(string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Unknown';
        }

        if (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
            return 'iOS';
        }
        if (preg_match('/Android/i', $userAgent)) {
            return 'Android';
        }
        if (preg_match('/Macintosh|Mac OS X/i', $userAgent)) {
            return 'macOS';
        }
        if (preg_match('/Windows NT/i', $userAgent)) {
            return 'Windows';
        }
        if (preg_match('/CrOS/i', $userAgent)) {
            return 'ChromeOS';
        }
        if (preg_match('/Linux/i', $userAgent)) {
            return 'Linux';
        }

        return 'Other';
    }

    /**
     * Detect device type category.
     */
    public function detectDevice(string $userAgent, bool $isBot): string
    {
        if ($isBot) {
            return 'Bot';
        }

        if (empty($userAgent)) {
            return 'Desktop';
        }

        if (preg_match('/iPad|Tablet|PlayBook/i', $userAgent) || (preg_match('/Android/i', $userAgent) && ! preg_match('/Mobile/i', $userAgent))) {
            return 'Tablet';
        }

        if (preg_match('/Mobile|iPhone|Android|webOS|BlackBerry|IEMobile|Opera Mini/i', $userAgent)) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    /**
     * Detect if request is originating from a crawler, spider, or automated bot.
     */
    public function detectIsBot(string $userAgent): bool
    {
        if (empty($userAgent)) {
            return false;
        }

        $botRegex = '/bot|crawler|spider|crawling|googlebot|bingbot|slurp|duckduckbot|baiduspider|yandexbot|sogou|exabot|facebot|facebookexternalhit|ia_archiver|curl|wget|python-requests|headlesschrome|bytespider|semrushbot|ahrefsbot/i';

        return (bool) preg_match($botRegex, $userAgent);
    }

    /**
     * Convert 2-letter ISO country code into Unicode flag emoji.
     */
    public function getCountryFlag(?string $countryCode): string
    {
        if (empty($countryCode) || strlen($countryCode) !== 2) {
            return '🌐';
        }

        $countryCode = strtoupper($countryCode);
        $first = ord($countryCode[0]) - ord('A') + 0x1F1E6;
        $second = ord($countryCode[1]) - ord('A') + 0x1F1E6;

        return mb_chr($first, 'UTF-8').mb_chr($second, 'UTF-8');
    }

    /**
     * Get country human name from 2-letter code.
     */
    public function getCountryName(?string $countryCode): string
    {
        if (empty($countryCode)) {
            return 'Unknown';
        }

        $code = strtoupper($countryCode);

        return self::COUNTRY_NAMES[$code] ?? $code;
    }

    /**
     * Categorize referer domain into channel (Search, Social, Developer, Direct, Referral).
     */
    public function categorizeRefererDomain(?string $domain): array
    {
        if (empty($domain) || $domain === 'Direct') {
            return ['channel' => 'Direct', 'color' => 'zinc'];
        }

        $d = strtolower($domain);

        if (str_contains($d, 'google') || str_contains($d, 'bing') || str_contains($d, 'duckduckgo') || str_contains($d, 'ecosia') || str_contains($d, 'yahoo') || str_contains($d, 'baidu')) {
            return ['channel' => 'Search', 'color' => 'blue'];
        }

        if (str_contains($d, 'twitter') || str_contains($d, 't.co') || str_contains($d, 'x.com') || str_contains($d, 'linkedin') || str_contains($d, 'facebook') || str_contains($d, 'instagram') || str_contains($d, 'reddit') || str_contains($d, 'youtube') || str_contains($d, 'threads.net')) {
            return ['channel' => 'Social', 'color' => 'purple'];
        }

        if (str_contains($d, 'github') || str_contains($d, 'gitlab') || str_contains($d, 'stackoverflow') || str_contains($d, 'codepen') || str_contains($d, 'dev.to') || str_contains($d, 'news.ycombinator.com')) {
            return ['channel' => 'Developer', 'color' => 'amber'];
        }

        return ['channel' => 'Referral', 'color' => 'emerald'];
    }

    /**
     * Base query for site views filtered by quiet mode, bots, and period.
     */
    public function buildViewQuery(Site $site, int $days, bool $includeQuiet = false, bool $includeBots = false)
    {
        $query = $site->siteViews();

        if (! $includeQuiet) {
            $query->where('is_quiet', false);
        }

        if (! $includeBots) {
            $query->where('is_bot', false);
        }

        if ($days > 0) {
            $query->where('created_at', '>=', now()->subDays($days));
        }

        return $query;
    }

    /**
     * Get real-time active unique visitor count within the past N minutes.
     */
    public function getActiveVisitorsCount(Site $site, int $minutes = 15): int
    {
        return (int) $site->siteViews()
            ->where('is_quiet', false)
            ->where('is_bot', false)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count(DB::raw('DISTINCT ip_address'));
    }

    /**
     * Get high-level summary overview metrics with previous period comparisons.
     */
    public function getOverview(Site $site, int $days = 7, bool $includeBots = false): array
    {
        $currentStart = now()->subDays($days);
        $previousStart = now()->subDays($days * 2);

        // Current period metrics
        $currentQuery = $site->siteViews()
            ->where('is_quiet', false)
            ->where('created_at', '>=', $currentStart);

        if (! $includeBots) {
            $currentQuery->where('is_bot', false);
        }

        $totalViews = (clone $currentQuery)->count();
        $uniqueVisitors = (clone $currentQuery)->count(DB::raw('DISTINCT ip_address'));

        // Asset requests (quiet views)
        $assetRequests = $site->siteViews()
            ->where('is_quiet', true)
            ->where('created_at', '>=', $currentStart)
            ->count();

        // Redirect hits
        $redirectHits = $site->siteViews()
            ->where('is_redirect', true)
            ->where('created_at', '>=', $currentStart)
            ->count();

        // 404 broken link requests
        $notFoundHits = $site->siteViews()
            ->where('status_code', 404)
            ->where('created_at', '>=', $currentStart)
            ->count();

        // Bot views count
        $botViews = $site->siteViews()
            ->where('is_bot', true)
            ->where('created_at', '>=', $currentStart)
            ->count();

        // Previous period metrics for trend calculation
        $previousQuery = $site->siteViews()
            ->where('is_quiet', false)
            ->whereBetween('created_at', [$previousStart, $currentStart]);

        if (! $includeBots) {
            $previousQuery->where('is_bot', false);
        }

        $prevTotalViews = (clone $previousQuery)->count();
        $prevUniqueVisitors = (clone $previousQuery)->count(DB::raw('DISTINCT ip_address'));

        $viewsGrowth = $prevTotalViews > 0
            ? round((($totalViews - $prevTotalViews) / $prevTotalViews) * 100, 1)
            : ($totalViews > 0 ? 100 : 0);

        $visitorsGrowth = $prevUniqueVisitors > 0
            ? round((($uniqueVisitors - $prevUniqueVisitors) / $prevUniqueVisitors) * 100, 1)
            : ($uniqueVisitors > 0 ? 100 : 0);

        $viewsPerVisitor = $uniqueVisitors > 0
            ? round($totalViews / $uniqueVisitors, 1)
            : ($totalViews > 0 ? 1.0 : 0.0);

        $activeNow = $this->getActiveVisitorsCount($site, 15);

        return [
            'total_views' => $totalViews,
            'unique_visitors' => $uniqueVisitors,
            'active_now' => $activeNow,
            'redirect_hits' => $redirectHits,
            'not_found_hits' => $notFoundHits,
            'bot_views' => $botViews,
            'asset_requests' => $assetRequests,
            'views_growth' => $viewsGrowth,
            'visitors_growth' => $visitorsGrowth,
            'views_per_visitor' => $viewsPerVisitor,
            'avg_daily_views' => round($totalViews / max($days, 1), 1),
        ];
    }

    /**
     * Get dual time series data (Page Views and Unique Visitors).
     * If $days == 1, groups by hour. Otherwise, groups by day.
     */
    public function getTimeSeries(Site $site, int $days = 7, bool $includeBots = false): array
    {
        $isHourly = ($days === 1);

        if ($isHourly) {
            return $this->getHourlyTimeSeries($site, $includeBots);
        }

        return $this->getDailyTimeSeries($site, $days, $includeBots);
    }

    /**
     * Daily time series over given days.
     */
    protected function getDailyTimeSeries(Site $site, int $days, bool $includeBots): array
    {
        $query = $site->siteViews()
            ->where('is_quiet', false)
            ->where('created_at', '>=', now()->subDays($days));

        if (! $includeBots) {
            $query->where('is_bot', false);
        }

        $viewsByDate = (clone $query)
            ->select(DB::raw('DATE(created_at) as period'), DB::raw('count(*) as count'))
            ->groupBy('period')
            ->pluck('count', 'period')
            ->toArray();

        $uniquesByDate = (clone $query)
            ->select(DB::raw('DATE(created_at) as period'), DB::raw('count(DISTINCT ip_address) as count'))
            ->groupBy('period')
            ->pluck('count', 'period')
            ->toArray();

        $labels = [];
        $viewsData = [];
        $uniquesData = [];

        for ($i = $days; $i >= 0; $i--) {
            $dt = now()->subDays($i);
            $key = $dt->format('Y-m-d');
            $labels[] = $dt->format('M d');
            $viewsData[] = (int) ($viewsByDate[$key] ?? 0);
            $uniquesData[] = (int) ($uniquesByDate[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'views' => $viewsData,
            'uniques' => $uniquesData,
        ];
    }

    /**
     * Hourly time series over the past 24 hours.
     */
    protected function getHourlyTimeSeries(Site $site, bool $includeBots): array
    {
        $query = $site->siteViews()
            ->where('is_quiet', false)
            ->where('created_at', '>=', now()->subHours(24));

        if (! $includeBots) {
            $query->where('is_bot', false);
        }

        $driver = DB::connection()->getDriverName();
        $hourExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m-%d %H:00', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m-%d %H:00')";

        $viewsByHour = (clone $query)
            ->select(DB::raw("{$hourExpr} as period"), DB::raw('count(*) as count'))
            ->groupBy('period')
            ->pluck('count', 'period')
            ->toArray();

        $uniquesByHour = (clone $query)
            ->select(DB::raw("{$hourExpr} as period"), DB::raw('count(DISTINCT ip_address) as count'))
            ->groupBy('period')
            ->pluck('count', 'period')
            ->toArray();

        $labels = [];
        $viewsData = [];
        $uniquesData = [];

        for ($i = 24; $i >= 0; $i--) {
            $dt = now()->subHours($i);
            $key = $dt->format('Y-m-d H:00');
            $labels[] = $dt->format('H:i');
            $viewsData[] = (int) ($viewsByHour[$key] ?? 0);
            $uniquesData[] = (int) ($uniquesByHour[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'views' => $viewsData,
            'uniques' => $uniquesData,
        ];
    }

    /**
     * Top pages visited for site.
     */
    public function getTopPages(Site $site, int $days = 7, int $limit = 10, bool $includeBots = false): Collection
    {
        $query = $this->buildViewQuery($site, $days, false, $includeBots);

        $totalViews = (clone $query)->count();

        $records = $query
            ->select(
                DB::raw("COALESCE(path, '/') as path"),
                DB::raw('count(*) as views_count'),
                DB::raw('count(DISTINCT ip_address) as unique_visitors')
            )
            ->groupBy('path')
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get();

        return $records->map(function ($row) use ($totalViews) {
            $percentage = $totalViews > 0 ? round(($row->views_count / $totalViews) * 100, 1) : 0;

            return [
                'path' => $row->path,
                'views' => (int) $row->views_count,
                'unique_visitors' => (int) $row->unique_visitors,
                'percentage' => $percentage,
            ];
        });
    }

    /**
     * Top referrers visited for site.
     */
    public function getTopReferrers(Site $site, int $days = 7, int $limit = 10, bool $includeBots = false): Collection
    {
        $query = $this->buildViewQuery($site, $days, false, $includeBots);

        $totalViews = (clone $query)->count();

        $records = $query
            ->select(
                DB::raw("COALESCE(referer_domain, 'Direct') as domain"),
                DB::raw('count(*) as views_count'),
                DB::raw('count(DISTINCT ip_address) as unique_visitors')
            )
            ->groupBy('domain')
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get();

        return $records->map(function ($row) use ($totalViews) {
            $percentage = $totalViews > 0 ? round(($row->views_count / $totalViews) * 100, 1) : 0;
            $cat = $this->categorizeRefererDomain($row->domain);

            return [
                'domain' => $row->domain,
                'channel' => $cat['channel'],
                'channel_color' => $cat['color'],
                'views' => (int) $row->views_count,
                'unique_visitors' => (int) $row->unique_visitors,
                'percentage' => $percentage,
            ];
        });
    }

    /**
     * Country breakdown.
     */
    public function getTopCountries(Site $site, int $days = 7, int $limit = 10, bool $includeBots = false): Collection
    {
        $query = $this->buildViewQuery($site, $days, false, $includeBots);

        $totalViews = (clone $query)->count();

        $records = $query
            ->select(
                DB::raw("COALESCE(country_code, 'Unknown') as code"),
                DB::raw('count(*) as views_count'),
                DB::raw('count(DISTINCT ip_address) as unique_visitors')
            )
            ->groupBy('code')
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get();

        return $records->map(function ($row) use ($totalViews) {
            $code = $row->code === 'Unknown' ? null : $row->code;
            $percentage = $totalViews > 0 ? round(($row->views_count / $totalViews) * 100, 1) : 0;

            return [
                'code' => $code ?: '??',
                'name' => $this->getCountryName($code),
                'flag' => $this->getCountryFlag($code),
                'views' => (int) $row->views_count,
                'unique_visitors' => (int) $row->unique_visitors,
                'percentage' => $percentage,
            ];
        });
    }

    /**
     * Device breakdown.
     */
    public function getDeviceDistribution(Site $site, int $days = 7, bool $includeBots = false): array
    {
        $query = $this->buildViewQuery($site, $days, false, $includeBots);

        $records = $query
            ->select(
                DB::raw("COALESCE(device, 'Desktop') as device_type"),
                DB::raw('count(*) as count')
            )
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();

        $labels = ['Desktop', 'Mobile', 'Tablet'];
        if ($includeBots) {
            $labels[] = 'Bot';
        }

        $data = [];
        foreach ($labels as $label) {
            $data[] = (int) ($records[$label] ?? 0);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Browser breakdown.
     */
    public function getBrowserDistribution(Site $site, int $days = 7, int $limit = 5, bool $includeBots = false): Collection
    {
        $query = $this->buildViewQuery($site, $days, false, $includeBots);

        $total = (clone $query)->count();

        $records = $query
            ->select(
                DB::raw("COALESCE(browser, 'Unknown') as name"),
                DB::raw('count(*) as count')
            )
            ->groupBy('name')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();

        return $records->map(function ($row) use ($total) {
            return [
                'name' => $row->name,
                'count' => (int) $row->count,
                'percentage' => $total > 0 ? round(($row->count / $total) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Operating system breakdown.
     */
    public function getOsDistribution(Site $site, int $days = 7, int $limit = 5, bool $includeBots = false): Collection
    {
        $query = $this->buildViewQuery($site, $days, false, $includeBots);

        $total = (clone $query)->count();

        $records = $query
            ->select(
                DB::raw("COALESCE(os, 'Unknown') as name"),
                DB::raw('count(*) as count')
            )
            ->groupBy('name')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();

        return $records->map(function ($row) use ($total) {
            return [
                'name' => $row->name,
                'count' => (int) $row->count,
                'percentage' => $total > 0 ? round(($row->count / $total) * 100, 1) : 0,
            ];
        });
    }

    /**
     * UTM Campaigns breakdown.
     */
    public function getCampaignDistribution(Site $site, int $days = 7, int $limit = 10): Collection
    {
        $query = $this->buildViewQuery($site, $days, false, false)
            ->whereNotNull('utm_campaign');

        $total = (clone $query)->count();

        $records = $query
            ->select(
                'utm_campaign',
                'utm_source',
                'utm_medium',
                DB::raw('count(*) as count'),
                DB::raw('count(DISTINCT ip_address) as unique_visitors')
            )
            ->groupBy('utm_campaign', 'utm_source', 'utm_medium')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();

        return $records->map(function ($row) use ($total) {
            return [
                'campaign' => $row->utm_campaign,
                'source' => $row->utm_source ?: 'direct',
                'medium' => $row->utm_medium ?: 'none',
                'views' => (int) $row->count,
                'unique_visitors' => (int) $row->unique_visitors,
                'percentage' => $total > 0 ? round(($row->count / $total) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Telemetry performance breakdown for configured redirect rules.
     */
    public function getRedirectPerformance(Site $site, int $days = 7): Collection
    {
        $redirects = $site->redirects()->get();

        return $redirects->map(function ($redirect) use ($site, $days) {
            $viewQuery = $site->siteViews()
                ->where('redirect_id', $redirect->id)
                ->when($days > 0, fn ($q) => $q->where('created_at', '>=', now()->subDays($days)));

            $periodHits = (clone $viewQuery)->count();
            $uniques = (clone $viewQuery)->count(DB::raw('DISTINCT ip_address'));

            return [
                'id' => $redirect->id,
                'from_path' => $redirect->from_path,
                'to_url' => $redirect->to_url,
                'status_code' => (int) $redirect->status_code,
                'is_active' => (bool) $redirect->is_active,
                'total_hits' => (int) $redirect->hit_count,
                'period_hits' => $periodHits,
                'uniques' => $uniques,
                'last_accessed_at' => $redirect->last_accessed_at,
                'description' => $redirect->description,
            ];
        })->sortByDesc('period_hits')->values();
    }

    /**
     * Top 404 broken link paths visited by users or bots.
     */
    public function getBrokenLinks(Site $site, int $days = 7, int $limit = 10): Collection
    {
        $query = $site->siteViews()
            ->where('status_code', 404)
            ->when($days > 0, fn ($q) => $q->where('created_at', '>=', now()->subDays($days)));

        $records = $query
            ->select(
                DB::raw("COALESCE(path, '/') as path"),
                DB::raw('count(*) as count'),
                DB::raw('max(created_at) as last_seen')
            )
            ->groupBy('path')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();

        return $records->map(function ($row) {
            return [
                'path' => $row->path,
                'count' => (int) $row->count,
                'last_seen' => $row->last_seen ? Carbon::parse($row->last_seen) : null,
            ];
        });
    }

    /**
     * 24-hour distribution of visits to pinpoint peak visitor activity hours.
     */
    public function getHourlyDistribution(Site $site, int $days = 7): array
    {
        $query = $this->buildViewQuery($site, $days, false, false);
        $driver = DB::connection()->getDriverName();
        $hourExpr = $driver === 'sqlite'
            ? "CAST(strftime('%H', created_at) AS INTEGER)"
            : 'HOUR(created_at)';

        $counts = $query
            ->select(DB::raw("{$hourExpr} as hour"), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        $labels = [];
        $data = [];
        for ($h = 0; $h < 24; $h++) {
            $labels[] = sprintf('%02d:00', $h);
            $data[] = (int) ($counts[$h] ?? 0);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Stream CSV export of analytics views for the site.
     */
    public function exportCsv(Site $site, int $days = 30): StreamedResponse
    {
        $filename = 'analytics-'.$site->slug.'-'.now()->format('Y-m-d').'.csv';

        $query = $site->siteViews()
            ->when($days > 0, fn ($q) => $q->where('created_at', '>=', now()->subDays($days)))
            ->latest('created_at');

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // BOM for UTF-8 Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV Header Row
            fputcsv($handle, [
                'Timestamp (UTC)',
                'Type',
                'Status Code',
                'Is Redirect',
                'Path',
                'IP Address',
                'Country',
                'Referer',
                'Referer Domain',
                'Browser',
                'Operating System',
                'Device',
                'Is Bot',
                'UTM Source',
                'UTM Medium',
                'UTM Campaign',
            ]);

            $query->chunk(500, function ($views) use ($handle) {
                foreach ($views as $v) {
                    $type = 'Page View';
                    if ($v->is_redirect) {
                        $type = 'Redirect ('.$v->status_code.')';
                    } elseif ($v->status_code === 404) {
                        $type = '404 Not Found';
                    } elseif ($v->is_quiet) {
                        $type = 'Asset';
                    }

                    fputcsv($handle, [
                        $v->created_at?->toIso8601String(),
                        $type,
                        $v->status_code ?? 200,
                        $v->is_redirect ? 'Yes' : 'No',
                        $v->path ?: '/',
                        $v->ip_address,
                        $v->country_code,
                        $v->referer,
                        $v->referer_domain,
                        $v->browser,
                        $v->os,
                        $v->device,
                        $v->is_bot ? 'Yes' : 'No',
                        $v->utm_source,
                        $v->utm_medium,
                        $v->utm_campaign,
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
