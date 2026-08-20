<?php

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('site editor can create, edit, rename and delete files', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'editor-site',
        'original_name' => 'Editor Site',
        'path' => 'sites/editor-site',
        'status' => 'active',
    ]);

    Storage::disk('public')->put("{$site->path}/index.html", '<h1>Original</h1>');

    $this->actingAs($user);

    // Test component mount & load
    Livewire::test('dashboard.site-editor', ['siteId' => $site->id])
        ->assertSet('currentFile', 'index.html')
        ->assertSet('fileContent', '<h1>Original</h1>')
        // Edit file
        ->set('fileContent', '<h1>Updated Content</h1>')
        ->call('saveFile');

    expect(Storage::disk('public')->get("{$site->path}/index.html"))->toBe('<h1>Updated Content</h1>');

    // Create file
    Livewire::test('dashboard.site-editor', ['siteId' => $site->id])
        ->set('newFileName', 'about.html')
        ->call('createFile')
        ->assertSet('currentFile', 'about.html');

    expect(Storage::disk('public')->exists("{$site->path}/about.html"))->toBeTrue();

    // Rename file
    Livewire::test('dashboard.site-editor', ['siteId' => $site->id])
        ->set('oldFileName', 'about.html')
        ->set('newRenameName', 'contact.html')
        ->call('renameFile');

    expect(Storage::disk('public')->exists("{$site->path}/about.html"))->toBeFalse();
    expect(Storage::disk('public')->exists("{$site->path}/contact.html"))->toBeTrue();

    // Delete file
    Livewire::test('dashboard.site-editor', ['siteId' => $site->id])
        ->call('deleteFile', 'contact.html');

    expect(Storage::disk('public')->exists("{$site->path}/contact.html"))->toBeFalse();
});

test('site settings can update password protection, permanent status, and deployment status', function () {
    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'settings-site',
        'original_name' => 'Settings Site',
        'path' => 'sites/settings-site',
        'status' => 'active',
        'is_permanent' => false,
        'expires_at' => now()->addDays(7),
    ]);

    $this->actingAs($user);

    Livewire::test('dashboard.site-settings', ['siteId' => $site->id])
        ->set('status', 'suspended')
        ->set('isPermanent', true)
        ->set('isPasswordProtected', true)
        ->set('sitePassword', 'secretpass123')
        ->call('save');

    $site->refresh();

    expect($site->status)->toBe('suspended');
    expect($site->is_permanent)->toBeTrue();
    expect($site->expires_at)->toBeNull();
    expect($site->is_password_protected)->toBeTrue();
    expect($site->site_password)->not->toBeNull();
});

test('site domains can add, verify, and remove custom domains', function () {
    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'domains-site',
        'original_name' => 'Domains Site',
        'path' => 'sites/domains-site',
        'status' => 'active',
    ]);

    $this->actingAs($user);

    $test = Livewire::test('dashboard.site-domains', ['siteId' => $site->id])
        ->set('newDomain', 'test.mysite.com')
        ->call('addDomain');

    $site->refresh();
    expect($site->domains)->toHaveCount(1);
    $domain = $site->domains->first();
    expect($domain->domain)->toBe('test.mysite.com');

    // Remove domain
    $test->call('removeDomain', $domain->id);

    $site->refresh();
    expect($site->domains)->toHaveCount(0);
});

test('file uploader publishes sites', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    $file = UploadedFile::fake()->create('page.html', 5, 'text/html');

    Livewire::test('file-uploader')
        ->set('file', $file)
        ->assertDispatched('site-published');

    expect($user->sites()->count())->toBe(1);
});

test('landing editor deploys mock code into a real site', function () {
    Storage::fake('public');

    Livewire::test('landing-editor')
        ->call('deploy');

    expect(Site::count())->toBe(1);
    $site = Site::first();
    expect(Storage::disk('public')->exists("{$site->path}/index.html"))->toBeTrue();
});
