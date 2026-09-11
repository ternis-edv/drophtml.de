<?php

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('site honors 301 and 302 redirects defined in _redirects file', function () {
    Storage::fake('public');

    $slug = 'redirect-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/index.html", '<h1>Home</h1>');
    Storage::disk('public')->put("{$path}/new-target.html", '<h1>New Target</h1>');
    Storage::disk('public')->put("{$path}/_redirects", implode("\n", [
        '/old-page /new-target 301',
        '/temp-page /new-target 302',
        '/external https://example.com/docs 302',
    ]));

    $site = Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'site.zip',
        'status' => 'active',
    ]);

    // 301 redirect
    $response = $this->get("/s/{$slug}/old-page");
    $response->assertRedirect("http://localhost:8000/s/{$slug}/new-target");
    $response->assertStatus(301);

    // 302 redirect
    $response2 = $this->get("/s/{$slug}/temp-page");
    $response2->assertRedirect("http://localhost:8000/s/{$slug}/new-target");
    $response2->assertStatus(302);

    // External redirect
    $response3 = $this->get("/s/{$slug}/external");
    $response3->assertRedirect('https://example.com/docs');
    $response3->assertStatus(302);
});

test('site supports wildcard splat redirects in _redirects file', function () {
    Storage::fake('public');

    $slug = 'splat-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/index.html", '<h1>Home</h1>');
    Storage::disk('public')->put("{$path}/_redirects", "/news/* /articles/:splat 301\n");

    Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'site.zip',
        'status' => 'active',
    ]);

    $response = $this->get("/s/{$slug}/news/2026/09/update");
    $response->assertRedirect("http://localhost:8000/s/{$slug}/articles/2026/09/update");
    $response->assertStatus(301);
});

test('site supports 200 rewrites for SPA routing via _redirects', function () {
    Storage::fake('public');

    $slug = 'spa-rewrite-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/index.html", '<h1>SPA Application App</h1>');
    Storage::disk('public')->put("{$path}/assets/style.css", 'body { margin: 0; }');
    Storage::disk('public')->put("{$path}/_redirects", "/* /index.html 200\n");

    Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'site.zip',
        'status' => 'active',
    ]);

    // Deep client-side route rewrites to index.html with 200 OK
    $response = $this->get("/s/{$slug}/dashboard/settings/profile");
    $response->assertOk();
    $response->assertSee('<h1>SPA Application App</h1>', false);

    // Existing static asset is NOT shadowed by 200 rewrite
    $assetResponse = $this->get("/s/{$slug}/assets/style.css");
    $assetResponse->assertOk();
    $assetResponse->assertSee('body { margin: 0; }', false);
});

test('site applies custom HTTP headers defined in _headers file', function () {
    Storage::fake('public');

    $slug = 'headers-site';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/index.html", '<h1>Headers Test</h1>');
    Storage::disk('public')->put("{$path}/api/data.json", '{"ok":true}');
    Storage::disk('public')->put("{$path}/_headers", implode("\n", [
        '/*',
        '  X-Custom-Platform: DropHTML-Edge',
        '  X-Frame-Options: SAMEORIGIN',
        '/api/*',
        '  Access-Control-Allow-Origin: *',
        '  Access-Control-Allow-Methods: GET, POST, OPTIONS',
    ]));

    Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'site.zip',
        'status' => 'active',
    ]);

    // Root page matches /*
    $response = $this->get("/s/{$slug}");
    $response->assertOk();
    $response->assertHeader('X-Custom-Platform', 'DropHTML-Edge');

    // /api/data.json matches both /* and /api/*
    $apiResponse = $this->get("/s/{$slug}/api/data.json");
    $apiResponse->assertOk();
    $apiResponse->assertHeader('X-Custom-Platform', 'DropHTML-Edge');
    $apiResponse->assertHeader('Access-Control-Allow-Origin', '*');
    $apiResponse->assertHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
});

test('access to _redirects and _headers files is blocked with 404', function () {
    Storage::fake('public');

    $slug = 'protected-configs';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/index.html", '<h1>Secret</h1>');
    Storage::disk('public')->put("{$path}/_redirects", "/old /new 301\n");
    Storage::disk('public')->put("{$path}/_headers", "/*\n  X-Secret: true\n");

    Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'site.zip',
        'status' => 'active',
    ]);

    $this->get("/s/{$slug}/_redirects")->assertNotFound();
    $this->get("/s/{$slug}/_headers")->assertNotFound();
});

test('site supports 1-click SPA mode fallback for virtual routes', function () {
    Storage::fake('public');

    $slug = 'oneclick-spa';
    $path = "sites/{$slug}";

    Storage::disk('public')->put("{$path}/index.html", '<h1>React Single Page App</h1>');
    Storage::disk('public')->put("{$path}/app.js", 'console.log("running");');

    // Site with is_spa = true
    Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'site.zip',
        'status' => 'active',
        'is_spa' => true,
    ]);

    // Virtual client-side route serves index.html with 200 OK
    $response = $this->get("/s/{$slug}/explore/trending");
    $response->assertOk();
    $response->assertSee('<h1>React Single Page App</h1>', false);

    // Existing asset is served normally
    $assetResponse = $this->get("/s/{$slug}/app.js");
    $assetResponse->assertOk();
    $assetResponse->assertSee('console.log("running");', false);

    // Missing asset with file extension returns 404 instead of index.html
    $missingAssetResponse = $this->get("/s/{$slug}/missing-image.png");
    $missingAssetResponse->assertNotFound();
});
