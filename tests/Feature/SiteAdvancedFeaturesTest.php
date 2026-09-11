<?php

use App\Jobs\DispatchSiteWebhookJob;
use App\Models\Site;
use App\Models\User;
use App\Services\SiteDeploymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('extensionless clean URLs serve corresponding html file', function () {
    Storage::fake('public');

    $slug = 'clean-urls-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/index.html", '<h1>Index</h1>');
    Storage::disk('public')->put("{$path}/pricing.html", '<h1>Pricing Page</h1>');
    Storage::disk('public')->put("{$path}/docs/guide.html", '<h1>Guide Page</h1>');

    $site = Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'site.zip',
        'status' => 'active',
    ]);

    // Request clean /pricing without .html extension
    $response = $this->get("/s/{$slug}/pricing");
    $response->assertOk();
    $response->assertSee('<h1>Pricing Page</h1>', false);
    $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');

    // Request clean nested /docs/guide without .html extension
    $nestedResponse = $this->get("/s/{$slug}/docs/guide");
    $nestedResponse->assertOk();
    $nestedResponse->assertSee('<h1>Guide Page</h1>', false);
});

test('custom 404 page is served when present in site root', function () {
    Storage::fake('public');

    $slug = 'custom-404-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/index.html", '<h1>Home</h1>');
    Storage::disk('public')->put("{$path}/404.html", '<h1>Custom Not Found Page</h1>');

    Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'site.zip',
        'status' => 'active',
    ]);

    $response = $this->get("/s/{$slug}/random-missing-page");
    $response->assertStatus(404);
    $response->assertSee('<h1>Custom Not Found Page</h1>', false);
});

test('ETag header is returned and 304 response is sent for unchanged assets', function () {
    Storage::fake('public');

    $slug = 'etag-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/assets/style.css", 'body { margin: 0; }');

    Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'site.zip',
        'status' => 'active',
    ]);

    $response = $this->get("/s/{$slug}/assets/style.css");
    $response->assertOk();
    $etag = $response->headers->get('ETag');
    expect($etag)->not->toBeEmpty();

    // Re-request with If-None-Match header matching ETag
    $cachedResponse = $this->withHeaders(['If-None-Match' => $etag])->get("/s/{$slug}/assets/style.css");
    $cachedResponse->assertStatus(304);
});

test('site owner can download full site as zip archive and non-owners are forbidden', function () {
    Storage::fake('public');

    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $slug = 'zip-download-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/index.html", '<h1>Download Me</h1>');
    Storage::disk('public')->put("{$path}/css/app.css", 'h1 { color: blue; }');

    $site = Site::create([
        'user_id' => $owner->id,
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'My Site',
        'status' => 'active',
    ]);

    // Unauthorized user receives 403
    $this->actingAs($otherUser);
    $forbidden = $this->get(route('dashboard.sites.download', $site->id));
    $forbidden->assertForbidden();

    // Owner receives binary ZIP download
    $this->actingAs($owner);
    $response = $this->get(route('dashboard.sites.download', $site->id));
    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/zip');
    expect($response->headers->get('Content-Disposition'))->toContain('.zip');
});

test('site deployment service can duplicate site', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $service = app(SiteDeploymentService::class);

    $originalSlug = 'original-site';
    $path = "sites/{$originalSlug}";

    Storage::disk('public')->put("{$path}/index.html", '<h1>Original Site</h1>');
    Storage::disk('public')->put("{$path}/about.html", '<h1>About Us</h1>');

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => $originalSlug,
        'path' => $path,
        'original_name' => 'Original Site',
        'status' => 'active',
        'views' => 150,
    ]);

    $clonedSite = $service->duplicateSite($site, $user);

    expect($clonedSite)->not->toBeNull();
    expect($clonedSite->id)->not->toBe($site->id);
    expect($clonedSite->slug)->not->toBe($originalSlug);
    expect($clonedSite->views)->toBe(0);
    expect($clonedSite->original_name)->toContain('Copy');

    expect(Storage::disk('public')->exists("{$clonedSite->path}/index.html"))->toBeTrue();
    expect(Storage::disk('public')->get("{$clonedSite->path}/index.html"))->toBe('<h1>Original Site</h1>');
    expect(Storage::disk('public')->exists("{$clonedSite->path}/about.html"))->toBeTrue();

    // Verify activity log recorded the duplication
    expect($user->activityLogs()->where('action', 'site_duplicated')->exists())->toBeTrue();
});

test('dashboard overview component duplicate action clones site', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    $slug = 'livewire-dup-site';
    $path = "sites/{$slug}";
    Storage::disk('public')->put("{$path}/index.html", '<h1>Livewire Dup</h1>');

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'Livewire Dup Site',
        'status' => 'active',
    ]);

    Livewire::test('dashboard.sites-overview')
        ->call('duplicateSite', $site->id);

    expect(Site::where('user_id', $user->id)->count())->toBe(2);
});

test('dashboard site settings component duplicate action clones site', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    $slug = 'settings-dup-site';
    $path = "sites/{$slug}";
    Storage::disk('public')->put("{$path}/index.html", '<h1>Settings Dup</h1>');

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'Settings Dup Site',
        'status' => 'active',
    ]);

    Livewire::test('dashboard.site-settings', ['siteId' => $site->id])
        ->call('duplicateSite');

    expect(Site::where('user_id', $user->id)->count())->toBe(2);
});

test('dashboard site settings can configure SPA mode and webhooks', function () {
    Storage::fake('public');
    Queue::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    $slug = 'settings-features-site';
    $path = "sites/{$slug}";
    Storage::disk('public')->put("{$path}/index.html", '<h1>Settings Test</h1>');

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'Settings Features Site',
        'status' => 'active',
        'is_spa' => false,
    ]);

    Livewire::test('dashboard.site-settings', ['siteId' => $site->id])
        ->set('isSpa', true)
        ->set('webhookUrl', 'https://hooks.example.com/deploy')
        ->set('webhookSecret', 'secret-key-123')
        ->call('save')
        ->assertHasNoErrors();

    $site->refresh();
    expect($site->is_spa)->toBeTrue();
    expect($site->webhook_url)->toBe('https://hooks.example.com/deploy');
    expect($site->webhook_secret)->toBe('secret-key-123');

    // Test sending ping
    Livewire::test('dashboard.site-settings', ['siteId' => $site->id])
        ->call('sendWebhookTestPing')
        ->assertHasNoErrors();

    Queue::assertPushed(DispatchSiteWebhookJob::class, function ($job) use ($site) {
        return $job->site->id === $site->id && $job->event === 'test.ping';
    });
});
