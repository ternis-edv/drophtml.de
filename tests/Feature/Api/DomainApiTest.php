<?php

use App\Models\Domain;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('user can manage domains via API', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'domain-test-site',
        'original_name' => 'Domain Test',
        'path' => 'sites/domain-test',
        'status' => 'active',
    ]);

    // Create domain
    $response = $this->postJson("/api/sites/{$site->id}/domains", [
        'domain' => 'https://api-domain.example.com/',
    ]);

    $response->assertCreated();
    $domainId = $response->json('data.id');

    $this->assertDatabaseHas('domains', [
        'site_id' => $site->id,
        'domain' => 'api-domain.example.com',
    ]);

    // List domains
    $listResponse = $this->getJson("/api/sites/{$site->id}/domains");
    $listResponse->assertOk();
    $listResponse->assertJsonFragment(['domain' => 'api-domain.example.com']);

    // Delete domain
    $deleteResponse = $this->deleteJson("/api/sites/{$site->id}/domains/{$domainId}");
    $deleteResponse->assertOk();
    $this->assertSoftDeleted('domains', ['id' => $domainId]);
});
