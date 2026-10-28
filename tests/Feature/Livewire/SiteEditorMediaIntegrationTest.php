<?php

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('media');
    Storage::fake('public');
});

test('site editor allows switching to cloud assets tab and uploading to S3/R2', function () {
    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'editor-media-site',
        'original_name' => 'Editor Media Site',
        'path' => 'sites/editor-media-site',
        'status' => 'active',
    ]);

    Storage::disk('public')->put("{$site->path}/index.html", '<h1>Editor</h1>');

    $this->actingAs($user);

    $file = UploadedFile::fake()->image('cloud-graphic.png');

    Livewire::test('dashboard.site-editor', ['siteId' => $site->id])
        ->set('explorerTab', 'assets')
        ->assertSet('explorerTab', 'assets')
        ->set('cloudUploadFile', $file);

    $this->assertDatabaseHas('media_assets', [
        'site_id' => $site->id,
        'original_name' => 'cloud-graphic.png',
        'storage_location' => 'asset_storage',
    ]);
});

test('site editor can insert cloud asset snippet into html code', function () {
    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'snippet-site',
        'original_name' => 'Snippet Site',
        'path' => 'sites/snippet-site',
        'status' => 'active',
    ]);

    Storage::disk('public')->put("{$site->path}/index.html", '<div class="hero">');

    $this->actingAs($user);

    Livewire::test('dashboard.site-editor', ['siteId' => $site->id])
        ->assertSet('currentFile', 'index.html')
        ->call('insertAssetSnippet', '<img src="https://cdn.example.com/logo.png" />')
        ->assertSet('fileContent', "<div class=\"hero\">\n<img src=\"https://cdn.example.com/logo.png\" />");
});

test('site editor can migrate binary file to cloud asset storage', function () {
    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'migrate-binary-site',
        'original_name' => 'Migrate Binary Site',
        'path' => 'sites/migrate-binary-site',
        'status' => 'active',
    ]);

    Storage::disk('public')->put("{$site->path}/index.html", '<h1>Index</h1>');
    Storage::disk('public')->put("{$site->path}/big-image.png", 'dummy image content');

    $this->actingAs($user);

    Livewire::test('dashboard.site-editor', ['siteId' => $site->id])
        ->call('selectFile', 'big-image.png')
        ->assertSet('isBinary', true)
        ->call('migrateCurrentFileToCloud');

    $this->assertDatabaseHas('media_assets', [
        'site_id' => $site->id,
        'original_name' => 'big-image.png',
        'storage_location' => 'asset_storage',
    ]);

    expect(Storage::disk('public')->exists("{$site->path}/big-image.png"))->toBeFalse();
});
