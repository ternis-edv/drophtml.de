<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\Site;
use App\Models\User;
use InvalidArgumentException;

class DomainService
{
    /**
     * Reserved hostnames that cannot be claimed as custom domains.
     */
    public const RESERVED_HOSTNAMES = [
        'localhost',
        '127.0.0.1',
        'drophtml.de',
        'sites.drophtml.de',
        'api.drophtml.de',
        'app.drophtml.de',
        'admin.drophtml.de',
        'mail.drophtml.de',
        'dashboard.drophtml.de',
        'staging.drophtml.de',
        'demo.drophtml.de',
        'test.drophtml.de',
        'dev.drophtml.de',
        'static.drophtml.de',
        'cdn.drophtml.de',
        'auth.drophtml.de',
    ];

    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Normalize a domain string.
     */
    public function normalizeDomain(string $domain): string
    {
        $cleaned = trim($domain);
        $cleaned = preg_replace('#^https?://#i', '', $cleaned);
        $cleaned = rtrim($cleaned, '/');

        return strtolower($cleaned);
    }

    /**
     * Determine whether a domain name is reserved or disallowed.
     */
    public function isReserved(string $domain): bool
    {
        $normalized = $this->normalizeDomain($domain);

        if (in_array($normalized, self::RESERVED_HOSTNAMES, true)) {
            return true;
        }

        // Match against current app host
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        if ($appHost && ($normalized === strtolower($appHost) || str_ends_with($normalized, '.'.strtolower($appHost)))) {
            return true;
        }

        // Disallow wildcard or top-level drophtml.de
        if (str_ends_with($normalized, '.drophtml.de')) {
            return true;
        }

        return false;
    }

    /**
     * Create and attach a domain to a site.
     */
    public function createDomain(Site $site, string $domain, bool $isCustom = true, ?User $actor = null): Domain
    {
        $normalized = $this->normalizeDomain($domain);

        if ($this->isReserved($normalized)) {
            throw new InvalidArgumentException("The domain '{$normalized}' is reserved and cannot be added.");
        }

        if (Domain::where('domain', $normalized)->exists()) {
            throw new InvalidArgumentException("The domain '{$normalized}' is already registered to a site.");
        }

        $domainRecord = $site->domains()->create([
            'domain' => $normalized,
            'is_custom' => $isCustom,
        ]);

        // Attempt immediate DNS verification
        $this->verifyDns($domainRecord);

        if ($actor || $site->user) {
            $this->activityLogService->log(
                $actor ?? $site->user,
                $site,
                'domain_added',
                "Added custom domain: {$normalized}"
            );
        }

        return $domainRecord;
    }

    /**
     * Remove a domain from a site.
     */
    public function deleteDomain(Domain $domain, ?User $actor = null): void
    {
        $domainName = $domain->domain;
        $site = $domain->site;

        $domain->delete();

        if ($actor || $site?->user) {
            $this->activityLogService->log(
                $actor ?? $site?->user,
                $site,
                'domain_removed',
                "Removed custom domain: {$domainName}"
            );
        }
    }

    /**
     * Verify DNS records for a domain (CNAME or A record).
     */
    public function verifyDns(Domain $domain): bool
    {
        $host = $domain->domain;
        $isVerified = false;

        // 1. Check CNAME records
        $cnameRecords = @dns_get_record($host, DNS_CNAME);
        if (is_array($cnameRecords)) {
            foreach ($cnameRecords as $record) {
                if (isset($record['target'])) {
                    $target = strtolower($record['target']);
                    if (str_contains($target, 'drophtml.de') || str_contains($target, 'sites.drophtml.de')) {
                        $isVerified = true;
                        break;
                    }
                }
            }
        }

        // 2. Check if domain resolves to the same IP as the app host
        if (! $isVerified) {
            $appHost = parse_url(config('app.url'), PHP_URL_HOST);
            if ($appHost && $appHost !== 'localhost') {
                $appIp = @gethostbyname($appHost);
                $domainIp = @gethostbyname($host);
                if ($appIp && $domainIp && $appIp === $domainIp && $domainIp !== $host) {
                    $isVerified = true;
                }
            }
        }

        if ($isVerified) {
            $domain->update(['verified_at' => now()]);

            return true;
        }

        return false;
    }
}
