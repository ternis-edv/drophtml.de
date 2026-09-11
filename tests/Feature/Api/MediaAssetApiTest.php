<?php

use App\Models\MediaAsset;
use App\Models\Site;
use App\Models\User;
use App\Services\MediaStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('media');
    Storage::fake('public');
});

test('user can list their media assets via API', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    MediaAsset::create([
        'user_id' => $user->id,
        'original_name' => 'picture.png',
        'path' => 'media/picture.png',
        'disk' => 'media',
        'storage_driver' => 'asset_storage',
        'file_type' => 'image',
        'size' => 1024,
    ]);

    $response = $this->getJson('/api/media');

    $response->assertOk();
    $response->assertJsonFragment(['original_name' => 'picture.png']);
});

test('user can upload media to cloud asset storage via API', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $file = UploadedFile::fake()->image('test-image.jpg', 640, 480);

    $response = $this->postJson('/api/media', [
        'file' => $file,
        'storage_driver' => 'asset_storage',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.storage_driver', 'asset_storage');
    $response->assertJsonPath('data.file_type', 'image');

    $this->assertDatabaseHas('media_assets', [
        'user_id' => $user->id,
        'original_name' => 'test-image.jpg',
        'storage_driver' => 'asset_storage',
    ]);
});

test('user can upload media to site code storage via API', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'api-site',
        'original_name' => 'API Site',
        'path' => 'sites/api-site',
        'status' => 'active',
    ]);

    $file = UploadedFile::fake()->create('whitepaper.pdf', 500, 'application/pdf');

    $response = $this->postJson('/api/media', [
        'file' => $file,
        'site_id' => $site->id,
        'storage_driver' => 'code',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.storage_driver', 'code');
    $response->assertJsonPath('data.site_id', $site->id);

    $this->assertDatabaseHas('media_assets', [
        'user_id' => $user->id,
        'site_id' => $site->id,
        'original_name' => 'whitepaper.pdf',
        'storage_driver' => 'code',
    ]);
});

test('user can migrate media asset via API', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'migrate-api-site',
        'original_name' => 'Migrate API Site',
        'path' => 'sites/migrate-api-site',
        'status' => 'active',
    ]);

    $file = UploadedFile::fake()->image('photo.png');
    $asset = app(MediaStorageService::class)->store($file, $user, $site, 'code');

    $response = $this->postJson("/api/media/{$asset->id}/migrate", [
        'target_driver' => 'asset_storage',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.storage_driver', 'asset_storage');
});

test('user can delete media asset via API', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $file = UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4');
    $asset = app(MediaStorageService::class)->store($file, $user, null, 'asset_storage');

    $response = $this->deleteJson("/api/media/{$asset->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
});

test('user cannot view or delete another user media asset', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $file = UploadedFile::fake()->image('private.png');
    $asset = app(MediaStorageService::class)->store($file, $user1, null, 'asset_storage');

    Sanctum::actingAs($user2);

    $this->getJson("/api/media/{$asset->id}")->assertForbidden();
    $this->deleteJson("/api/media/{$asset->id}")->assertForbidden();
});
