<?php

use App\Models\MediaAsset;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('user can download site code backup via web route', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $slug = 'export-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/index.html", '<h1>Export Me</h1>');
    Storage::disk('public')->put("{$path}/style.css", 'body { margin: 0; }');

    $site = Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'export-site.zip',
        'status' => 'active',
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.sites.download', $site));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/zip');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment;');
    expect($response->headers->get('Content-Disposition'))->toContain("{$slug}-backup-");
});

test('user can download universal backup containing cloud media and export manifest', function () {
    Storage::fake('public');
    Storage::fake('media');

    $user = User::factory()->create();
    $slug = 'universal-export-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/index.html", '<h1>Universal Export</h1>');

    $site = Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'universal-export.zip',
        'status' => 'active',
        'user_id' => $user->id,
    ]);

    // Create cloud media asset
    $mediaPath = "sites/{$site->id}/banner.png";
    Storage::disk('media')->put($mediaPath, 'fake-png-binary-content');

    MediaAsset::create([
        'user_id' => $user->id,
        'site_id' => $site->id,
        'original_name' => 'banner.png',
        'path' => $mediaPath,
        'disk' => 'media',
        'storage_driver' => 'asset_storage',
        'file_type' => 'image',
        'mime_type' => 'image/png',
        'size' => 24,
        'public_url' => "https://assets.drophtml.de/{$mediaPath}",
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.sites.download', ['site' => $site, 'media' => 1]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/zip');
    expect($response->headers->get('Content-Disposition'))->toContain("{$slug}-universal-backup-");

    // Inspect ZIP entries
    $zipFile = $response->getFile()->getPathname();
    $zip = new ZipArchive;
    expect($zip->open($zipFile))->toBeTrue();
    expect($zip->locateName('index.html'))->not->toBeFalse();
    expect($zip->locateName('_cloud_media/banner.png'))->not->toBeFalse();
    expect($zip->locateName('export-manifest.json'))->not->toBeFalse();
    $manifestContent = json_decode($zip->getFromName('export-manifest.json'), true);
    expect($manifestContent['includes_cloud_media'])->toBeTrue();
    expect($manifestContent['slug'])->toBe($slug);
    $zip->close();
});

test('authenticated API user can download site zip via REST API', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $slug = 'api-export-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/index.html", '<h1>API Download Test</h1>');

    $site = Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'api-site.zip',
        'status' => 'active',
        'user_id' => $user->id,
    ]);

    $response = $this->getJson("/api/sites/{$site->id}/download");

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/zip');
});

test('another user cannot download site zip via REST API', function () {
    Storage::fake('public');

    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    Sanctum::actingAs($otherUser);

    $slug = 'private-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/index.html", '<h1>Private</h1>');

    $site = Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'private-site.zip',
        'status' => 'active',
        'user_id' => $owner->id,
    ]);

    $response = $this->getJson("/api/sites/{$site->id}/download");

    $response->assertForbidden();
});
