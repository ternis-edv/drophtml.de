<?php

use App\Models\Site;
use App\Models\User;
use App\Models\View;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('analytics service correctly parses user agent, referer and geo headers', function () {
    $service = app(AnalyticsService::class);

    $request = Request::create('/s/my-site/pricing.html?utm_source=twitter&utm_medium=social&utm_campaign=launch', 'GET', [
        'utm_source' => 'twitter',
        'utm_medium' => 'social',
        'utm_campaign' => 'launch',
    ], [], [], [
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        'HTTP_REFERER' => 'https://twitter.com/drophtml/status/12345',
        'HTTP_CF_IPCOUNTRY' => 'DE',
        'REMOTE_ADDR' => '198.51.100.42',
    ]);

    $metadata = $service->extractMetadata($request, 'pricing.html', true);

    expect($metadata['path'])->toBe('/pricing.html');
    expect($metadata['country_code'])->toBe('DE');
    expect($metadata['browser'])->toBe('Chrome');
    expect($metadata['os'])->toBe('macOS');
    expect($metadata['device'])->toBe('Desktop');
    expect($metadata['is_bot'])->toBeFalse();
    expect($metadata['referer_domain'])->toBe('twitter.com');
    expect($metadata['utm_source'])->toBe('twitter');
    expect($metadata['utm_medium'])->toBe('social');
    expect($metadata['utm_campaign'])->toBe('launch');
    expect($metadata['is_quiet'])->toBeFalse();
});

test('analytics service detects mobile devices and bots', function () {
    $service = app(AnalyticsService::class);

    // Mobile user agent
    $mobileReq = Request::create('/s/my-site', 'GET', [], [], [], [
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
    ]);
    $mobileMeta = $service->extractMetadata($mobileReq, null, true);
    expect($mobileMeta['device'])->toBe('Mobile');
    expect($mobileMeta['os'])->toBe('iOS');
    expect($mobileMeta['browser'])->toBe('Safari');
    expect($mobileMeta['is_bot'])->toBeFalse();

    // Bot user agent
    $botReq = Request::create('/s/my-site', 'GET', [], [], [], [
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
    ]);
    $botMeta = $service->extractMetadata($botReq, null, true);
    expect($botMeta['device'])->toBe('Bot');
    expect($botMeta['is_bot'])->toBeTrue();
});

test('site serving records rich visitor analytics on view', function () {
    Storage::fake('public');

    $slug = 'analytics-test-site';
    $path = "sites/{$slug}";
    Storage::disk('public')->put("{$path}/index.html", '<h1>Analytics Page</h1>');
    Storage::disk('public')->put("{$path}/assets/style.css", 'body { background: #fff; }');

    $site = Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'site.zip',
        'status' => 'active',
    ]);

    // 1. Visit HTML page
    $response = $this->withHeaders([
        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15',
        'Referer' => 'https://news.ycombinator.com/item?id=123',
        'CF-IPCountry' => 'US',
    ])->get("/s/{$slug}?utm_source=hackernews&utm_campaign=frontpage");

    $response->assertOk();

    $site->refresh();
    expect($site->views)->toBe(1);

    $view = $site->siteViews()->first();
    expect($view)->not->toBeNull();
    expect($view->path)->toBe('/');
    expect($view->country_code)->toBe('US');
    expect($view->browser)->toBe('Safari');
    expect($view->os)->toBe('macOS');
    expect($view->device)->toBe('Desktop');
    expect($view->referer_domain)->toBe('news.ycombinator.com');
    expect($view->utm_source)->toBe('hackernews');
    expect($view->utm_campaign)->toBe('frontpage');
    expect($view->is_quiet)->toBeFalse();

    // 2. Visit static sub-asset
    $cssResponse = $this->get("/s/{$slug}/assets/style.css");
    $cssResponse->assertOk();

    $site->refresh();
    expect($site->views)->toBe(1); // not incremented for asset
    expect($site->siteViews()->count())->toBe(2);

    $assetView = $site->siteViews()->latest('id')->first();
    expect($assetView->is_quiet)->toBeTrue();
    expect($assetView->path)->toBe('/assets/style.css');
});

