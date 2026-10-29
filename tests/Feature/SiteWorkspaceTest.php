<?php

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('uses the shared site workspace shell across site management', function ($section) {
    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id, 'slug' => 'workspace-test', 'original_name' => 'Workspace test',
        'path' => 'sites/workspace-test', 'status' => 'active',
    ]);
    $this->actingAs($user)->get(route('dashboard.sites.'.$section, $site))
        ->assertOk()->assertSee('Site workspace')->assertSee('Open Code Studio')->assertSee('Site sections');
})->with(['media', 'stats', 'deployments', 'domains', 'redirects', 'errors', 'settings']);

it('rejects a site workspace owned by another user', function () {
    $owner = User::factory()->create();
    $site = Site::create([
        'user_id' => $owner->id, 'slug' => 'other-site', 'path' => 'sites/other-site', 'status' => 'active',
    ]);
    $this->actingAs(User::factory()->create())->get(route('dashboard.sites.media', $site))->assertNotFound();
});

it('keeps global navigation available across the dashboard', function ($route) {
    $this->actingAs(User::factory()->create())->get(route($route))
        ->assertOk()
        ->assertSee('site-console', false)
        ->assertSee('dashboard-navigation', false)
        ->assertSee('All sites')
        ->assertSee('Deploy site')
        ->assertSee('Account settings')
        ->assertSee('Close navigation');
})->with(['dashboard', 'dashboard.sites.upload', 'dashboard.media', 'dashboard.activity', 'dashboard.help', 'profile.edit', 'settings.storage', 'api-tokens.edit', 'appearance.edit', 'settings.data-export']);
