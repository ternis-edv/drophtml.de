<?php

use App\Models\Deployment;
use App\Models\Site;
use App\Models\User;
use App\Services\SiteDeploymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('uploading a site creates an initial deployment and snapshot archive', function () {
    Storage::fake('public');
    Storage::fake('local');

    $user = User::factory()->create();
    $service = app(SiteDeploymentService::class);

    $html = '<html><body><h1>Deployment Test</h1></body></html>';
    $tempZip = tempnam(sys_get_temp_dir(), 'test_zip_');
    $zip = new ZipArchive;
    $zip->open($tempZip, ZipArchive::CREATE);
    $zip->addFromString('index.html', $html);
    $zip->addFromString('style.css', 'body { color: red; }');
    $zip->close();

    $file = new UploadedFile($tempZip, 'bundle.zip', 'application/zip', null, true);

    $site = $service->createSiteFromUpload($file, 'Bundle Project', $user);

    expect($site->deployments)->toHaveCount(1);
    $deployment = $site->deployments->first();

    expect($deployment->status)->toBe('success');
    expect($deployment->source)->toBe('upload');
    expect($deployment->metadata)->toHaveKey('snapshot_path');
    expect($deployment->metadata['file_count'])->toBe(2);

    expect(Storage::disk('local')->exists($deployment->metadata['snapshot_path']))->toBeTrue();
    @unlink($tempZip);
});

test('deploying content from editor creates deployment and snapshot', function () {
    Storage::fake('public');
    Storage::fake('local');

    $user = User::factory()->create();
    $service = app(SiteDeploymentService::class);

    $site = $service->createSiteFromContent('<h1>Editor Test</h1>', 'Editor Project', $user);

    expect($site->deployments)->toHaveCount(1);
    $deployment = $site->deployments->first();

    expect($deployment->status)->toBe('success');
    expect($deployment->source)->toBe('editor');
    expect(Storage::disk('local')->exists($deployment->metadata['snapshot_path']))->toBeTrue();
});

test('rollback restores previous site files and captures a rollback deployment', function () {
    Storage::fake('public');
    Storage::fake('local');

    $user = User::factory()->create();
    $service = app(SiteDeploymentService::class);

    // 1. First deployment: V1
    $site = $service->createSiteFromContent('<h1>Version 1</h1>', 'Rollback Project', $user);
    $v1Deployment = $site->deployments()->first();

    // 2. Second deployment: V2
    $tempZip = tempnam(sys_get_temp_dir(), 'v2_');
    $zip = new ZipArchive;
    $zip->open($tempZip, ZipArchive::CREATE);
    $zip->addFromString('index.html', '<h1>Version 2</h1>');
    $zip->close();

    $file = new UploadedFile($tempZip, 'v2.zip', 'application/zip', null, true);
    $v2Deployment = $service->deployToExistingSite($site, $file, $user, 'Upgrade to V2');
    @unlink($tempZip);

    expect(Storage::disk('public')->get("{$site->path}/index.html"))->toContain('Version 2');
    expect($site->deployments()->count())->toBe(2);

    // 3. Rollback to V1
    $rollbackDeployment = $service->rollbackToDeployment($site, $v1Deployment, $user);

    expect($rollbackDeployment->source)->toBe('rollback');
    expect($site->deployments()->count())->toBe(3);
    expect(Storage::disk('public')->get("{$site->path}/index.html"))->toContain('Version 1');
});

test('downloading deployment snapshot zip streams file for site owner', function () {
    Storage::fake('public');
    Storage::fake('local');

    $user = User::factory()->create();
    $service = app(SiteDeploymentService::class);

    $site = $service->createSiteFromContent('<h1>Download Snapshot</h1>', 'Snapshot Site', $user);
    $deployment = $site->deployments()->first();

    $this->actingAs($user);

    $response = $this->get(route('dashboard.sites.deployments.download', [$site, $deployment]));
    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/zip');
});

