<?php

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('a password protected site prompts for password', function () {
    Storage::fake('public');

    $slug = 'secret-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/index.html", '<h1>Secret Content</h1>');

    $site = Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'secret.html',
        'is_password_protected' => true,
        'site_password' => Hash::make('secret123'),
        'status' => 'active',
    ]);

    $response = $this->get("/s/{$slug}");

    $response->assertStatus(401);
    $response->assertSee('Protected Site');
    $response->assertDontSee('Secret Content');
});

test('a user cannot unlock with incorrect password', function () {
    Storage::fake('public');

    $slug = 'secret-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/index.html", '<h1>Secret Content</h1>');

    Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'secret.html',
        'is_password_protected' => true,
        'site_password' => Hash::make('secret123'),
        'status' => 'active',
    ]);

    $response = $this->post("/s/{$slug}/_unlock", [
        'password' => 'wrongpassword',
    ]);

    $response->assertSessionHas('error');
    $this->assertNull(session('site_unlocked_secret-site'));
});

test('a user can unlock with correct password and view site', function () {
    Storage::fake('public');

    $slug = 'secret-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/index.html", '<h1>Secret Content</h1>');

    $site = Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'secret.html',
        'is_password_protected' => true,
        'site_password' => Hash::make('secret123'),
        'status' => 'active',
    ]);

    $response = $this->post("/s/{$slug}/_unlock", [
        'password' => 'secret123',
    ]);

    $response->assertRedirect(url("/s/{$slug}"));
    expect(session("site_unlocked_{$site->id}"))->toBeTrue();

    // Now visit the site with the session unlocked
    $siteResponse = $this->get("/s/{$slug}");
    $siteResponse->assertOk();
    $siteResponse->assertSee('Secret Content');
});
