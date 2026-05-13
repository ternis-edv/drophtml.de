<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use App\Jobs\DeployGithubSiteJob;
use Tests\TestCase;

class WebhookBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_github_webhook_performance()
    {
        Queue::fake();

        // Setup user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'github_token' => 'fake_token'
        ]);

        // Setup sites
        for ($i = 0; $i < 50; $i++) {
            Site::create([
                'user_id' => $user->id,
                'slug' => 'test-site-' . $i,
                'path' => 'test-path-' . $i,
                'github_repo_full_name' => 'test/repo',
                'github_branch' => 'main',
                'auto_deploy' => true,
            ]);
        }

        // Mock HTTP response
        Http::fake([
            'https://api.github.com/repos/test/repo/zipball/main' => Http::response('fake_zip_content', 200),
        ]);

        $payload = [
            'repository' => [
                'full_name' => 'test/repo'
            ],
            'ref' => 'refs/heads/main',
            'head_commit' => [
                'message' => 'Test commit'
            ]
        ];

        $start = microtime(true);

        $response = $this->postJson('/webhooks/github', $payload);

        $end = microtime(true);

        $response->assertStatus(200);

        Queue::assertPushed(DeployGithubSiteJob::class, 50);

        $time = $end - $start;
        echo "\nWebhook endpoint took: " . number_format($time * 1000, 2) . "ms\n";
    }
}
