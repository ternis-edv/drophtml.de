<?php

use App\Models\Site;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a site can be served via its slug', function () {
    Storage::fake('public');
    
    $slug = 'test-site';
    $path = "sites/{$slug}";
    
    Storage::disk('public')->put("{$path}/index.html", '<h1>Hello World</h1>');
    
    Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'index.html',
    ]);
    
    $response = $this->get("/s/{$slug}");
    
    $response->assertStatus(200);
    $response->assertSee('<h1>Hello World</h1>', false);
    $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
});

test('sub-assets can be served', function () {
    Storage::fake('public');
    
    $slug = 'test-site';
    $path = "sites/{$slug}";
    
    Storage::disk('public')->put("{$path}/css/style.css", 'body { color: red; }');
    
    Site::create([
        'slug' => $slug,
        'path' => $path,
        'original_name' => 'site.zip',
    ]);
    
    $response = $this->get("/s/{$slug}/css/style.css");
    
    $response->assertStatus(200);
    $response->assertSee('body { color: red; }', false);
    $response->assertHeader('Content-Type', 'text/css; charset=UTF-8');
});
