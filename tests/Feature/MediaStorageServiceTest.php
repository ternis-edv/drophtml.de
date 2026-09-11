<?php

use App\Models\MediaAsset;
use App\Models\Site;
use App\Models\User;
use App\Services\MediaStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('media');
    Storage::fake('public');
    $this->service = app(MediaStorageService::class);
});

test('can store media asset in cloud asset storage (S3/R2)', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('banner.png', 800, 600);

    $asset = $this->service->store($file, $user, null, 'asset_storage');

    expect($asset)->toBeInstanceOf(MediaAsset::class)
        ->and($asset->storage_driver)->toBe('asset_storage')
        ->and($asset->disk)->toBe('media')
        ->and($asset->file_type)->toBe('image')
        ->and($asset->original_name)->toBe('banner.png');

    Storage::disk('media')->assertExists($asset->path);
    $this->assertDatabaseHas('media_assets', [
        'id' => $asset->id,
        'user_id' => $user->id,
        'storage_driver' => 'asset_storage',
    ]);
});

test('can store video in site code repository', function () {
    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'test-site',
        'original_name' => 'Test Site',
        'path' => 'sites/test-site',
        'status' => 'active',
    ]);

    $file = UploadedFile::fake()->create('intro.mp4', 1024, 'video/mp4');

    $asset = $this->service->store($file, $user, $site, 'code');

    expect($asset->storage_driver)->toBe('code')
        ->and($asset->disk)->toBe('public')
        ->and($asset->file_type)->toBe('video')
        ->and($asset->site_id)->toBe($site->id);

    Storage::disk('public')->assertExists($asset->path);
    expect($asset->path)->toContain('sites/test-site/videos/');
});

test('rejects dangerous executable files from upload', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('malicious.php', 10, 'text/x-php');

    expect(fn () => $this->service->store($file, $user, null, 'asset_storage'))
        ->toThrow(InvalidArgumentException::class);
});

test('can delete media asset and cleans up physical file', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

    $asset = $this->service->store($file, $user, null, 'asset_storage');
    Storage::disk('media')->assertExists($asset->path);

    $this->service->delete($asset, $user);

    Storage::disk('media')->assertMissing($asset->path);
    $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
});

test('can migrate media asset from code to cloud asset storage', function () {
    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'migrate-site',
        'original_name' => 'Migrate Site',
        'path' => 'sites/migrate-site',
        'status' => 'active',
    ]);

    $file = UploadedFile::fake()->image('photo.jpg');
    $asset = $this->service->store($file, $user, $site, 'code');

    Storage::disk('public')->assertExists($asset->path);

    $migrated = $this->service->migrate($asset, 'asset_storage', null, $user);

    expect($migrated->storage_driver)->toBe('asset_storage')
        ->and($migrated->disk)->toBe('media');

    Storage::disk('media')->assertExists($migrated->path);
    Storage::disk('public')->assertMissing($asset->path);
});

test('can migrate media asset from cloud asset storage to site code', function () {
    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'to-code-site',
        'original_name' => 'To Code Site',
        'path' => 'sites/to-code-site',
        'status' => 'active',
    ]);

    $file = UploadedFile::fake()->image('logo.png');
    $asset = $this->service->store($file, $user, $site, 'asset_storage');

    Storage::disk('media')->assertExists($asset->path);

    $migrated = $this->service->migrate($asset, 'code', $site, $user);

    expect($migrated->storage_driver)->toBe('code')
        ->and($migrated->disk)->toBe('public');

    Storage::disk('public')->assertExists($migrated->path);
    Storage::disk('media')->assertMissing($asset->path);
});

test('can copy cloud asset into site code without deleting original', function () {
    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'copy-site',
        'original_name' => 'Copy Site',
        'path' => 'sites/copy-site',
        'status' => 'active',
    ]);

    $file = UploadedFile::fake()->create('report.pdf', 300, 'application/pdf');
    $asset = $this->service->store($file, $user, null, 'asset_storage');

    $relPath = $this->service->copyToCode($asset, $site);

    expect($relPath)->toContain('report-');
    Storage::disk('media')->assertExists($asset->path);
    Storage::disk('public')->assertExists("{$site->path}/{$relPath}");
});

test('can promote existing file in site code to cloud asset storage', function () {
    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'promote-site',
        'original_name' => 'Promote Site',
        'path' => 'sites/promote-site',
        'status' => 'active',
    ]);

    Storage::disk('public')->put("{$site->path}/images/hero.png", 'fake image content');

    $asset = $this->service->promoteCodeFileToAssetStorage($site, 'images/hero.png', $user, true);

    expect($asset->storage_driver)->toBe('asset_storage')
        ->and($asset->file_type)->toBe('image')
        ->and($asset->original_name)->toBe('hero.png');

    Storage::disk('media')->assertExists($asset->path);
    Storage::disk('public')->assertMissing("{$site->path}/images/hero.png");
});

test('generates correct HTML and Markdown tags for different media types', function () {
    $user = User::factory()->create();

    $imgFile = UploadedFile::fake()->image('sample.png');
    $imgAsset = $this->service->store($imgFile, $user, null, 'asset_storage');
    expect($imgAsset->html_tag)->toContain('<img src=')
        ->and($imgAsset->markdown_tag)->toContain('![sample.png]');

    $videoFile = UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4');
    $videoAsset = $this->service->store($videoFile, $user, null, 'asset_storage');
    expect($videoAsset->html_tag)->toContain('<video controls')
        ->and($videoAsset->markdown_tag)->toContain('[clip.mp4]');

    $pdfFile = UploadedFile::fake()->create('guide.pdf', 200, 'application/pdf');
    $pdfAsset = $this->service->store($pdfFile, $user, null, 'asset_storage');
    expect($pdfAsset->html_tag)->toContain('<a href=')
        ->and($pdfAsset->isPdf())->toBeTrue();
});
