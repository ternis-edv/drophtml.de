<?php

use App\Models\ActivityLog;
use App\Models\Deployment;
use App\Models\Domain;
use App\Models\MediaAsset;
use App\Models\Site;
use App\Models\SiteRedirect;
use App\Models\User;
use App\Models\View;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('data export page requires authentication', function () {
    $this->get(route('settings.data-export'))
        ->assertRedirect(route('login'));
});

test('data export page requires a verified email address', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('settings.data-export'))
        ->assertRedirect(route('verification.notice'));
});

test('data export page is displayed for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.data-export'))
        ->assertOk()
        ->assertSee('Data export')
        ->assertSee('Download data export (JSON)');
});

test('data export downloads a json file with the users personal data', function () {
    $user = User::factory()->create([
        'name' => 'Export Tester',
        'email' => 'export@example.com',
    ]);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'export-test-site',
        'path' => 'sites/export-test-site',
        'original_name' => 'Export Test Site',
        'entry_path' => 'index.html',
    ]);

    Domain::create([
        'site_id' => $site->id,
        'domain' => 'example.test',
        'is_custom' => true,
        'verified_at' => now(),
    ]);

    Deployment::create([
        'site_id' => $site->id,
        'status' => 'success',
        'commit_hash' => 'abc1234',
        'commit_message' => 'Initial deploy',
        'source' => 'manual',
    ]);

    SiteRedirect::create([
        'site_id' => $site->id,
        'from_path' => '/old',
        'to_url' => '/new',
        'status_code' => 301,
        'is_active' => true,
    ]);

    MediaAsset::create([
        'user_id' => $user->id,
        'site_id' => $site->id,
        'original_name' => 'photo.png',
        'path' => 'media/photo.png',
        'disk' => 'media',
        'storage_driver' => 'asset_storage',
        'file_type' => 'image',
        'mime_type' => 'image/png',
        'size' => 1234,
    ]);

    ActivityLog::create([
        'user_id' => $user->id,
        'site_id' => $site->id,
        'action' => 'site.created',
        'description' => 'Site created',
        'ip_address' => '203.0.113.5',
    ]);

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.data-export')
        ->call('download');

    $component->assertFileDownloaded(
        'drophtml-dsgvo-export-'.now()->format('Y-m-d').'.json',
        null,
        'application/json'
    );

    $data = json_decode(base64_decode(data_get($component->effects, 'download.content')), true);

    expect($data['account']['name'])->toBe('Export Tester')
        ->and($data['account']['email'])->toBe('export@example.com')
        ->and($data['sites'])->toHaveCount(1)
        ->and($data['sites'][0]['slug'])->toBe('export-test-site')
        ->and($data['sites'][0]['domains'][0]['domain'])->toBe('example.test')
        ->and($data['sites'][0]['deployments'][0]['commit_hash'])->toBe('abc1234')
        ->and($data['sites'][0]['redirects'][0]['from_path'])->toBe('/old')
        ->and($data['media_assets'][0]['original_name'])->toBe('photo.png')
        ->and($data['activity_logs'][0]['action'])->toBe('site.created')
        ->and($data['activity_logs'][0]['ip_address'])->toBe('203.0.113.5');
});

test('data export excludes secrets and third party visitor data', function () {
    $user = User::factory()->withTwoFactor()->create([
        'github_token' => 'gh-secret-token',
        'github_refresh_token' => 'gh-secret-refresh',
    ]);

    $site = Site::create([
        'user_id' => $user->id,
        'slug' => 'secret-site',
        'path' => 'sites/secret-site',
        'site_password' => 'hunter2-site-password',
        'webhook_secret' => 'whsec-supersecret',
    ]);

    $plainTextToken = $user->createToken('CI')->plainTextToken;

    View::create([
        'site_id' => $site->id,
        'ip_address' => '198.51.100.99',
        'user_agent' => 'SomeVisitorBrowser/1.0',
    ]);

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.data-export')
        ->call('download');

    $raw = base64_decode(data_get($component->effects, 'download.content'));
    $data = json_decode($raw, true);

    // Secrets must never appear in the export.
    expect($raw)->not->toContain($user->password)
        ->and($raw)->not->toContain(Hash::make('password'))
        ->and($raw)->not->toContain('gh-secret-token')
        ->and($raw)->not->toContain('gh-secret-refresh')
        ->and($raw)->not->toContain($plainTextToken)
        ->and($raw)->not->toContain('hunter2-site-password')
        ->and($raw)->not->toContain('whsec-supersecret')
        ->and($data['sites'][0])->not->toHaveKey('site_password')
        ->and($data['sites'][0])->not->toHaveKey('webhook_secret')
        ->and($data['account'])->not->toHaveKey('password')
        ->and($data['account']['two_factor_authentication']['enabled'])->toBeTrue();

    // API token metadata is exported without the token value.
    expect($data['api_tokens'][0]['name'])->toBe('CI')
        ->and($data['api_tokens'][0])->not->toHaveKey('token');

    // Third-party visitor logs must not be exported.
    expect($raw)->not->toContain('198.51.100.99')
        ->and($raw)->not->toContain('SomeVisitorBrowser/1.0')
        ->and($data)->not->toHaveKey('views');
});

test('data export only contains data of the authenticated user', function () {
    $otherUser = User::factory()->create(['email' => 'other@example.com']);

    Site::create([
        'user_id' => $otherUser->id,
        'slug' => 'other-users-site',
        'path' => 'sites/other-users-site',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.data-export')
        ->call('download');

    $raw = base64_decode(data_get($component->effects, 'download.content'));
    $data = json_decode($raw, true);

    expect($data['account']['email'])->not->toBe('other@example.com')
        ->and($data['sites'])->toHaveCount(0)
        ->and($raw)->not->toContain('other@example.com')
        ->and($raw)->not->toContain('other-users-site');
});
