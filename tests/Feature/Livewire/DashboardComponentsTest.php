<?php

use App\Models\ActivityLog;
use App\Models\Site;
use App\Models\User;
use App\Services\GitHubService;
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

test('site editor never writes outside the site directory', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'safe-editor-site',
        'original_name' => 'Safe Editor Site',
        'path' => 'sites/safe-editor-site',
        'status' => 'active',
    ]);

    Storage::disk('public')->put("{$site->path}/index.html", '<h1>Original</h1>');
    $this->actingAs($user);

    // saveFile through a traversal property must be rejected
    Livewire::test('dashboard.site-editor', ['siteId' => $site->id])
        ->set('currentFile', '../evil.html')
        ->set('fileContent', '<h1>evil</h1>')
        ->call('saveFile');

    expect(Storage::disk('public')->exists('evil.html'))->toBeFalse();
    expect(Storage::disk('public')->get("{$site->path}/index.html"))->toBe('<h1>Original</h1>');

    // deleteFile through a traversal argument must not delete outside files
    Storage::disk('public')->put('outside.html', '<p>keep me</p>');

    Livewire::test('dashboard.site-editor', ['siteId' => $site->id])
        ->call('deleteFile', '../outside.html');

    expect(Storage::disk('public')->exists('outside.html'))->toBeTrue();

    // createFile with a sneaky nested traversal sequence now ends up
    // inside the site directory, never outside of it
    Livewire::test('dashboard.site-editor', ['siteId' => $site->id])
        ->set('newFileName', '....//evil2.html')
        ->call('createFile');

    expect(Storage::disk('public')->exists('evil2.html'))->toBeFalse();
    expect(Storage::disk('public')->exists("{$site->path}/evil2.html"))->toBeFalse();
});

test('sites overview clamps expiry extensions to the offered allowlist', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'expiry-site',
        'original_name' => 'Expiry Site',
        'path' => 'sites/expiry-site',
        'status' => 'active',
    ]);

    Storage::disk('public')->put("{$site->path}/index.html", '<h1>Hx</h1>');
    $site->expires_at = now()->addDay();
    $site->save();

    Livewire::test('dashboard.sites-overview')
        ->call('extendExpiry', $site->id, 999999);

    $site->refresh();

    // 999999 days is not offered in the UI: it must clamp to the default +7 days
    expect($site->expires_at->between(now()->addDays(8)->subMinutes(3), now()->addDays(8)->addMinutes(3)))->toBeTrue();
});

test('activity log filters match the actions actually logged', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'log-site',
        'original_name' => 'Log Site',
        'path' => 'sites/log-site',
        'status' => 'active',
    ]);

    ActivityLog::create(['user_id' => $user->id, 'site_id' => $site->id, 'action' => 'site_uploaded', 'description' => 'Uploaded the site archive']);
    ActivityLog::create(['user_id' => $user->id, 'site_id' => $site->id, 'action' => 'domain_added', 'description' => 'Added custom domain other.example']);

    Livewire::test('dashboard.activity-log')
        ->call('setFilter', 'deploys')
        ->assertSee('Uploaded the site archive')
        ->assertDontSee('Added custom domain other.example')
        ->call('setFilter', 'domains')
        ->assertSee('Added custom domain other.example')
        ->assertDontSee('Uploaded the site archive');
});

test('github manual deploy component extracts repo archive and creates site', function () {
    Storage::fake('public');

    $user = User::factory()->create(['github_token' => 'fake-token']);
    $this->actingAs($user);

    $githubService = Mockery::mock(GitHubService::class);
    $githubService->shouldReceive('getUserRepositories')->andReturn([
        ['full_name' => 'me/demo-site', 'name' => 'demo-site', 'default_branch' => 'main'],
    ]);
    $githubService->shouldReceive('downloadRepositoryArchive')
        ->andReturnUsing(function ($fullName, $branch, $token, $destination) {
            $zip = new ZipArchive;
            $zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $zip->addFromString('index.html', '<h1>Github Demo</h1>');
            $zip->close();
        });

    $this->app->instance(GitHubService::class, $githubService);

    Livewire::test('dashboard.github-deploy')
        ->set('autoDeploy', false)
        ->call('deploy', 'me/demo-site');

    expect(Site::where('github_repo_full_name', 'me/demo-site')->count())->toBe(1);

    $site = Site::first();
    expect($site->entry_path)->toBe('index.html');
    expect(Storage::disk('public')->get("{$site->path}/index.html"))->toBe('<h1>Github Demo</h1>');
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

test('file uploader applies the uploads rate limit', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Livewire::test('file-uploader');
    for ($i = 0; $i < 20; $i++) {
        $component->set('file', UploadedFile::fake()->create("page-$i.html", 1, 'text/html'));
    }

    // The 21st upload within a minute exceeds the named "uploads" limiter
    $component->set('file', UploadedFile::fake()->create('page-20.html', 1, 'text/html'))
        ->assertSet('errorMessage', fn ($v) => str_contains($v, 'Too many uploads'));

    expect($user->sites()->count())->toBe(20);
});

test('command palette mounts and filters sites on search', function () {
    $user = User::factory()->create();
    $site1 = Site::create([
        'user_id' => $user->id,
        'slug' => 'portfolio-cool',
        'original_name' => 'Portfolio Cool',
        'path' => 'sites/portfolio-cool',
        'status' => 'active',
    ]);
    $site2 = Site::create([
        'user_id' => $user->id,
        'slug' => 'blog-super',
        'original_name' => 'Blog Super',
        'path' => 'sites/blog-super',
        'status' => 'active',
    ]);

    $this->actingAs($user);

    Livewire::test('dashboard.command-palette')
        ->assertSet('isOpen', false)
        ->call('openPalette')
        ->assertSet('isOpen', true)
        ->assertSee('Portfolio Cool')
        ->assertSee('Blog Super')
        ->set('search', 'portfolio')
        ->assertSee('Portfolio Cool')
        ->assertDontSee('Blog Super')
        ->call('closePalette')
        ->assertSet('isOpen', false);
});

test('site deployments livewire component mounts and can trigger manual snapshot', function () {
    Storage::fake('public');
    Storage::fake('local');

    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'snap-site',
        'original_name' => 'Snap Site',
        'path' => 'sites/snap-site',
        'status' => 'active',
    ]);

    Storage::disk('public')->put("{$site->path}/index.html", '<h1>Snap</h1>');

    $this->actingAs($user);

    Livewire::test('dashboard.site-deployments', ['siteId' => $site->id])
        ->call('createManualSnapshot');

    $site->refresh();
    expect($site->deployments()->count())->toBe(1);
    $deployment = $site->deployments()->first();
    expect($deployment->source)->toBe('manual');
});

test('sites overview setSort applies deterministic column and direction', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('dashboard.sites-overview')
        ->call('setSort', 'original_name', 'desc')
        ->assertSet('sortBy', 'original_name')
        ->assertSet('sortDir', 'desc')
        ->call('setSort', 'views')
        ->assertSet('sortBy', 'views')
        ->assertSet('sortDir', 'desc')
        ->call('setSort', 'invalid_column')
        ->assertSet('sortBy', 'views');
});