test('analytics service generates accurate aggregations', function () {
    $service = app(AnalyticsService::class);
    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'metrics-site',
        'path' => 'sites/metrics-site',
        'original_name' => 'metrics-site',
        'status' => 'active',
    ]);

    // Create seed views
    View::create([
        'site_id' => $site->id,
        'ip_address' => '1.1.1.1',
        'path' => '/',
        'referer_domain' => 'google.com',
        'country_code' => 'US',
        'browser' => 'Chrome',
        'os' => 'macOS',
        'device' => 'Desktop',
        'is_bot' => false,
        'is_quiet' => false,
        'created_at' => now(),
    ]);

    View::create([
        'site_id' => $site->id,
        'ip_address' => '1.1.1.1',
        'path' => '/about.html',
        'referer_domain' => 'google.com',
        'country_code' => 'US',
        'browser' => 'Chrome',
        'os' => 'macOS',
        'device' => 'Desktop',
        'is_bot' => false,
        'is_quiet' => false,
        'created_at' => now(),
    ]);

    View::create([
        'site_id' => $site->id,
        'ip_address' => '2.2.2.2',
        'path' => '/',
        'referer_domain' => 'twitter.com',
        'country_code' => 'DE',
        'browser' => 'Firefox',
        'os' => 'Windows',
        'device' => 'Desktop',
        'is_bot' => false,
        'is_quiet' => false,
        'utm_campaign' => 'spring_promo',
        'utm_source' => 'twitter',
        'created_at' => now(),
    ]);

    // Asset view
    View::create([
        'site_id' => $site->id,
        'ip_address' => '2.2.2.2',
        'path' => '/style.css',
        'is_quiet' => true,
        'is_bot' => false,
        'created_at' => now(),
    ]);

    // Bot view
    View::create([
        'site_id' => $site->id,
        'ip_address' => '3.3.3.3',
        'path' => '/',
        'is_quiet' => false,
        'is_bot' => true,
        'device' => 'Bot',
        'created_at' => now(),
    ]);

    $overview = $service->getOverview($site, 7, false);
    expect($overview['total_views'])->toBe(3);
    expect($overview['unique_visitors'])->toBe(2);
    expect($overview['asset_requests'])->toBe(1);
    expect($overview['views_per_visitor'])->toBe(1.5);

    $topPages = $service->getTopPages($site, 7, 10, false);
    expect($topPages)->toHaveCount(2);
    expect($topPages->first()['path'])->toBe('/');
    expect($topPages->first()['views'])->toBe(2);

    $topReferrers = $service->getTopReferrers($site, 7, 10, false);
    expect($topReferrers)->toHaveCount(2);
    $channels = $topReferrers->pluck('channel')->all();
    expect($channels)->toContain('Search');
    expect($channels)->toContain('Social');

    $countries = $service->getTopCountries($site, 7, 10, false);
    expect($countries)->toHaveCount(2);
    expect($countries->pluck('code')->all())->toContain('US', 'DE');

    $campaigns = $service->getCampaignDistribution($site, 7);
    expect($campaigns)->toHaveCount(1);
    expect($campaigns->first()['campaign'])->toBe('spring_promo');

    $timeSeries = $service->getTimeSeries($site, 7, false);
    expect($timeSeries)->toHaveKeys(['labels', 'views', 'uniques']);
    expect(array_sum($timeSeries['views']))->toBe(3);
    expect(array_sum($timeSeries['uniques']))->toBe(2);
});

test('site stats livewire component mounts and updates period', function () {
    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'stats-component-site',
        'path' => 'sites/stats-component-site',
        'original_name' => 'stats-component-site',
        'status' => 'active',
    ]);

    $this->actingAs($user);

    Livewire::test('dashboard.site-stats', ['siteId' => $site->id])
        ->assertSet('period', 7)
        ->assertSet('includeBots', false)
        ->assertSee('Site Analytics')
        ->call('setPeriod', 30)
        ->assertSet('period', 30)
        ->call('toggleBots')
        ->assertSet('includeBots', true);
});

