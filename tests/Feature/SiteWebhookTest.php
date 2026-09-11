<?php

use App\Jobs\DispatchSiteWebhookJob;
use App\Models\Site;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\SiteDeploymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('dispatch site webhook job sends HTTP POST with HMAC signature', function () {
    Http::fake([
        'https://api.example.com/webhook' => Http::response(['received' => true], 200),
    ]);

    $user = User::factory()->create();
    $site = Site::create([
        'slug' => 'webhook-site',
        'path' => 'sites/webhook-site',
        'original_name' => 'site.zip',
        'status' => 'active',
        'user_id' => $user->id,
        'webhook_url' => 'https://api.example.com/webhook',
        'webhook_secret' => 'supersecret123',
    ]);

    $job = new DispatchSiteWebhookJob($site, 'site.deployed', [
        'commit_message' => 'New release v1.0.0',
    ]);

    $activityLogService = app(ActivityLogService::class);
    $job->handle($activityLogService);

    Http::assertSent(function ($request) {
        if ($request->url() !== 'https://api.example.com/webhook') {
            return false;
        }

        if ($request->header('X-DropHTML-Event')[0] !== 'site.deployed') {
            return false;
        }

        $sig = $request->header('X-DropHTML-Signature')[0] ?? '';
        if (! str_starts_with($sig, 'sha256=')) {
            return false;
        }

        $expectedSig = 'sha256='.hash_hmac('sha256', $request->body(), 'supersecret123');
        if ($sig !== $expectedSig) {
            return false;
        }

        $data = $request->data();

        return $data['event'] === 'site.deployed'
            && $data['site']['slug'] === 'webhook-site'
            && $data['data']['commit_message'] === 'New release v1.0.0';
    });
});

test('deployToExistingSite dispatches webhook job when webhook_url is set', function () {
    Storage::fake('public');
    Queue::fake();

    $user = User::factory()->create();
    $slug = 'webhook-deploy-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/index.html", '<h1>Initial</h1>');

    $site = Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'site.zip',
        'status' => 'active',
        'user_id' => $user->id,
        'webhook_url' => 'https://example.com/webhook',
    ]);

    $file = UploadedFile::fake()->createWithContent('index.html', '<h1>Updated</h1>');

    $deploymentService = app(SiteDeploymentService::class);
    $deploymentService->deployToExistingSite($site, $file, $user, 'Deploy v2', 'upload');

    Queue::assertPushed(DispatchSiteWebhookJob::class, function ($job) use ($site) {
        return $job->site->id === $site->id && $job->event === 'site.deployed';
    });
});

test('dispatch site webhook job ignores empty or invalid URLs', function () {
    Http::fake();

    $user = User::factory()->create();
    $site = Site::create([
        'slug' => 'invalid-webhook-site',
        'path' => 'sites/invalid-webhook-site',
        'status' => 'active',
        'user_id' => $user->id,
        'webhook_url' => 'not-a-valid-url',
    ]);

    $job = new DispatchSiteWebhookJob($site, 'site.deployed');
    $activityLogService = app(ActivityLogService::class);
    $job->handle($activityLogService);

    Http::assertNothingSent();
});
