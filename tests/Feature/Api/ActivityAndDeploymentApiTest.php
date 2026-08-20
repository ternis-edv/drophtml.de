<?php

use App\Models\ActivityLog;
use App\Models\Deployment;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('user can list and view their activity logs via API', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $log = ActivityLog::create([
        'user_id' => $user->id,
        'action' => 'site_created',
        'description' => 'Created test site',
        'ip_address' => '127.0.0.1',
    ]);

    $response = $this->getJson('/api/activity-logs');
    $response->assertOk();
    $response->assertJsonFragment(['action' => 'site_created']);

    $showResponse = $this->getJson("/api/activity-logs/{$log->id}");
    $showResponse->assertOk();
    $showResponse->assertJsonFragment(['description' => 'Created test site']);
});

test('user can list and view site deployments via API', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'dep-site',
        'original_name' => 'Dep Site',
        'path' => 'sites/dep-site',
        'status' => 'active',
    ]);

    $deployment = Deployment::create([
        'site_id' => $site->id,
        'status' => 'success',
        'source' => 'github_webhook',
        'commit_hash' => 'c1d2e3f',
        'commit_message' => 'Initial deployment',
    ]);

    $response = $this->getJson("/api/sites/{$site->id}/deployments");
    $response->assertOk();
    $response->assertJsonFragment(['commit_hash' => 'c1d2e3f']);

    $showResponse = $this->getJson("/api/sites/{$site->id}/deployments/{$deployment->id}");
    $showResponse->assertOk();
    $showResponse->assertJsonFragment(['commit_message' => 'Initial deployment']);
});
