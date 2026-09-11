<?php

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('storage settings page requires authentication', function () {
    $this->get('/settings/storage')
        ->assertRedirect('/login');
});

test('authenticated user can view storage settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.storage'))
        ->assertOk()
        ->assertSee('Storage &amp; Usage', false)
        ->assertSee('Account Storage Capacity');
});

test('storage settings Livewire component displays breakdown and handles recalculate', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    $slug = 'settings-storage-site';
    $path = "sites/{$slug}";
    Storage::disk('public')->put("{$path}/index.html", str_repeat('X', 4096));

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'Settings Storage Site',
        'status' => 'active',
        'storage_bytes' => 0,
    ]);

    Livewire::test('pages::settings.storage')
        ->assertOk()
        ->assertSee('Settings Storage Site')
        ->call('recalculateSite', $site->id)
        ->assertHasNoErrors();

    $site->refresh();
    expect($site->storage_bytes)->toBe(4096);

    // Call recalculateAll
    Livewire::test('pages::settings.storage')
        ->call('recalculateAll')
        ->assertHasNoErrors();
});
