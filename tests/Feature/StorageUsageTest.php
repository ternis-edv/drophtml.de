<?php

use App\Models\MediaAsset;
use App\Models\Site;
use App\Models\User;
use App\Services\SiteDeploymentService;
use App\Services\StorageUsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('storage usage service calculates accurate totals across media and site code', function () {
    Storage::fake('public');
    Storage::fake('media');

    $user = User::factory()->create([
        'storage_limit_bytes' => 500000000, // 500 MB
    ]);

    // Create a site with code files
    $slug = 'storage-test-site';
    $sitePath = "sites/{$slug}";
    Storage::disk('public')->put("{$sitePath}/index.html", str_repeat('A', 1000));
    Storage::disk('public')->put("{$sitePath}/app.js", str_repeat('B', 2000));

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => $slug,
        'path' => $sitePath,
        'original_name' => 'Storage Site',
        'status' => 'active',
        'storage_bytes' => 3000,
    ]);

    // Create cloud media asset
    $mediaPath = "sites/{$site->id}/banner.png";
    Storage::disk('media')->put($mediaPath, str_repeat('M', 5000));

    MediaAsset::create([
        'user_id' => $user->id,
        'site_id' => $site->id,
        'original_name' => 'banner.png',
        'path' => $mediaPath,
        'disk' => 'media',
        'storage_driver' => 'asset_storage',
        'file_type' => 'image',
        'size' => 5000,
    ]);

    $service = app(StorageUsageService::class);
    $stats = $service->getUserStorageStats($user);

    expect($stats['breakdown']['site_code_bytes'])->toBe(3000);
    expect($stats['breakdown']['cloud_media_bytes'])->toBe(5000);
    expect($stats['total_used_bytes'])->toBe(8000);
    expect($stats['quota_bytes'])->toBe(500000000);
    expect($stats['remaining_bytes'])->toBe(499992000);
    expect($stats['is_over_quota'])->toBeFalse();
    expect($stats['counts']['sites'])->toBe(1);
    expect($stats['counts']['cloud_media_assets'])->toBe(1);
});

test('quota enforcement blocks deployments when quota would be exceeded', function () {
    Storage::fake('public');

    $user = User::factory()->create([
        'storage_limit_bytes' => 1000, // Tiny 1 KB quota
    ]);

    // File that exceeds quota
    $file = UploadedFile::fake()->createWithContent('index.html', str_repeat('Z', 2000));

    $deploymentService = app(SiteDeploymentService::class);

    expect(function () use ($deploymentService, $file, $user) {
        $deploymentService->createSiteFromUpload($file, 'large-site.zip', $user);
    })->toThrow(ValidationException::class);
});

test('recalculating site storage updates site storage_bytes correctly', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $slug = 'recalc-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/index.html", str_repeat('X', 1234));
    Storage::disk('public')->put("{$path}/style.css", str_repeat('Y', 5678));

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'Recalc Site',
        'status' => 'active',
        'storage_bytes' => 0,
    ]);

    $service = app(StorageUsageService::class);
    $bytes = $service->calculateSiteStorageBytes($site);

    expect($bytes)->toBe(1234 + 5678);
    $site->refresh();
    expect($site->storage_bytes)->toBe(1234 + 5678);
});

test('authenticated user can fetch storage telemetry via REST API', function () {
    Storage::fake('public');
    Storage::fake('media');

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/user/storage');

    $response->assertOk();
    $response->assertJsonStructure([
        'total_used_bytes',
        'quota_bytes',
        'remaining_bytes',
        'used_percentage',
        'formatted' => ['total_used', 'quota', 'remaining'],
        'breakdown' => ['cloud_media_bytes', 'site_code_bytes', 'snapshots_bytes'],
        'counts' => ['sites', 'media_assets', 'cloud_media_assets'],
        'file_types',
        'sites',
    ]);
});

test('site REST API resource includes storage telemetry', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'api-storage-site',
        'path' => 'sites/api-storage-site',
        'original_name' => 'API Storage Site',
        'status' => 'active',
        'storage_bytes' => 150000,
    ]);

    $response = $this->getJson("/api/sites/{$site->id}");

    $response->assertOk();
    $response->assertJsonPath('data.storage_bytes', 150000);
    $response->assertJsonPath('data.formatted_storage_bytes', '146.5 KB');
});
