<?php

use App\Models\MediaAsset;
use App\Models\User;
use App\Services\MediaStorageService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('media');
    Storage::fake('public');
});

test('user can visit static media hosting dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard.media'))
        ->assertOk()
        ->assertSee('Static Media Hosting');
});

test('user can upload media via Livewire media manager component', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $file = UploadedFile::fake()->image('nature.webp');

    Livewire::test('dashboard.media-manager')
        ->set('storageDestination', 'asset_storage')
        ->set('uploadFiles', [$file])
        ->call('uploadMedia');

    $this->assertDatabaseHas('media_assets', [
        'user_id' => $user->id,
        'original_name' => 'nature.webp',
        'storage_location' => 'asset_storage',
    ]);
});

test('user can delete media via Livewire media manager', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $file = UploadedFile::fake()->create('contract.pdf', 300, 'application/pdf');
    $asset = app(MediaStorageService::class)->store($file, $user, null, 'asset_storage');

    Livewire::test('dashboard.media-manager')
        ->call('deleteAsset', $asset->id);

    $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
});

test('user can filter media assets by type and storage driver', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    MediaAsset::create([
        'user_id' => $user->id,
        'original_name' => 'logo.svg',
        'path' => 'media/logo.svg',
        'disk' => 'media',
        'storage_driver' => 'asset_storage',
        'file_type' => 'image',
        'size' => 500,
    ]);

    MediaAsset::create([
        'user_id' => $user->id,
        'original_name' => 'podcast.mp3',
        'path' => 'media/podcast.mp3',
        'disk' => 'media',
        'storage_driver' => 'asset_storage',
        'file_type' => 'audio',
        'size' => 5000,
    ]);

    Livewire::test('dashboard.media-manager')
        ->set('typeFilter', 'image')
        ->assertSee('logo.svg')
        ->assertDontSee('podcast.mp3');
});

it('shows readable embed examples and public URLs in the asset inspector', function ($type, $mime, $extension, $tag) {
    $user = User::factory()->create();
    $asset = MediaAsset::create([
        'user_id' => $user->id,
        'original_name' => 'creative "draft" `${sample}`.'.$extension,
        'path' => 'media/sample.'.$extension,
        'public_url' => 'https://cdn.example.test/sample.'.$extension.'?version=1&size=large',
        'disk' => 'media',
        'storage_driver' => 'asset_storage',
        'file_type' => $type,
        'mime_type' => $mime,
        'size' => 4000,
    ]);

    Livewire::actingAs($user)->test('dashboard.media-manager')
        ->call('showPreview', $asset->id)
        ->assertSet('previewAssetId', $asset->id)
        ->assertSee('Public URL')
        ->assertSee('HTML embed')
        ->assertSee('Markdown')
        ->assertSee($asset->resolved_url)
        ->assertSee($asset->html_tag)
        ->call('closePreview')
        ->assertSet('previewAssetId', null)
        ->assertSet('previewAsset', null);

    expect($asset->html_tag)->toContain($tag)->not->toContain('class="w-full');
})->with([
    ['image', 'image/webp', 'webp', '<img '],
    ['video', 'video/mp4', 'mp4', '<video controls'],
    ['audio', 'audio/mpeg', 'mp3', '<audio controls'],
    ['document', 'application/pdf', 'pdf', '<a href='],
]);

it('resets pagination when searching or filtering the media library', function () {
    $user = User::factory()->create();
    foreach (range(1, 26) as $index) {
        MediaAsset::create([
            'user_id' => $user->id,
            'original_name' => "photo-{$index}.png",
            'path' => "media/photo-{$index}.png",
            'disk' => 'media',
            'storage_driver' => 'asset_storage',
            'file_type' => 'image',
            'size' => 500,
        ]);
    }

    Livewire::actingAs($user)->test('dashboard.media-manager')
        ->call('setPage', 2)
        ->assertSet('paginators.page', 2)
        ->set('search', 'photo-26')
        ->assertSet('paginators.page', 1)
        ->assertSee('photo-26.png')
        ->set('search', '')
        ->call('setPage', 2)
        ->call('setTypeFilter', 'image')
        ->assertSet('paginators.page', 1)
        ->call('setPage', 2)
        ->set('driverFilter', 'code')
        ->assertSet('paginators.page', 1)
        ->assertSee('No matching assets');
});

it('does not expose another users asset through the inspector', function () {
    $owner = User::factory()->create();
    $asset = MediaAsset::create([
        'user_id' => $owner->id, 'original_name' => 'private.png', 'path' => 'media/private.png',
        'disk' => 'media', 'storage_driver' => 'asset_storage', 'file_type' => 'image', 'size' => 500,
    ]);
    $component = Livewire::actingAs(User::factory()->create())->test('dashboard.media-manager');
    expect(fn () => $component->call('showPreview', $asset->id))
        ->toThrow(ModelNotFoundException::class);
});

it('keeps failed files available for retry instead of closing the upload form', function () {
    Livewire::actingAs(User::factory()->create())->test('dashboard.media-manager')
        ->set('uploadFiles', [UploadedFile::fake()->create('script.php', 1, 'text/x-php')])
        ->call('uploadMedia')
        ->assertHasNoErrors()
        ->assertCount('uploadFiles', 1);
    $this->assertDatabaseCount('media_assets', 0);
});
