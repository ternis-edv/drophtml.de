<?php

use App\Http\Controllers\SiteController;
use App\Models\Domain;
use App\Models\Site;
use App\Models\User;
use App\Services\SiteDeploymentService;
use App\Services\SiteServingService;
use App\Services\UserDataExportService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->owner = User::factory()->create();
    $this->site = Site::create([
        'user_id' => $this->owner->id, 'slug' => 'error-demo', 'original_name' => 'Demo website',
        'path' => 'sites/error-demo', 'status' => 'active',
    ]);
    Storage::disk('public')->put('sites/error-demo/index.html', '<h1>Site home</h1>');
});

it('distinguishes app and hosted-site 404s and never exposes query parameters', function () {
    $this->get('/not-a-drophtml-route?token=private-value')->assertNotFound()
        ->assertSee('Find your next destination')->assertSee('YOU WERE LOOKING FOR')->assertDontSee('private-value');
    $this->get('/s/error-demo/missing?token=private-value')->assertNotFound()
        ->assertSee('A wrong turn.')->assertSee('Demo website')->assertSee('Back to site home')
        ->assertSee('http://localhost:8000/s/error-demo/', false)->assertDontSee('private-value')
        ->assertDontSee('Back to your sites')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('renders missing slugs and unconnected custom domains as hosted-site errors', function () {
    foreach (['/s/unknown-site/missing', 'http://unconnected.example.test/missing'] as $url) {
        $this->get($url)->assertNotFound()->assertSee('No destination')
            ->assertDontSee('Back to site home')->assertDontSee('Your sites');
    }
});

it('preserves uploaded 404.html on free sites', function () {
    Storage::disk('public')->put('sites/error-demo/404.html', '<h1>Bundled free fallback</h1>');
    $this->get('/s/error-demo/missing')->assertNotFound()->assertSee('Bundled free fallback')
        ->assertSee('<base href="http://localhost:8000/s/error-demo/">', false);
});

it('serves paid overrides ahead of bundled 404s with escaped variables and no Blade evaluation', function () {
    $this->owner->forceFill(['plan' => 'paid'])->save();
    $this->site->update(['original_name' => '<img src=x onerror=alert(1)>']);
    Storage::disk('public')->put('sites/error-demo/404.html', '<h1>Old fallback</h1>');
    $this->site->errorPages()->create(['status_code' => 404, 'html' => '<h1>Our custom page</h1>{{site_name}} {{requested_path}} {{status_code}} {{ config("app.key") }} <a href="{{site_url}}">Home</a>']);
    $this->get('/s/error-demo/missing')->assertNotFound()->assertSee('Our custom page')
        ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)->assertDontSee('<img src=x', false)
        ->assertSee('{{ config("app.key") }}', false)->assertDontSee('Old fallback')
        ->assertHeader('Cache-Control', 'no-store, private');
});

it('uses the current site host and base URL on custom domains and subdomains', function () {
    $this->owner->forceFill(['plan' => 'paid'])->save();
    $this->site->errorPages()->create(['status_code' => 404, 'html' => '<a href="{{site_url}}">Custom home</a>']);
    Domain::create(['site_id' => $this->site->id, 'domain' => 'brand.example.test', 'is_custom' => true]);
    $this->get('http://brand.example.test/docs/missing')->assertNotFound()
        ->assertSee('<a href="http://brand.example.test/">', false)->assertSee('<base href="http://brand.example.test/">', false);

    Route::domain('{slug}.localhost')->get('/{path?}', [SiteController::class, 'showSubdomain'])->where('path', '.*');
    $this->get('http://error-demo.localhost/missing')->assertNotFound()
        ->assertSee('<a href="http://error-demo.localhost/">', false);
});

it('does not use overrides after downgrade or when disabled', function () {
    $this->owner->forceFill(['plan' => 'paid'])->save();
    $page = $this->site->errorPages()->create(['status_code' => 404, 'html' => 'Paid override', 'enabled' => false]);
    Storage::disk('public')->put('sites/error-demo/404.html', 'Free fallback');
    $this->get('/s/error-demo/missing')->assertSee('Free fallback')->assertDontSee('Paid override');
    $page->update(['enabled' => true]);
    $this->owner->forceFill(['plan' => 'free'])->save();
    $this->get('/s/error-demo/missing')->assertSee('Free fallback')->assertDontSee('Paid override');
    expect($page->fresh()->html)->toBe('Paid override');
});

it('keeps overrides isolated to their site and honors password protection', function () {
    $this->owner->forceFill(['plan' => 'paid'])->save();
    $this->site->errorPages()->create(['status_code' => 404, 'html' => 'Private custom content']);
    $other = Site::create(['slug' => 'other-website', 'path' => 'sites/other', 'status' => 'active']);
    $this->get('/s/other-website/missing')->assertNotFound()->assertDontSee('Private custom content');
    $this->site->update(['is_password_protected' => true, 'site_password' => Hash::make('secret')]);
    $this->get('/s/error-demo/missing')->assertStatus(401)->assertDontSee('Private custom content');
});

it('preserves status codes for unavailable sites and server failures', function (int $status) {
    $this->owner->forceFill(['plan' => 'paid'])->save();
    $this->site->errorPages()->create(['status_code' => $status, 'html' => "Our {$status} page"]);
    if ($status === 403) {
        $this->site->update(['status' => 'suspended']);
    } else {
        $this->mock(SiteServingService::class)->makePartial()->shouldReceive('serve')->once()->andThrow(new HttpException($status, 'internal failure detail'));
    }
    $response = $this->get('/s/error-demo/')->assertStatus($status)
        ->assertSee("Our {$status} page")->assertDontSee('internal failure detail');
    if ($status === 503) {
        $response->assertHeader('Retry-After', '60');
    }
})->with([403, 500, 503]);

it('never reads a bundled error page outside the site directory', function () {
    Storage::disk('public')->put('outside-secret.html', 'do not disclose');
    symlink(Storage::disk('public')->path('outside-secret.html'), Storage::disk('public')->path('sites/error-demo/404.html'));
    $this->get('/s/error-demo/missing')->assertNotFound()->assertDontSee('do not disclose')->assertSee('A wrong turn.');
});

it('returns JSON to JSON clients and never turns a 404 into a 304', function () {
    $this->getJson('/s/error-demo/missing')->assertNotFound()->assertJson(['message' => 'Not Found']);
    $this->withHeader('If-None-Match', 'anything')->get('/s/error-demo/missing')->assertNotFound();
});

it('provides a paid editor but prevents free-account writes', function () {
    $this->actingAs($this->owner)->get(route('dashboard.sites.errors', $this->site))
        ->assertOk()->assertSee('Paid feature')->assertSee('Free sites can still use');
    Livewire::actingAs($this->owner)->test('dashboard.site-errors', ['siteId' => $this->site->id])
        ->set('html', '<h1>Unpaid override</h1>')->call('save')->assertForbidden();
    expect($this->site->errorPages()->count())->toBe(0);
});

it('allows paid owners to save, select and remove their own overrides', function () {
    $this->owner->forceFill(['plan' => 'paid'])->save();
    Livewire::actingAs($this->owner)->test('dashboard.site-errors', ['siteId' => $this->site->id])
        ->set('html', '<h1>Brand 404</h1>')->call('save')->assertHasNoErrors()->assertDispatched('error-page-saved')
        ->call('selectStatus', 503)->set('html', '<h1>Maintenance</h1>')->call('save')->assertHasNoErrors()
        ->call('selectStatus', 404)->assertSet('html', '<h1>Brand 404</h1>')->call('removeOverride');
    expect($this->site->errorPages()->pluck('status_code')->all())->toBe([503]);
});

it('rejects foreign sites, invalid statuses and oversized source', function () {
    $other = User::factory()->create();
    $other->forceFill(['plan' => 'paid'])->save();
    expect(fn () => Livewire::actingAs($other)->test('dashboard.site-errors', ['siteId' => $this->site->id]))
        ->toThrow(ModelNotFoundException::class);
    $this->owner->forceFill(['plan' => 'paid'])->save();
    Livewire::actingAs($this->owner)->test('dashboard.site-errors', ['siteId' => $this->site->id])
        ->set('html', str_repeat('a', 100001))->call('save')->assertHasErrors(['html']);
    Livewire::actingAs($this->owner)->test('dashboard.site-errors', ['siteId' => $this->site->id])
        ->call('selectStatus', 200)->assertStatus(422);
});

it('manages paid entitlement from the server and not through mass assignment', function () {
    $this->owner->fill(['plan' => 'paid'])->save();
    expect($this->owner->fresh()->hasPaidPlan())->toBeFalse();
    $this->artisan('accounts:set-plan', ['email' => $this->owner->email, 'plan' => 'paid'])->assertSuccessful();
    expect($this->owner->fresh()->hasPaidPlan())->toBeTrue();
    $this->artisan('accounts:set-plan', ['email' => $this->owner->email, 'plan' => 'invalid'])->assertFailed();
});

it('retains error pages across cloning and includes them in owner exports', function () {
    Storage::fake('media');
    Storage::fake('local');
    $this->site->errorPages()->create(['status_code' => 404, 'html' => '<h1>Owned design</h1>']);
    $clone = app(SiteDeploymentService::class)->duplicateSite($this->site, $this->owner);
    expect($clone->errorPages()->first()->html)->toBe('<h1>Owned design</h1>');
    $export = app(UserDataExportService::class)->export($this->owner);
    expect(collect($export['sites'])->firstWhere('id', $this->site->id)['error_pages'][0]['html'])->toBe('<h1>Owned design</h1>');
    app(SiteDeploymentService::class)->deleteSite($clone, $this->owner);
    expect($clone->errorPages()->count())->toBe(0)->and($this->site->errorPages()->count())->toBe(1);
});

it('revokes editor writes immediately if a paid account is downgraded', function () {
    $this->owner->forceFill(['plan' => 'paid'])->save();
    $component = Livewire::actingAs($this->owner)->test('dashboard.site-errors', ['siteId' => $this->site->id]);
    User::whereKey($this->owner->id)->update(['plan' => 'free']);
    $component->set('html', 'Draft after downgrade')->call('save')->assertForbidden();
    expect($this->site->errorPages()->count())->toBe(0);
});

it('preserves an existing retry hint when rendering a hosted 503', function () {
    $this->mock(SiteServingService::class)->shouldReceive('serve')->once()
        ->andThrow(new HttpException(503, 'Please retry', headers: ['Retry-After' => '120']));
    $this->get('/s/error-demo/')->assertStatus(503)->assertHeader('Retry-After', '120')
        ->assertSee('A little pause.');
});