test('site stats csv export works for site owner and forbids non-owners', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $site = Site::create([
        'user_id' => $owner->id,
        'slug' => 'csv-site',
        'path' => 'sites/csv-site',
        'original_name' => 'csv-site',
        'status' => 'active',
    ]);

    View::create([
        'site_id' => $site->id,
        'ip_address' => '198.51.100.1',
        'path' => '/features',
        'country_code' => 'FR',
        'browser' => 'Safari',
        'os' => 'macOS',
        'device' => 'Desktop',
        'is_quiet' => false,
        'is_bot' => false,
        'created_at' => now(),
    ]);

    // Unauthorized non-owner receives 403
    $this->actingAs($otherUser);
    $forbiddenResponse = $this->get(route('dashboard.sites.stats.export', ['site' => $site->id, 'days' => 7]));
    $forbiddenResponse->assertForbidden();

    // Owner receives CSV download
    $this->actingAs($owner);
    $response = $this->get(route('dashboard.sites.stats.export', ['site' => $site->id, 'days' => 7]));
    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

test('analytics service tracks real-time active visitors, broken links, and hourly distribution', function () {
    $service = app(AnalyticsService::class);
    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'realtime-test-site',
        'path' => 'sites/realtime-test-site',
        'status' => 'active',
    ]);

    // 1. Create active visitor view (within last 5 minutes)
    View::create([
        'site_id' => $site->id,
        'ip_address' => '203.0.113.10',
        'path' => '/',
        'status_code' => 200,
        'is_quiet' => false,
        'is_bot' => false,
        'created_at' => now()->subMinutes(2),
    ]);

    // 2. Create another active visitor view (within last 10 minutes)
    View::create([
        'site_id' => $site->id,
        'ip_address' => '203.0.113.20',
        'path' => '/about',
        'status_code' => 200,
        'is_quiet' => false,
        'is_bot' => false,
        'created_at' => now()->subMinutes(8),
    ]);

    // 3. Create an older view (30 minutes ago, not in 15m window)
    View::create([
        'site_id' => $site->id,
        'ip_address' => '203.0.113.30',
        'path' => '/pricing',
        'status_code' => 200,
        'is_quiet' => false,
        'is_bot' => false,
        'created_at' => now()->subMinutes(30),
    ]);

    // 4. Create 404 broken link view
    View::create([
        'site_id' => $site->id,
        'ip_address' => '203.0.113.40',
        'path' => '/old-docs',
        'status_code' => 404,
        'is_quiet' => false,
        'is_bot' => false,
        'created_at' => now()->subMinutes(5),
    ]);

    // Check active visitors count (3 active visitors within 15 minutes: 203.0.113.10, 203.0.113.20, 203.0.113.40)
    $activeCount = $service->getActiveVisitorsCount($site, 15);
    expect($activeCount)->toBe(3);

    // Check overview
    $overview = $service->getOverview($site, 7, false);
    expect($overview['active_now'])->toBe(3);
    expect($overview['not_found_hits'])->toBe(1);

    // Check broken links
    $brokenLinks = $service->getBrokenLinks($site, 7);
    expect($brokenLinks)->toHaveCount(1);
    expect($brokenLinks->first()['path'])->toBe('/old-docs');
    expect($brokenLinks->first()['count'])->toBe(1);

    // Check hourly distribution
    $hourly = $service->getHourlyDistribution($site, 7);
    expect($hourly['labels'])->toHaveCount(24);
    expect(array_sum($hourly['data']))->toBeGreaterThan(0);
});

test('site stats component renders tabs and switches between them', function () {
    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'tabs-test-site',
        'path' => 'sites/tabs-test-site',
        'status' => 'active',
    ]);

    $this->actingAs($user);

    Livewire::test('dashboard.site-stats', ['siteId' => $site->id])
        ->assertSet('activeTab', 'traffic')
        ->assertSee('Traffic Timeline')
        ->call('setTab', 'pages')
        ->assertSet('activeTab', 'pages')
        ->assertSee('Top Visited Pages')
        ->assertSee('Broken Links')
        ->call('setTab', 'sources')
        ->assertSet('activeTab', 'sources')
        ->assertSee('Inbound Traffic Sources')
        ->call('setTab', 'redirects')
        ->assertSet('activeTab', 'redirects')
        ->assertSee('Redirect URL Performance')
        ->call('setTab', 'activity')
        ->assertSet('activeTab', 'activity')
        ->assertSee('Live Request Activity Stream');
});
