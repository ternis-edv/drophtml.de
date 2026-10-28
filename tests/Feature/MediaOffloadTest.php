<?php

use App\Models\MediaAsset;
use App\Models\Site;
use App\Models\User;
use App\Services\MediaStorageService;
use App\Services\SiteDeploymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('media');
    Storage::fake('public');

    config()->set('filesystems.disks.media.driver', 's3');
    config()->set('media.public_url', 'https://assets.drophtml.de');
    config()->set('media.offload.enabled', true);
    config()->set('media.offload.threshold_kb', 100); // 100 KB for test files
    config()->set('media.offload.types', ['image', 'video']);

    $this->service = app(MediaStorageService::class);
});

function makeOffloadSite(User $user): Site
{
    return Site::create([
        'user_id' => $user->id,
        'slug' => 'big-assets',
        'original_name' => 'Big Assets',
        'path' => 'sites/big-assets',
        'status' => 'active',
    ]);
}

test('publicUrlFor prefers the configured custom domain', function () {
    expect($this->service->publicUrlFor('sites/1/media/hero.mp4'))
        ->toBe('https://assets.drophtml.de/sites/1/media/hero.mp4');
});

test('publicUrlFor falls back to the disk url without a custom domain', function () {
    config()->set('media.public_url', null);

    $url = $this->service->publicUrlFor('sites/1/media/hero.mp4');

    expect($url)->not->toContain('assets.drophtml.de');
});

test('large images in a code bundle are offloaded to cloud storage and references are rewritten', function () {
    $user = User::factory()->create();
    $site = makeOffloadSite($user);

    Storage::disk('public')->put("{$site->path}/images/hero.jpg", random_bytes(200 * 1024));
    Storage::disk('public')->put("{$site->path}/css/site.css", 'body { background: url("/images/hero.jpg"); }');
    Storage::disk('public')->put("{$site->path}/index.html", <<<'HTML'
        <html><body>
            <img src="images/hero.jpg" alt="Hero">
            <img src="/images/hero.jpg" srcset="images/hero.jpg 1x, /images/hero.jpg 2x">
            <a href="./images/hero.jpg?raw=1">download</a>
        </body></html>
        HTML);

    $offloaded = $this->service->offloadLargeAssets($site, $user);

    $asset = MediaAsset::where('site_id', $site->id)->firstOrFail();
    expect($asset->path)->toBe($asset->id.'.jpg');
    expect($offloaded)->toHaveCount(1)
        ->and($offloaded[0]['path'])->toBe('images/hero.jpg')
        ->and($offloaded[0]['url'])->toBe("https://assets.drophtml.de/{$asset->id}.jpg");

    // File moved from code bundle to cloud storage
    $asset = MediaAsset::where('site_id', $site->id)->firstOrFail();
    Storage::disk('public')->assertMissing("{$site->path}/images/hero.jpg");
    Storage::disk('media')->assertExists($asset->path);

    // MediaAsset tracked as cloud asset with the custom-domain URL
    $asset = MediaAsset::where('site_id', $site->id)->first();
    expect($asset->storage_driver)->toBe('asset_storage')
        ->and($asset->public_url)->toBe("https://assets.drophtml.de/{$asset->id}.jpg")
        ->and($asset->metadata['offloaded_from_code'])->toBe('images/hero.jpg');

    // References rewritten in HTML and CSS
    $html = Storage::disk('public')->get("{$site->path}/index.html");
    $css = Storage::disk('public')->get("{$site->path}/css/site.css");

    expect($html)->toContain("https://assets.drophtml.de/{$asset->id}.jpg")
        ->not->toContain('src="images/hero.jpg"')
        ->and($css)->toContain("url(\"https://assets.drophtml.de/{$asset->id}.jpg\")");
});

test('small files and non-media files stay in the code bundle', function () {
    $user = User::factory()->create();
    $site = makeOffloadSite($user);

    Storage::disk('public')->put("{$site->path}/images/tiny.png", random_bytes(5 * 1024));
    Storage::disk('public')->put("{$site->path}/assets/big-archive.zip", random_bytes(200 * 1024));

    $offloaded = $this->service->offloadLargeAssets($site, $user);

    expect($offloaded)->toBeEmpty();
    Storage::disk('public')->assertExists("{$site->path}/images/tiny.png");
    Storage::disk('public')->assertExists("{$site->path}/assets/big-archive.zip");
});

test('offload is a no-op when disabled', function () {
    config()->set('media.offload.enabled', false);

    $user = User::factory()->create();
    $site = makeOffloadSite($user);
    Storage::disk('public')->put("{$site->path}/videos/clip.mp4", random_bytes(200 * 1024));

    expect($this->service->offloadLargeAssets($site, $user))->toBeEmpty();
    Storage::disk('public')->assertExists("{$site->path}/videos/clip.mp4");
});

test('site uploads automatically offload large media', function () {
    $user = User::factory()->create();

    $zipPath = tempnam(sys_get_temp_dir(), 'offload_').'.zip';
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('index.html', '<html><body><img src="images/hero.jpg"></body></html>');
    $zip->addFromString('images/hero.jpg', random_bytes(200 * 1024));
    $zip->close();

    $file = new UploadedFile($zipPath, 'my-site.zip', 'application/zip', null, true);

    $site = app(SiteDeploymentService::class)->createSiteFromUpload($file, 'my-site.zip', $user);

    $asset = MediaAsset::where('site_id', $site->id)->firstOrFail();
    Storage::disk('public')->assertMissing("{$site->path}/images/hero.jpg");
    Storage::disk('media')->assertExists($asset->path);

    $html = Storage::disk('public')->get("{$site->path}/index.html");
    expect($html)->toContain("https://assets.drophtml.de/{$asset->id}.jpg");

    @unlink($zipPath);
});
