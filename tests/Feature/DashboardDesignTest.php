<?php

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('uses the dashboard design system on every global and account view', function ($route) {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->get(route($route))
        ->assertOk()
        ->assertSee('dashboard-ui', false)
        ->assertSee('dashboard-', false);
})->with(['dashboard', 'dashboard.sites.upload', 'dashboard.media', 'dashboard.activity', 'dashboard.help', 'profile.edit', 'security.edit', 'settings.storage', 'api-tokens.edit', 'settings.data-export', 'appearance.edit']);

it('uses the dashboard design system on every site view including the editor', function ($section) {
    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id, 'slug' => 'design-check', 'original_name' => 'Design check',
        'path' => 'sites/design-check', 'status' => 'active',
    ]);
    $this->actingAs($user)->get(route('dashboard.sites.'.$section, $site))
        ->assertOk()->assertSee('dashboard-ui', false);
})->with(['edit', 'media', 'stats', 'deployments', 'domains', 'redirects', 'settings']);

it('keeps the dashboard stylesheet out of public and auth pages', function ($route) {
    $this->get(route($route))->assertOk()->assertDontSee('dashboard-ui', false)->assertDontSee('/assets/dashboard-', false);
})->with(['home', 'login', 'register', 'password.request']);

it('keeps the public uploader presentation while styling dashboard uploads separately', function () {
    Livewire::test('file-uploader')
        ->assertSee('rounded-2xl', false)
        ->assertDontSee('dashboard-rounded', false);
    Livewire::test('file-uploader', ['dashboard' => true])
        ->assertSee('dashboard-rounded', false)
        ->assertDontSee('rounded-2xl', false);
});
