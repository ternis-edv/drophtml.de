<?php

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('a site can be served via its slug', function () {
    Storage::fake('public');

    $slug = 'test-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/index.html", '<!DOCTYPE html><html><head><title>Test</title></head><body><h1>Hello World</h1></body></html>');

    $site = Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'index.html',
        'status' => 'active',
    ]);

    $response = $this->get("/s/{$slug}");

    $response->assertStatus(200);
    $response->assertSee('<h1>Hello World</h1>', false);
    $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    $response->assertSee('<base href="http://localhost:8000/s/test-site/">', false);

    $site->refresh();
    expect($site->views)->toBe(1);
    expect($site->siteViews()->count())->toBe(1);
    expect($site->siteViews()->first()->is_quiet)->toBeFalse();
});

test('sub-assets can be served with correct mime type without incrementing view count', function () {
    Storage::fake('public');

    $slug = 'test-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/css/style.css", 'body { color: red; }');

    $site = Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'site.zip',
        'status' => 'active',
    ]);

    $response = $this->get("/s/{$slug}/css/style.css");

    $response->assertStatus(200);
    $response->assertSee('body { color: red; }', false);
    $response->assertHeader('Content-Type', 'text/css; charset=UTF-8');

    $site->refresh();
    expect($site->views)->toBe(0);
    expect($site->siteViews()->count())->toBe(1);
    expect($site->siteViews()->first()->is_quiet)->toBeTrue();
});

test('directory request serves index.html within directory', function () {
    Storage::fake('public');

    $slug = 'nested-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/docs/index.html", '<h1>Docs Index</h1>');

    $site = Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'site.zip',
        'status' => 'active',
    ]);

    $response = $this->get("/s/{$slug}/docs");

    $response->assertOk();
    $response->assertSee('<h1>Docs Index</h1>', false);
});

test('suspended site returns 403', function () {
    Storage::fake('public');

    $slug = 'suspended-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/index.html", '<h1>Suspended</h1>');

    Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'site.html',
        'status' => 'suspended',
    ]);

    $response = $this->get("/s/{$slug}");
    $response->assertStatus(403);
});

test('non existent site returns 404', function () {
    $response = $this->get('/s/non-existent-slug');
    $response->assertNotFound();
});
