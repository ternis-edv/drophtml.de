<?php

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('user can list their sites via API', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'user-site-1',
        'original_name' => 'Site 1',
        'path' => 'sites/user-site-1',
        'status' => 'active',
    ]);

    $response = $this->getJson('/api/sites');

    $response->assertOk();
    $response->assertJsonFragment(['slug' => 'user-site-1']);
});

test('user can create a site via API with file upload', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $file = UploadedFile::fake()->create('landing.html', 10, 'text/html');

    $response = $this->postJson('/api/sites', [
        'original_name' => 'landing.html',
        'file' => $file,
        'is_password_protected' => true,
        'site_password' => 'pass123',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('sites', [
        'user_id' => $user->id,
        'original_name' => 'landing.html',
        'is_password_protected' => true,
    ]);
});

test('user can update site settings via API', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'user-site-2',
        'original_name' => 'Site 2',
        'path' => 'sites/user-site-2',
        'status' => 'active',
    ]);

    $response = $this->putJson("/api/sites/{$site->id}", [
        'status' => 'suspended',
        'is_permanent' => true,
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('sites', [
        'id' => $site->id,
        'status' => 'suspended',
        'is_permanent' => true,
    ]);
});

test('user can delete a site via API', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'to-delete',
        'original_name' => 'To Delete',
        'path' => 'sites/to-delete',
        'status' => 'active',
    ]);

    Storage::disk('public')->put('sites/to-delete/index.html', 'content');

    $response = $this->deleteJson("/api/sites/{$site->id}");

    $response->assertOk();
    $this->assertSoftDeleted('sites', ['id' => $site->id]);
    Storage::disk('public')->assertMissing('sites/to-delete');
});
