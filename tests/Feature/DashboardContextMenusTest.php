<?php

use App\Models\MediaAsset;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('offers context actions on site cards and table rows while keeping existing actions', function () {
    $user = User::factory()->create();
    Site::create(['user_id' => $user->id, 'slug' => 'context-site', 'original_name' => 'Context site', 'path' => 'sites/context-site', 'status' => 'active']);
    Livewire::actingAs($user)->test('dashboard.sites-overview')
        ->assertSee('x-data="dashboardContextMenu"', false)
        ->assertSee('role="menuitem"', false)
        ->assertDontSee('@js($site', false)
        ->assertSee('Copy site URL')->assertSee('Delete site')->assertSee('More actions for');
});

it('offers context actions for media assets and editor files', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $site = Site::create(['user_id' => $user->id, 'slug' => 'context-files', 'path' => 'sites/context-files', 'status' => 'active']);
    Storage::disk('public')->put($site->path.'/index.html', '<h1>Hello</h1>');
    MediaAsset::create(['user_id' => $user->id, 'original_name' => 'picture.png', 'storage_key' => 'picture.png', 'disk' => 'media', 'storage_location' => 'asset_storage', 'file_type' => 'image', 'size' => 12]);
    Livewire::actingAs($user)->test('dashboard.media-manager')->assertSee('Copy asset URL')->assertSee('Delete asset');
    $this->actingAs($user)->get(route('dashboard.sites.edit', $site))->assertOk()
        ->assertSee('x-data="dashboardContextMenu"', false)->assertSee('Rename file')->assertSee('Delete file');
});
