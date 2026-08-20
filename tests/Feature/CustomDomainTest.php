<?php

use App\Models\Domain;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('a site can be served via custom domain', function () {
    Storage::fake('public');

    $slug = 'custom-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/index.html", '<h1>Custom Domain Landing</h1>');

    $site = Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'custom.html',
        'status' => 'active',
    ]);

    $domain = Domain::create([
        'site_id' => $site->id,
        'domain' => 'mybrand.example.com',
        'is_custom' => true,
        'verified_at' => now(),
    ]);

    $response = $this->get('http://mybrand.example.com/');

    $response->assertOk();
    $response->assertSee('<h1>Custom Domain Landing</h1>', false);
});
