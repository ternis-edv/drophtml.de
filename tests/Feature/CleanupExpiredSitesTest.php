<?php

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('sites:cleanup command removes expired non-permanent sites and storage', function () {
    Storage::fake('public');

    // Expired site
    $expired = Site::create([
        'slug' => 'expired-site',
        'path' => 'sites/expired-site',
        'original_name' => 'Expired',
        'is_permanent' => false,
        'expires_at' => now()->subDay(),
    ]);
    Storage::disk('public')->put('sites/expired-site/index.html', 'content');

    // Active future site
    $active = Site::create([
        'slug' => 'active-site',
        'path' => 'sites/active-site',
        'original_name' => 'Active',
        'is_permanent' => false,
        'expires_at' => now()->addDays(5),
    ]);
    Storage::disk('public')->put('sites/active-site/index.html', 'content');

    // Permanent site with past date
    $permanent = Site::create([
        'slug' => 'perm-site',
        'path' => 'sites/perm-site',
        'original_name' => 'Perm',
        'is_permanent' => true,
        'expires_at' => now()->subDays(10),
    ]);
    Storage::disk('public')->put('sites/perm-site/index.html', 'content');

    $this->artisan('sites:cleanup')
        ->expectsOutput('Found 1 expired sites.')
        ->assertSuccessful();

    $this->assertSoftDeleted('sites', ['id' => $expired->id]);
    $this->assertDatabaseHas('sites', ['id' => $active->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('sites', ['id' => $permanent->id, 'deleted_at' => null]);

    Storage::disk('public')->assertMissing('sites/expired-site');
    Storage::disk('public')->assertExists('sites/active-site/index.html');
    Storage::disk('public')->assertExists('sites/perm-site/index.html');
});
