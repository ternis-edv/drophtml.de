<?php

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('github push webhook triggers site auto-deployment', function () {
    Storage::fake('public');

    $user = User::factory()->create([
        'github_token' => 'gh_token_123',
    ]);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'auto-deploy-site',
        'original_name' => 'My Repo',
        'path' => 'sites/auto-deploy-site',
        'github_repo_full_name' => 'ternis-edv/my-repo',
        'github_branch' => 'main',
        'auto_deploy' => true,
        'status' => 'active',
    ]);

    // Create a mock zip content
    $zipPath = tempnam(sys_get_temp_dir(), 'test_zip');
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('index.html', '<h1>Auto Deployed From Webhook</h1>');
    $zip->close();
    $zipBinary = file_get_contents($zipPath);
    unlink($zipPath);

    Http::fake([
        'https://api.github.com/repos/ternis-edv/my-repo/zipball/main' => Http::response($zipBinary, 200),
    ]);

    $payload = [
        'ref' => 'refs/heads/main',
        'repository' => [
            'full_name' => 'ternis-edv/my-repo',
        ],
        'after' => 'a1b2c3d4e5f6',
        'head_commit' => [
            'message' => 'Update landing page',
        ],
    ];

    $response = $this->postJson('/webhooks/github', $payload);

    $response->assertOk();
    $response->assertJson(['message' => 'Deployments triggered']);

    expect($site->deployments()->count())->toBe(1);
    $deployment = $site->deployments()->first();
    expect($deployment->status)->toBe('success');
    expect($deployment->commit_hash)->toBe('a1b2c3d4e5f6');

    expect(Storage::disk('public')->exists("{$site->path}/index.html"))->toBeTrue();
    expect(Storage::disk('public')->get("{$site->path}/index.html"))->toBe('<h1>Auto Deployed From Webhook</h1>');
});
