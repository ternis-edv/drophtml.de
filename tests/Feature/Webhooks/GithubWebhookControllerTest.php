<?php

namespace Tests\Feature\Webhooks;

use App\Models\ActivityLog;
use App\Models\Deployment;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GithubWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_invalid_payloads()
    {
        // Missing repository
        $response = $this->postJson(route('webhooks.github'), [
            'ref' => 'refs/heads/main',
        ]);
        $response->assertStatus(400);

        // Missing ref
        $response = $this->postJson(route('webhooks.github'), [
            'repository' => ['full_name' => 'test/repo'],
        ]);
        $response->assertStatus(400);
    }

    public function test_it_handles_valid_push_without_matching_sites()
    {
        $response = $this->postJson(route('webhooks.github'), [
            'repository' => ['full_name' => 'test/repo'],
            'ref' => 'refs/heads/main',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Deployments triggered']);
    }

    public function test_it_deploys_site_on_valid_push()
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'github_token' => 'dummy-token',
        ]);

        $site = Site::create([
            'user_id' => $user->id,
            'slug' => 'test-slug',
            'github_repo_full_name' => 'test/repo',
            'github_branch' => 'main',
            'auto_deploy' => true,
            'path' => 'sites/test-slug',
        ]);

        // Create a dummy zip file in memory to return from HTTP mock
        $zip = new \ZipArchive();
        $zipPath = tempnam(sys_get_temp_dir(), 'testzip');
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFromString('index.html', '<h1>Hello World</h1>');
        $zip->close();
        $zipContent = file_get_contents($zipPath);
        unlink($zipPath);

        Http::fake([
            "https://api.github.com/repos/test/repo/zipball/main" => Http::response($zipContent, 200)
        ]);

        $response = $this->postJson(route('webhooks.github'), [
            'repository' => ['full_name' => 'test/repo'],
            'ref' => 'refs/heads/main',
            'after' => 'dummy-commit-hash',
            'head_commit' => [
                'message' => 'Test commit message'
            ]
        ]);

        $response->assertStatus(200);

        // Assert file extracted
        Storage::disk('public')->assertExists($site->path . '/index.html');
        $this->assertEquals('<h1>Hello World</h1>', Storage::disk('public')->get($site->path . '/index.html'));

        // Assert entry_path updated
        $this->assertEquals('index.html', $site->fresh()->entry_path);

        // Assert Deployment created
        $this->assertDatabaseHas('deployments', [
            'site_id' => $site->id,
            'status' => 'success',
            'source' => 'github_webhook',
            'commit_hash' => 'dummy-commit-hash',
            'commit_message' => 'Test commit message',
        ]);

        // Assert ActivityLog created
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'site_id' => $site->id,
            'action' => 'github_auto_deployed',
        ]);
    }

    public function test_it_handles_failed_download_gracefully()
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'github_token' => 'dummy-token',
        ]);

        $site = Site::create([
            'user_id' => $user->id,
            'slug' => 'test-slug',
            'github_repo_full_name' => 'test/repo',
            'github_branch' => 'main',
            'auto_deploy' => true,
            'path' => 'sites/test-slug',
        ]);

        Http::fake([
            "https://api.github.com/repos/test/repo/zipball/main" => Http::response('Server Error', 500)
        ]);

        $response = $this->postJson(route('webhooks.github'), [
            'repository' => ['full_name' => 'test/repo'],
            'ref' => 'refs/heads/main',
        ]);

        $response->assertStatus(200);

        // Assert failed Deployment created
        $this->assertDatabaseHas('deployments', [
            'site_id' => $site->id,
            'status' => 'failed',
            'source' => 'github_webhook',
        ]);

        $deployment = Deployment::where('site_id', $site->id)->first();
        $this->assertStringContainsString('Auto-deploy failed: Failed to download archive', $deployment->commit_message);
    }

    public function test_it_skips_deployment_if_user_has_no_github_token()
    {
        $user = User::factory()->create([
            'github_token' => null,
        ]);

        $site = Site::create([
            'user_id' => $user->id,
            'slug' => 'test-slug',
            'github_repo_full_name' => 'test/repo',
            'github_branch' => 'main',
            'auto_deploy' => true,
            'path' => 'sites/test-slug',
        ]);

        $response = $this->postJson(route('webhooks.github'), [
            'repository' => ['full_name' => 'test/repo'],
            'ref' => 'refs/heads/main',
        ]);

        $response->assertStatus(200);

        // Assert no Deployment created
        $this->assertDatabaseMissing('deployments', [
            'site_id' => $site->id,
        ]);
    }
}
