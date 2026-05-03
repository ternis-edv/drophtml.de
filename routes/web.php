<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SiteController;

$host = parse_url(config('app.url'), PHP_URL_HOST);

if ($host && $host !== 'localhost') {
    Route::domain('{slug}.' . $host)->group(function () {
        Route::get('/{path?}', [SiteController::class, 'show'])->where('path', '.*');
    });
}

Route::view('/', 'welcome')->name('home');

Route::get('/s/{slug}/{path?}', [SiteController::class, 'show'])->where('path', '.*');

use App\Http\Controllers\Auth\SocialiteController;

Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])->name('social.redirect');
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])->name('social.callback');

Route::post('/webhooks/github', [App\Http\Controllers\Webhooks\GithubWebhookController::class, 'handle'])->name('webhooks.github');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('dashboard/sites/upload', 'dashboard.sites-upload')->name('dashboard.sites.upload');
    Route::view('dashboard/sites/{site}/edit', 'dashboard.site-editor')->name('dashboard.sites.edit');
    Route::view('dashboard/sites/{site}/domains', 'dashboard.site-domains')->name('dashboard.sites.domains');
    Route::view('dashboard/sites/{site}/stats', 'dashboard.site-stats')->name('dashboard.sites.stats');
    Route::view('dashboard/sites/{site}/settings', 'dashboard.site-settings')->name('dashboard.sites.settings');
    Route::view('dashboard/activity', 'dashboard.activity')->name('dashboard.activity');
});

require __DIR__.'/settings.php';
