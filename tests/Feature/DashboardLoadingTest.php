<?php

use App\Models\User;
use App\Services\StorageUsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the dashboard shell before calculating storage or loading the overview', function () {
    $this->mock(StorageUsageService::class)->shouldNotReceive('getUserStorageStats');

    $this->actingAs(User::factory()->create())->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-dashboard-preview', false)
        ->assertSee('Loading your dashboard')
        ->assertSee('Reload dashboard')
        ->assertDontSee('Total Page Views');
});

it('offers useful recovery paths on missing pages', function () {
    $this->get('/a-page-that-does-not-exist')
        ->assertNotFound()
        ->assertSee('PAGE NOT FOUND')
        ->assertSee('Take me home')
        ->assertSee('Read the docs')
        ->assertSee('noindex, nofollow', false);
});

it('offers signed-in users a way back to their sites from a missing page', function () {
    $this->actingAs(User::factory()->create())->get('/a-page-that-does-not-exist')
        ->assertNotFound()
        ->assertSee('Back to your sites');
});
