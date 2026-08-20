<?php

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('user can view and update profile via API', function () {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);
    Sanctum::actingAs($user);

    $getResponse = $this->getJson('/api/user');
    $getResponse->assertOk();
    $getResponse->assertJsonFragment(['name' => 'Original Name']);

    $updateResponse = $this->putJson('/api/user', [
        'name' => 'New Name',
        'email' => 'new@example.com',
    ]);
    $updateResponse->assertOk();
    $updateResponse->assertJsonFragment(['name' => 'New Name', 'email' => 'new@example.com']);
});

test('user can update password via API', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
        'password_set_at' => now(),
    ]);
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/user/password', [
        'current_password' => 'old-password',
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ]);

    $response->assertOk();
    expect(Hash::check('new-secure-password', $user->refresh()->password))->toBeTrue();
});

test('user can delete account via API and clean up storage', function () {
    Storage::fake('public');

    $user = User::factory()->create([
        'password' => Hash::make('mypassword'),
        'password_set_at' => now(),
    ]);
    Sanctum::actingAs($user);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'user-site-to-delete',
        'original_name' => 'Site',
        'path' => 'sites/user-site-to-delete',
    ]);
    Storage::disk('public')->put('sites/user-site-to-delete/index.html', 'content');

    $response = $this->deleteJson('/api/user', [
        'password' => 'mypassword',
    ]);

    $response->assertOk();
    $this->assertSoftDeleted('users', ['id' => $user->id]);
    Storage::disk('public')->assertMissing('sites/user-site-to-delete');
});
