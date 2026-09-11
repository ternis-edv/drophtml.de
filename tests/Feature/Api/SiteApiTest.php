<?php

use App\Models\MediaAsset;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('user can list their sites via API', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'user-site-1',
        'original_name' => 'Site 1',
        'path' => 'sites/user-site-1',
        'status' => 'active',
    ]);

    $response = $this->getJson('/api/sites');

    $response->assertOk();
    $response->assertJsonFragment(['slug' => 'user-site-1']);
});

test('user can create a site via API with file upload', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $file = UploadedFile::fake()->create('landing.html', 10, 'text/html');

    $response = $this->postJson('/api/sites', [
        'original_name' => 'landing.html',
        'file' => $file,
        'is_password_protected' => true,
        'site_password' => 'pass123',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('sites', [
        'user_id' => $user->id,
        'original_name' => 'landing.html',
        'is_password_protected' => true,
    ]);
});

test('user can update site settings via API', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'user-site-2',
        'original_name' => 'Site 2',
        'path' => 'sites/user-site-2',
        'status' => 'active',
    ]);

    $response = $this->putJson("/api/sites/{$site->id}", [
        'status' => 'suspended',
        'is_permanent' => true,
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('sites', [
        'id' => $site->id,
        'status' => 'suspended',
        'is_permanent' => true,
    ]);
});

test('user can delete a site via API', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'to-delete',
        'original_name' => 'To Delete',
        'path' => 'sites/to-delete',
        'status' => 'active',
    ]);

    Storage::disk('public')->put('sites/to-delete/index.html', 'content');

    $response = $this->deleteJson("/api/sites/{$site->id}");

    $response->assertOk();
    $this->assertSoftDeleted('sites', ['id' => $site->id]);
    Storage::disk('public')->assertMissing('sites/to-delete');
});

test('user can duplicate a site via API including media assets', function () {
    Storage::fake('public');
    Storage::fake('media');
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'original-site',
        'original_name' => 'Original Site',
        'path' => 'sites/original-site',
        'status' => 'active',
    ]);

    Storage::disk('public')->put('sites/original-site/index.html', '<h1>Hello World</h1>');

    $mediaAsset = MediaAsset::create([
        'user_id' => $user->id,
        'site_id' => $site->id,
        'original_name' => 'hero.webp',
        'path' => "sites/{$site->id}/media/hero-123.webp",
        'disk' => 'media',
        'storage_driver' => 'asset_storage',
        'file_type' => 'image',
        'mime_type' => 'image/webp',
        'size' => 1024,
        'public_url' => 'https://assets.drophtml.de/media/hero.webp',
    ]);

    Storage::disk('media')->put($mediaAsset->path, 'fake image binary');

    $response = $this->postJson("/api/sites/{$site->id}/duplicate", [
        'custom_slug' => 'my-cloned-site',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.slug', 'my-cloned-site');
    $response->assertJsonPath('data.media_assets_count', 1);

    $this->assertDatabaseHas('sites', [
        'user_id' => $user->id,
        'slug' => 'my-cloned-site',
    ]);

    $clonedSite = Site::where('slug', 'my-cloned-site')->first();
    expect($clonedSite)->not->toBeNull();
    expect(Storage::disk('public')->exists("{$clonedSite->path}/index.html"))->toBeTrue();
    expect(Storage::disk('media')->exists("sites/{$clonedSite->id}/media/hero-123.webp"))->toBeTrue();
    expect($clonedSite->mediaAssets()->count())->toBe(1);
});

test('user cannot duplicate another users site via API', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    Sanctum::actingAs($user2);

    $site = Site::create([
        'user_id' => $user1->id,
        'slug' => 'user1-site',
        'original_name' => 'User1 Site',
        'path' => 'sites/user1-site',
        'status' => 'active',
    ]);

    $response = $this->postJson("/api/sites/{$site->id}/duplicate");
    $response->assertForbidden();
});

test('deleting a site via API cascades and permanently cleans up cloud media assets', function () {
    Storage::fake('public');
    Storage::fake('media');
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'site-to-cascade-delete',
        'original_name' => 'Cascade Site',
        'path' => 'sites/cascade-delete',
        'status' => 'active',
    ]);

    Storage::disk('public')->put('sites/cascade-delete/index.html', 'content');

    $mediaAsset = MediaAsset::create([
        'user_id' => $user->id,
        'site_id' => $site->id,
        'original_name' => 'logo.png',
        'path' => "sites/{$site->id}/media/logo-456.png",
        'disk' => 'media',
        'storage_driver' => 'asset_storage',
        'file_type' => 'image',
        'mime_type' => 'image/png',
        'size' => 2048,
    ]);

    Storage::disk('media')->put($mediaAsset->path, 'fake logo content');

    $response = $this->deleteJson("/api/sites/{$site->id}");
    $response->assertOk();

    // Verify public site files are deleted
    Storage::disk('public')->assertMissing('sites/cascade-delete');

    // Verify cloud media folder for site is deleted
    Storage::disk('media')->assertMissing("sites/{$site->id}");

    // Verify database record in media_assets is permanently cleaned up
    $this->assertDatabaseMissing('media_assets', [
        'id' => $mediaAsset->id,
    ]);
});
