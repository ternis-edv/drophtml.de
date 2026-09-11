<?php

use App\Models\MediaAsset;
use App\Models\User;
use App\Services\MediaStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('media');
    Storage::fake('public');
});

test('user can visit static media hosting dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard.media'))
        ->assertOk()
        ->assertSee('Static Media Hosting');
});

test('user can upload media via Livewire media manager component', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $file = UploadedFile::fake()->image('nature.webp');

    Livewire::test('dashboard.media-manager')
        ->set('storageDestination', 'asset_storage')
        ->set('uploadFiles', [$file])
        ->call('uploadMedia');

    $this->assertDatabaseHas('media_assets', [
        'user_id' => $user->id,
        'original_name' => 'nature.webp',
        'storage_driver' => 'asset_storage',
    ]);
});

test('user can delete media via Livewire media manager', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $file = UploadedFile::fake()->create('contract.pdf', 300, 'application/pdf');
    $asset = app(MediaStorageService::class)->store($file, $user, null, 'asset_storage');

    Livewire::test('dashboard.media-manager')
        ->call('deleteAsset', $asset->id);

    $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
});

test('user can filter media assets by type and storage driver', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    MediaAsset::create([
        'user_id' => $user->id,
        'original_name' => 'logo.svg',
        'path' => 'media/logo.svg',
        'disk' => 'media',
        'storage_driver' => 'asset_storage',
        'file_type' => 'image',
        'size' => 500,
    ]);

    MediaAsset::create([
        'user_id' => $user->id,
        'original_name' => 'podcast.mp3',
        'path' => 'media/podcast.mp3',
        'disk' => 'media',
        'storage_driver' => 'asset_storage',
        'file_type' => 'audio',
        'size' => 5000,
    ]);

    Livewire::test('dashboard.media-manager')
        ->set('typeFilter', 'image')
        ->assertSee('logo.svg')
        ->assertDontSee('podcast.mp3');
});
