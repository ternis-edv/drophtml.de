<?php

use App\Models\Site;
use App\Models\User;
use App\Services\DomainService;
use App\Services\SiteDeploymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('zip slip path traversal in zip archives is detected and rejected', function () {
    Storage::fake('public');

    $deploymentService = app(SiteDeploymentService::class);
    $user = User::factory()->create();

    $zipPath = tempnam(sys_get_temp_dir(), 'slip_zip');
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('../../evil.txt', 'evil payload');
    $zip->close();

    $file = new UploadedFile($zipPath, 'malicious.zip', 'application/zip', null, true);

    expect(fn () => $deploymentService->createSiteFromUpload($file, 'Malicious Site', $user))
        ->toThrow(InvalidArgumentException::class, 'Security violation');

    @unlink($zipPath);
});

test('zip archives with dangerous php files are rejected', function () {
    Storage::fake('public');

    $deploymentService = app(SiteDeploymentService::class);
    $user = User::factory()->create();

    $zipPath = tempnam(sys_get_temp_dir(), 'php_zip');
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('index.html', '<h1>Hello</h1>');
    $zip->addFromString('shell.php', '<?php phpinfo(); ?>');
    $zip->close();

    $file = new UploadedFile($zipPath, 'dangerous.zip', 'application/zip', null, true);

    expect(fn () => $deploymentService->createSiteFromUpload($file, 'Dangerous Site', $user))
        ->toThrow(InvalidArgumentException::class, 'Security violation: ZIP contains executable or dangerous file');

    @unlink($zipPath);
});

test('direct upload of php file is rejected', function () {
    Storage::fake('public');

    $deploymentService = app(SiteDeploymentService::class);
    $user = User::factory()->create();

    $file = UploadedFile::fake()->create('exploit.php', 10, 'application/x-php');

    expect(fn () => $deploymentService->createSiteFromUpload($file, 'exploit.php', $user))
        ->toThrow(InvalidArgumentException::class, 'Uploading .php files is not permitted');
});

test('reserved platform domains cannot be added as custom domains', function () {
    $domainService = app(DomainService::class);
    $user = User::factory()->create();

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'test-reserved',
        'original_name' => 'test',
        'path' => 'sites/test-reserved',
        'status' => 'active',
    ]);

    expect(fn () => $domainService->createDomain($site, 'drophtml.de'))
        ->toThrow(InvalidArgumentException::class, 'reserved');

    expect(fn () => $domainService->createDomain($site, 'api.drophtml.de'))
        ->toThrow(InvalidArgumentException::class, 'reserved');

    expect(fn () => $domainService->createDomain($site, 'localhost'))
        ->toThrow(InvalidArgumentException::class, 'reserved');
});

test('users cannot access or modify sites belonging to other users via API', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $site = Site::create([
        'user_id' => $userA->id,
        'slug' => 'user-a-site',
        'original_name' => 'User A Site',
        'path' => 'sites/user-a-site',
        'status' => 'active',
    ]);

    // User B tries to view User A's site
    $response = $this->actingAs($userB, 'sanctum')->getJson("/api/sites/{$site->id}");
    $response->assertForbidden();

    // User B tries to update User A's site
    $response = $this->actingAs($userB, 'sanctum')->putJson("/api/sites/{$site->id}", [
        'status' => 'suspended',
    ]);
    $response->assertForbidden();

    // User B tries to delete User A's site
    $response = $this->actingAs($userB, 'sanctum')->deleteJson("/api/sites/{$site->id}");
    $response->assertForbidden();
});

test('users cannot add domains to sites belonging to other users via API', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $site = Site::create([
        'user_id' => $userA->id,
        'slug' => 'user-a-site-2',
        'original_name' => 'User A Site',
        'path' => 'sites/user-a-site-2',
        'status' => 'active',
    ]);

    $response = $this->actingAs($userB, 'sanctum')->postJson("/api/sites/{$site->id}/domains", [
        'domain' => 'unauthorized-domain.com',
    ]);
    $response->assertForbidden();
});

test('hidden files access via site serving is blocked', function () {
    Storage::fake('public');

    $slug = 'hidden-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/.env", 'APP_KEY=secret');
    Storage::disk('public')->put("{$path}/index.html", '<h1>Safe</h1>');

    Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'site.zip',
        'status' => 'active',
    ]);

    $response = $this->get("/s/{$slug}/.env");
    $response->assertNotFound();
});

test('github webhook rejects requests with invalid signature when secret is configured', function () {
    config(['services.github.webhook_secret' => 'supersecret']);

    $payload = [
        'ref' => 'refs/heads/main',
        'repository' => ['full_name' => 'test/repo'],
    ];

    $response = $this->postJson('/webhooks/github', $payload, [
        'X-Hub-Signature-256' => 'sha256=invalidhash',
    ]);

    $response->assertStatus(401);
    $response->assertJson(['message' => 'Invalid webhook signature']);
});
