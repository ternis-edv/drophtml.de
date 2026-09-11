<?php

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('site owner can manage redirect rules via api', function () {
    $user = User::factory()->create();
    $site = Site::create([
        'slug' => 'redirect-api-site',
        'path' => 'sites/redirect-api-site',
        'user_id' => $user->id,
        'status' => 'active',
    ]);

    // 1. Create redirect via API
    $response = $this->actingAs($user)->postJson("/api/sites/{$site->id}/redirects", [
        'from_path' => 'twitter',
        'to_url' => 'https://x.com/drophtml',
        'status_code' => 302,
        'description' => 'Twitter shortlink',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.from_path', '/twitter');
    $response->assertJsonPath('data.to_url', 'https://x.com/drophtml');
    $response->assertJsonPath('data.status_code', 302);

    $redirectId = $response->json('data.id');

    // 2. List redirects via API
    $listResponse = $this->actingAs($user)->getJson("/api/sites/{$site->id}/redirects");
    $listResponse->assertOk();
    $listResponse->assertJsonCount(1, 'data');

    // 3. Update redirect via API
    $updateResponse = $this->actingAs($user)->putJson("/api/sites/{$site->id}/redirects/{$redirectId}", [
        'to_url' => 'https://twitter.com/drophtml',
        'status_code' => 301,
    ]);
    $updateResponse->assertOk();
    $updateResponse->assertJsonPath('data.to_url', 'https://twitter.com/drophtml');
    $updateResponse->assertJsonPath('data.status_code', 301);

    // 4. Delete redirect via API
    $deleteResponse = $this->actingAs($user)->deleteJson("/api/sites/{$site->id}/redirects/{$redirectId}");
    $deleteResponse->assertOk();
    expect($site->redirects()->count())->toBe(0);
});

test('non-owner cannot access redirect rules via api', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $site = Site::create([
        'slug' => 'auth-test-site',
        'path' => 'sites/auth-test-site',
        'user_id' => $owner->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($other)->getJson("/api/sites/{$site->id}/redirects");
    $response->assertForbidden();

    $postResponse = $this->actingAs($other)->postJson("/api/sites/{$site->id}/redirects", [
        'from_path' => '/test',
        'to_url' => '/target',
    ]);
    $postResponse->assertForbidden();
});

test('site serving executes database redirect rules, tracks hits and records visitor analytics', function () {
    Storage::fake('public');

    $slug = 'serving-redirect-site';
    $path = "sites/{$slug}";
    Storage::disk('public')->put("{$path}/index.html", '<h1>Index</h1>');
    Storage::disk('public')->put("{$path}/destination.html", '<h1>Destination</h1>');

    $site = Site::create([
        'slug' => $slug,
        'path' => $path,
        'status' => 'active',
    ]);

    // Create DB redirect rule
    $redirect = $site->redirects()->create([
        'from_path' => '/discord',
        'to_url' => 'https://discord.gg/drophtml',
        'status_code' => 302,
        'is_active' => true,
    ]);

    // Request the redirect path
    $response = $this->withHeaders([
        'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Safari/604.1',
        'CF-IPCountry' => 'DE',
    ])->get("/s/{$slug}/discord?ref=newsletter");

    $response->assertStatus(302);
    $response->assertRedirect('https://discord.gg/drophtml?ref=newsletter');

    // Verify hit counter incremented
    $redirect->refresh();
    expect($redirect->hit_count)->toBe(1);
    expect($redirect->last_accessed_at)->not->toBeNull();

    // Verify visitor analytics recorded for the redirect
    $view = $site->siteViews()->where('is_redirect', true)->first();
    expect($view)->not->toBeNull();
    expect($view->path)->toBe('/discord');
    expect($view->status_code)->toBe(302);
    expect($view->redirect_id)->toBe($redirect->id);
    expect($view->country_code)->toBe('DE');
    expect($view->device)->toBe('Mobile');
});

test('site serving supports wildcard splat and 200 rewrites from database', function () {
    Storage::fake('public');

    $slug = 'wildcard-db-site';
    $path = "sites/{$slug}";
    Storage::disk('public')->put("{$path}/index.html", '<h1>Home</h1>');

    $site = Site::create([
        'slug' => $slug,
        'path' => $path,
        'status' => 'active',
    ]);

    // 1. Wildcard 301 redirect: /blog/* -> /news/:splat
    $site->redirects()->create([
        'from_path' => '/blog/*',
        'to_url' => '/news/:splat',
        'status_code' => 301,
        'is_active' => true,
    ]);

    // 2. 200 Internal rewrite: /app/* -> /index.html
    $site->redirects()->create([
        'from_path' => '/app/*',
        'to_url' => '/index.html',
        'status_code' => 200,
        'is_active' => true,
    ]);

    // Test wildcard redirect
    $redirectResponse = $this->get("/s/{$slug}/blog/2026/09/release");
    $redirectResponse->assertStatus(301);
    $redirectResponse->assertRedirect("http://localhost:8000/s/{$slug}/news/2026/09/release");

    // Test internal rewrite
    $rewriteResponse = $this->get("/s/{$slug}/app/settings/account");
    $rewriteResponse->assertOk();
    $rewriteResponse->assertSee('<h1>Home</h1>', false);
});

test('paused database redirect rules are ignored', function () {
    Storage::fake('public');

    $slug = 'paused-redirect-site';
    $path = "sites/{$slug}";
    Storage::disk('public')->put("{$path}/index.html", '<h1>Home</h1>');

    $site = Site::create([
        'slug' => $slug,
        'path' => $path,
        'status' => 'active',
    ]);

    $redirect = $site->redirects()->create([
        'from_path' => '/paused',
        'to_url' => 'https://google.com',
        'status_code' => 301,
        'is_active' => false,
    ]);

    // Should not redirect since is_active is false
    $response = $this->get("/s/{$slug}/paused");
    $response->assertNotFound();
    expect($redirect->fresh()->hit_count)->toBe(0);
});

test('livewire site-redirects component allows creating, editing, toggling, and deleting rules', function () {
    $user = User::factory()->create();
    $site = Site::create([
        'slug' => 'livewire-redirect-site',
        'path' => 'sites/livewire-redirect-site',
        'user_id' => $user->id,
        'status' => 'active',
    ]);

    Livewire::actingAs($user)
        ->test('dashboard.site-redirects', ['siteId' => $site->id])
        ->assertSee('Configured Rules')
        ->set('fromPath', '/promo')
        ->set('toUrl', 'https://example.com/promo')
        ->set('statusCode', 301)
        ->set('description', 'Summer Promo')
        ->call('createRedirect')
        ->assertHasNoErrors();

    expect($site->redirects()->count())->toBe(1);
    $rule = $site->redirects()->first();
    expect($rule->from_path)->toBe('/promo');

    // Toggle active
    Livewire::actingAs($user)
        ->test('dashboard.site-redirects', ['siteId' => $site->id])
        ->call('toggleActive', $rule->id);

    expect($rule->fresh()->is_active)->toBeFalse();

    // Delete rule
    Livewire::actingAs($user)
        ->test('dashboard.site-redirects', ['siteId' => $site->id])
        ->call('deleteRedirect', $rule->id);

    expect($site->redirects()->count())->toBe(0);
});
