<?php

use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\Webhooks\GithubWebhookController;
use App\Models\Site;
use Illuminate\Support\Facades\Route;

$host = parse_url(config('app.url'), PHP_URL_HOST);
$escapedHost = $host ? preg_quote($host, '/') : 'drophtml\.de';
$appDomain = $host ?: 'localhost';

// 1. Subdomain routing for {slug}.drophtml.de
if ($host && $host !== 'localhost') {
    Route::domain('{slug}.'.$host)->group(function () {
        Route::get('/{path?}', [SiteController::class, 'showSubdomain'])->where('path', '.*');
        Route::post('/_unlock', [SiteController::class, 'unlockSubdomain'])->name('site.subdomain.unlock');
    });
}

// 2. Custom domains routing (matches any non-app host domain)
Route::domain('{custom_domain}')->where(['custom_domain' => '^(?!localhost$|127\.0\.0\.1$|'.$escapedHost.'$|.*\.'.$escapedHost.'$).*'])->group(function () {
    Route::get('/{path?}', [SiteController::class, 'showCustomDomain'])->where('path', '.*');
    Route::post('/_unlock', [SiteController::class, 'unlockCustomDomain'])->name('site.custom_domain.unlock');
});

// 3. Main App routes (scoped to app domain or localhost)
Route::domain($appDomain)->group(function () {
    Route::get('/', function () {
        return view('welcome', [
            'totalSites' => Site::count(),
        ]);
    })->name('home');

    Route::get('/legal/{page}', function ($page) {
        if (! in_array($page, ['privacy', 'terms', 'imprint'])) {
            abort(404);
        }

        return view('pages.legal.show', ['page' => $page]);
    })->name('legal.show');

    Route::get('/s/{slug}/{path?}', [SiteController::class, 'show'])->where('path', '.*');
    Route::post('/s/{slug}/_unlock', [SiteController::class, 'unlock'])->name('site.unlock');

    Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])->name('social.callback');

    Route::post('/webhooks/github', [GithubWebhookController::class, 'handle'])->name('webhooks.github');

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
});