test('api deploy endpoint allows authorized user to redeploy existing site', function () {
    Storage::fake('public');
    Storage::fake('local');

    $user = User::factory()->create();
    $service = app(SiteDeploymentService::class);

    $site = $service->createSiteFromContent('<h1>Original</h1>', 'API Site', $user);

    Sanctum::actingAs($user, ['*']);

    $tempZip = tempnam(sys_get_temp_dir(), 'api_deploy_');
    $zip = new ZipArchive;
    $zip->open($tempZip, ZipArchive::CREATE);
    $zip->addFromString('index.html', '<h1>Redeployed via API</h1>');
    $zip->close();

    $file = new UploadedFile($tempZip, 'update.zip', 'application/zip', null, true);

    $response = $this->post("/api/sites/{$site->id}/deploy", [
        'file' => $file,
        'commit_message' => 'CI Deployment #42',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.source', 'api');
    $response->assertJsonPath('data.commit_message', 'CI Deployment #42');

    expect(Storage::disk('public')->get("{$site->path}/index.html"))->toContain('Redeployed via API');
    @unlink($tempZip);
});

test('api rollback endpoint rolls back site to previous deployment', function () {
    Storage::fake('public');
    Storage::fake('local');

    $user = User::factory()->create();
    $service = app(SiteDeploymentService::class);

    $site = $service->createSiteFromContent('<h1>V1</h1>', 'Rollback API Site', $user);
    $v1Deployment = $site->deployments()->first();

    $tempZip = tempnam(sys_get_temp_dir(), 'api_v2_');
    $zip = new ZipArchive;
    $zip->open($tempZip, ZipArchive::CREATE);
    $zip->addFromString('index.html', '<h1>V2</h1>');
    $zip->close();

    $file = new UploadedFile($tempZip, 'v2.zip', 'application/zip', null, true);
    $service->deployToExistingSite($site, $file, $user, 'V2 Update');
    @unlink($tempZip);

    Sanctum::actingAs($user, ['*']);

    $response = $this->post("/api/sites/{$site->id}/deployments/{$v1Deployment->id}/rollback");
    $response->assertStatus(201);
    $response->assertJsonPath('data.source', 'rollback');

    expect(Storage::disk('public')->get("{$site->path}/index.html"))->toContain('V1');
});

test('dynamic robots.txt serves valid robots.txt pointing to sitemap if none uploaded', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'robots-site',
        'path' => 'sites/robots-site',
        'status' => 'active',
    ]);

    Storage::disk('public')->put("{$site->path}/index.html", '<h1>Hello</h1>');

    $response = $this->get("/s/{$site->slug}/robots.txt");
    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    $response->assertSee('User-agent: *');
    $response->assertSee('Allow: /');
    $response->assertSee('sitemap.xml');
});

test('dynamic sitemap.xml generates standard XML with HTML pages', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'sitemap-site',
        'path' => 'sites/sitemap-site',
        'status' => 'active',
    ]);

    Storage::disk('public')->put("{$site->path}/index.html", '<h1>Home</h1>');
    Storage::disk('public')->put("{$site->path}/pricing.html", '<h1>Pricing</h1>');
    Storage::disk('public')->put("{$site->path}/style.css", 'body { color: blue; }');
    Storage::disk('public')->put("{$site->path}/404.html", '<h1>Not Found</h1>');

    $response = $this->get("/s/{$site->slug}/sitemap.xml");
    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    $content = $response->getContent();
    expect($content)->toContain('<?xml version="1.0" encoding="UTF-8"?>');
    expect($content)->toContain('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
    expect($content)->toContain('pricing');
    expect($content)->not->toContain('404');
    expect($content)->not->toContain('style.css');
});

test('custom robots.txt overrides dynamic generation', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'custom-robots-site',
        'path' => 'sites/custom-robots-site',
        'status' => 'active',
    ]);

    Storage::disk('public')->put("{$site->path}/index.html", '<h1>Home</h1>');
    Storage::disk('public')->put("{$site->path}/robots.txt", "User-agent: BadBot\nDisallow: /private");

    $response = $this->get("/s/{$site->slug}/robots.txt");
    $response->assertOk();
    $response->assertSee('Disallow: /private');
});
